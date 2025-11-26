<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Obat;
use App\Models\ObatLog;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ObatController extends Controller
{
    /**
     * Index: List Riwayat Obat Pasien (Untuk Admin)
     */
    public function index(Request $request) 
    {
        if (!$request->has('id_user')) {
            return response()->json(['statusCode'=>400, 'message'=>'id_user diperlukan'], 400);
        }

        // 1. Ambil semua Log milik user
        $logs = ObatLog::with(['obat'])
            ->whereHas('obat', function($query) use ($request) {
                $query->where('id_user', $request->id_user);
            })
            // Urutkan tanggal dari yang terbaru (Desc)
            ->orderBy('jadwal_konsumsi', 'desc') 
            ->get();

        // 2. Custom Sorting (Tanggal & Waktu) lalu Flatten
        $timeline = $logs->groupBy(function ($item) {
            // Group dulu per tanggal agar kita bisa sort waktu per harinya
            return $item->jadwal_konsumsi->format('Y-m-d');
        })->map(function ($itemsInDate) {
            // Di dalam setiap tanggal, urutkan berdasarkan Waktu
            return $itemsInDate->sortBy(function ($log) {
                $urutanWaktu = [
                    'pagi'  => 1,
                    'siang' => 2,
                    'sore'  => 3,
                    'malam' => 4
                ];
                return $urutanWaktu[strtolower($log->obat->waktu)] ?? 99;
            })->values();
        })
        ->flatten(1); // <--- INI KUNCINYA: Menggabungkan grup kembali menjadi satu array

        return response()->json([
            'statusCode' => 200, 
            'message'    => 'Jadwal Harian Obat',
            'data'       => $timeline
        ]);
    }
    /**
     * Store: Simpan Obat & Generate Log Harian
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_user'    => 'required|exists:users,id',
            'nama_obat'  => 'required|string',
            'dosis'      => 'required|string',
            'waktu'      => 'required|in:pagi,siang,sore,malam',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'keterangan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['statusCode' => 422, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            // 1. Simpan Master Obat
            $obat = Obat::create([
                'id_user'    => $request->id_user,
                'nama_obat'  => $request->nama_obat,
                'dosis'      => $request->dosis,
                'waktu'      => $request->waktu,
                'start_date' => $request->start_date,
                'end_date'   => $request->end_date,
                'keterangan' => $request->keterangan,
            ]);

            // 2. Generate Log Harian (Looping Tanggal)
            $period = CarbonPeriod::create($request->start_date, $request->end_date);

            foreach ($period as $date) {
                ObatLog::create([
                    'id_obat'         => $obat->id,
                    'jadwal_konsumsi' => $date->format('d-m-Y'), // Di DB tetap Y-m-d
                    'status'          => 'belum',
                ]);
            }

            DB::commit();

            return response()->json([
                'statusCode' => 201,
                'message'    => 'Jadwal obat berhasil dibuat',
                'data'       => $obat->load('logs')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['statusCode' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Stop: Hentikan Obat & Hapus Jadwal Masa Depan
     */
    public function stop(Request $request, string $id)
    {
        $obat = Obat::find($id);

        if (!$obat) {
            return response()->json(['statusCode' => 404, 'message' => 'Obat tidak ditemukan'], 404);
        }

        // Default tanggal stop hari ini
        $tanggalStop = $request->input('tanggal_stop', Carbon::now()->format('Y-m-d'));

        DB::beginTransaction();
        try {
            // 1. Update Master: Potong tanggal akhir
            $obat->update([
                'end_date'   => $tanggalStop,
                'keterangan' => $obat->keterangan . " (Dihentikan dokter pada $tanggalStop)",
            ]);

            // 2. Hapus Log Masa Depan ( > tanggalStop )
            // Force Delete agar bersih dari jadwal
            ObatLog::where('id_obat', $id)
                   ->whereDate('jadwal_konsumsi', '>', $tanggalStop)
                   ->forceDelete();

            DB::commit();

            return response()->json([
                'statusCode' => 200,
                'message'    => 'Obat berhasil dihentikan. Jadwal masa depan dihapus.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['statusCode' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update Log Status (Checklist sudah/belum)
     */
    public function updateLogStatus(Request $request, string $logId)
    {
        $log = ObatLog::find($logId);
        if (!$log) return response()->json(['statusCode' => 404, 'message' => 'Log tidak ditemukan'], 404);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:sudah,belum'
        ]);

        if ($validator->fails()) return response()->json(['statusCode'=>422, 'errors'=>$validator->errors()], 422);

        $log->update([
            'status' => $request->status,
            'waktu_aktual_konsumsi' => $request->status == 'sudah' ? now() : null
        ]);

        return response()->json([
            'statusCode' => 200,
            'message'    => 'Status diperbarui',
            'data'       => $log
        ]);
    }
    
    /**
     * MENAMPILKAN DAFTAR MASTER OBAT (Admin View)
     * Endpoint ini dipakai untuk mengisi list di Dashboard Admin.
     * Menampilkan: Waktu, Nama, Dosis, Rentang Tanggal.
     */
    public function listMasterObat(Request $request)
    {
        // 1. Validasi ID User
        if (!$request->has('id_user')) {
            return response()->json(['statusCode'=>400, 'message'=>'id_user diperlukan'], 400);
        }

        // 2. Ambil Master Obat milik pasien
        // Kita urutkan berdasarkan 'created_at' desc (terbaru di atas) 
        // atau bisa juga berdasarkan status aktif.
        $daftarObat = Obat::where('id_user', $request->id_user)
            ->orderBy('created_at', 'desc')
            ->get();

        // 3. Modifikasi data agar mudah dipakai Frontend
        // Kita tambahkan status "Aktif/Non-Aktif" berdasarkan tanggal hari ini
        $formattedData = $daftarObat->map(function($item) {
            
            // Cek apakah obat masih aktif (Hari ini <= End Date)
            // Carbon::createFromFormat karena cast model kita d-m-Y
            $endDate = Carbon::createFromFormat('d-m-Y', $item->end_date);
            $today   = Carbon::today();
            $isActive = $endDate->greaterThanOrEqualTo($today);

            return [
                'id'         => $item->id,
                'waktu'      => ucfirst($item->waktu), // "Pagi", "Siang"...
                'nama_obat'  => $item->nama_obat,
                'dosis'      => $item->dosis,
                
                // Format Rentang Tanggal untuk UI: "01-11-2025 s.d 05-11-2025"
                'rentang'    => $item->start_date . ' s.d ' . $item->end_date,
                
                // Status tambahan untuk styling di frontend (Opsional)
                // Jika false, frontend bisa bikin text jadi abu-abu/disabled
                'is_active'  => $isActive, 
                
                // Keterangan tambahan (misal: "Dihentikan Dokter")
                'keterangan' => $item->keterangan
            ];
        });

        return response()->json([
            'statusCode' => 200,
            'message'    => 'Daftar Rencana Pengobatan Pasien',
            'data'       => $formattedData
        ]);
    }

    public function destroy(string $id)
    {
        // Cari obat (bisa gunakan find or fail atau find biasa)
        $obat = Obat::find($id);

        if (!$obat) {
            return response()->json(['statusCode' => 404, 'message' => 'Obat tidak ditemukan'], 404);
        }

        try {
            // PENTING: Gunakan forceDelete() untuk menghapus permanen.
            // Karena di migration kita set 'cascadeOnDelete()', 
            // maka semua LOG harian otomatis ikut terhapus bersih dari database.
            $obat->forceDelete(); 

            return response()->json([
                'statusCode' => 200,
                'message'    => 'Data obat berhasil dihapus permanen.'
            ]);

        } catch (\Exception $e) {
            return response()->json(['statusCode' => 500, 'message' => $e->getMessage()], 500);
        }
    }
}
