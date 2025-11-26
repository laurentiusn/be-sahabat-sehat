<?php

namespace App\Http\Controllers\Pasien;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use App\Models\LogHarian;
use App\Models\LogMakanan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CatatanHarianController extends Controller
{
    /**
     * LIST RIWAYAT (Sesuai Tabel UI)
     */
    public function index()
    {
        $userId = Auth::id();

        // 1. DATA RIWAYAT HARIAN (Logic Lama)
        $riwayat = LogHarian::with(['makanan', 'aktivitas'])
            ->where('id_user', $userId)
            ->orderBy('tanggal', 'desc')
            ->get();

        $formattedRiwayat = $riwayat->map(function ($log) {
            return [
                'id_log_harian' => $log->id,
                'tanggal'       => $log->tanggal->format('d - m - Y'),
                'makanan_utama' => $log->makanan->where('type', 'utama')->values()->map(fn($m) => [
                    'id' => $m->id, 'nama' => $m->nama_makanan
                ]),
                'makanan_selingan' => $log->makanan->where('type', 'selingan')->values()->map(fn($m) => [
                    'id' => $m->id, 'nama' => $m->nama_makanan
                ]),
                'aktivitas_fisik' => $log->aktivitas->values()->map(fn($a) => [
                    'id' => $a->id, 'nama' => $a->nama_aktivitas, 'durasi' => $a->durasi . ' menit'
                ]),
            ];
        });

        // 2. DATA BARU: TOTAL MENIT PER BULAN
        // Kita join tabel log_aktivitas dengan log_harian untuk mendapatkan tanggalnya
        $statistikBulanan = DB::table('log_aktivitas')
            ->join('log_harian', 'log_aktivitas.id_log_harian', '=', 'log_harian.id')
            ->where('log_harian.id_user', $userId)
            ->select(
                // Format Tanggal untuk Grouping (Tahun-Bulan)
                DB::raw("DATE_FORMAT(log_harian.tanggal, '%Y-%m') as periode_raw"),
                // Format Label untuk UI (November 2025)
                DB::raw("DATE_FORMAT(log_harian.tanggal, '%M %Y') as periode_label"),
                // Hitung Total Durasi
                DB::raw("SUM(log_aktivitas.durasi) as total_menit")
            )
            ->groupBy('periode_raw', 'periode_label')
            ->orderBy('periode_raw', 'desc') // Bulan terbaru di atas
            ->get();

        // Format hasil statistik agar lebih rapi
        $formattedStatistik = $statistikBulanan->map(function($item) {
            return [
                'bulan' => $item->periode_label, // Contoh: "November 2025"
                'total' => (int) $item->total_menit . ' menit' // Contoh: "45 menit"
            ];
        });

        return response()->json([
            'statusCode' => 200,
            'message'    => 'Riwayat Catatan Harian',
            'data'       => [
                'statistik_bulanan' => $formattedStatistik, // <--- Data Baru
                'riwayat_harian'    => $formattedRiwayat    // <--- Data Lama
            ]
        ]);
    }

    /**
     * TAMBAH MAKANAN
     */
    public function storeMakanan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_makanan' => 'required|string|max:255',
            'type'         => 'required|in:utama,selingan',
            'tanggal'      => 'nullable|date', // Bisa input tanggal mundur kalau perlu
        ]);

        if ($validator->fails()) return response()->json(['statusCode'=>422, 'errors'=>$validator->errors()], 422);

        $tanggal = $request->tanggal ?? Carbon::today()->format('Y-m-d');
        $userId  = Auth::id();

        DB::beginTransaction();
        try {
            // 1. Parent: Cari atau Buat (FirstOrCreate)
            $logHarian = LogHarian::firstOrCreate(
                ['id_user' => $userId, 'tanggal' => $tanggal]
            );

            // 2. Child: Simpan Makanan
            $makanan = LogMakanan::create([
                'id_log_harian' => $logHarian->id,
                'nama_makanan'  => $request->nama_makanan,
                'type'          => $request->type,
                'jam_makan'     => Carbon::now()->format('H:i:s'),
            ]);

            DB::commit();

            return response()->json([
                'statusCode' => 201,
                'message'    => 'Makanan berhasil dicatat',
                'data'       => $makanan
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['statusCode' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * TAMBAH AKTIVITAS
     */
    public function storeAktivitas(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_aktivitas' => 'required|string|max:255',
            'durasi'         => 'required|integer|min:1',
            'tanggal'        => 'nullable|date',
        ]);

        if ($validator->fails()) return response()->json(['statusCode'=>422, 'errors'=>$validator->errors()], 422);

        $tanggal = $request->tanggal ?? Carbon::today()->format('Y-m-d');
        $userId  = Auth::id();

        DB::beginTransaction();
        try {
            // 1. Parent
            $logHarian = LogHarian::firstOrCreate(
                ['id_user' => $userId, 'tanggal' => $tanggal]
            );

            // 2. Child
            $aktivitas = LogAktivitas::create([
                'id_log_harian'  => $logHarian->id,
                'nama_aktivitas' => $request->nama_aktivitas,
                'durasi'         => $request->durasi,
                'jam_aktivitas'  => Carbon::now()->format('H:i:s'),
            ]);

            DB::commit();

            return response()->json([
                'statusCode' => 201,
                'message'    => 'Aktivitas berhasil dicatat',
                'data'       => $aktivitas
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['statusCode' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * HAPUS MAKANAN
     */
    public function destroyMakanan($id)
    {
        // Security Check: Gunakan whereHas('logHarian') 
        // Ini butuh relasi 'logHarian' di model LogMakanan
        $item = LogMakanan::where('id', $id)
            ->whereHas('logHarian', function($q) {
                $q->where('id_user', Auth::id());
            })->first();

        if (!$item) return response()->json(['statusCode' => 404, 'message' => 'Data tidak ditemukan'], 404);

        $item->delete();
        return response()->json(['statusCode' => 200, 'message' => 'Makanan dihapus']);
    }

    /**
     * HAPUS AKTIVITAS
     */
    public function destroyAktivitas($id)
    {
        // Security Check
        $item = LogAktivitas::where('id', $id)
            ->whereHas('logHarian', function($q) {
                $q->where('id_user', Auth::id());
            })->first();

        if (!$item) return response()->json(['statusCode' => 404, 'message' => 'Data tidak ditemukan'], 404);

        $item->delete();
        return response()->json(['statusCode' => 200, 'message' => 'Aktivitas dihapus']);
    }
}
