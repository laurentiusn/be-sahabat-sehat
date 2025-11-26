<?php

namespace App\Http\Controllers\Pasien;

use App\Http\Controllers\Controller;
use App\Models\MonitorImt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use function Symfony\Component\Clock\now;

class MonitorImtController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        /** @var \App\Models\User $user */

        $user = Auth::user();
        $latest = $user->load(['latestMonitorImt']);
        $historyImt = MonitorImt::where('id_user', $user->id)
            ->orderBy('tanggal', 'desc')
            ->get();

        return response()->json([
            'statusCode' => 200,
            'message' => 'Data Monitor Indeks Massa Tubuh',
            'data' => [
                'latestMonitorImt' => $latest->latestMonitorImt,
                'historyImt'       => $historyImt,
            ]
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'tinggi_badan' => 'required|numeric|min:1', // Asumsi input dalam CM
            'massa_badan'  => 'required|numeric|min:1', // Input dalam KG
        ]);

        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 422,
                'message'    => 'Validasi gagal',
                'errors'     => $validator->errors()
            ], 422);
        }

        // 2. Logika Konversi & Perhitungan Rumus
        $tbCm = (float) $request->tinggi_badan; // Input user (misal: 170)
        $bbKg = (float) $request->massa_badan;  // Input user (misal: 65)

        // [PENTING] Konversi CM ke Meter untuk rumus IMT
        // Rumus: Berat (kg) / (Tinggi (m) x Tinggi (m))
        $tbMeter = $tbCm / 100; 

        // Hitung IMT
        // Contoh: 65 / (1.7 * 1.7) = 22.49...
        $imtValue = $bbKg / ($tbMeter * $tbMeter);
        
        // Bulatkan 2 desimal
        $imtValue = round($imtValue, 2);

        // 3. Klasifikasi Status berdasarkan Seeder
        $indicators = DB::table('indikator')->where('type', 'imt')->get();
        $finalStatus = 'Tidak Diketahui';

        foreach ($indicators as $indicator) {
            $range = json_decode($indicator->range, true);
            $min = $range['min'];
            $max = $range['max'];

            // Cek range (handle null sebagai infinity)
            $passMin = is_null($min) || $imtValue >= $min;
            $passMax = is_null($max) || $imtValue <= $max;

            if ($passMin && $passMax) {
                $finalStatus = $indicator->status;
                break; 
            }
        }

        // 4. Simpan ke Database
        try {
            $monitorImt = MonitorImt::create([
                'id_user'      => Auth::id(),
                'tanggal'      => now(),
                'tinggi_badan' => $tbCm,     // Tetap simpan dalam CM (sesuai input user agar konsisten saat ditampilkan)
                'massa_badan'  => $bbKg,     // Simpan KG
                'imt'          => $imtValue, // Simpan hasil hitungan
                'status'       => $finalStatus,
            ]);

            return response()->json([
                'statusCode' => 201,
                'message'    => 'Data IMT berhasil disimpan',
                'data'       => $monitorImt
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'statusCode' => 500,
                'message'    => 'Terjadi kesalahan server',
                'error'      => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        /** @var \App\Models\User $user */
        $user = User::findOrFail($id);
        $latest = $user->load(['latestMonitorImt']);
        $historyImt = MonitorImt::where('id_user', $user->id)
            ->orderBy('tanggal', 'desc')
            ->get();

        return response()->json([
            'statusCode' => 200,
            'message' => 'Data Monitor Indeks Massa Tubuh',
            'data' => [
                'latestMonitorImt' => $latest->latestMonitorImt,
                'historyImt'       => $historyImt,
            ]
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $monitorImt = MonitorImt::where('id', $id)
            ->where('id_user', Auth::id())
            ->first();

        if (!$monitorImt) {
            return response()->json([
                'statusCode' => 404,
                'message'    => 'Data tidak ditemukan atau akses ditolak.'
            ], 404);
        }

        try {
            $monitorImt->delete();
            return response()->json([
                'statusCode' => 200, 
                'message' => 'Data berhasil dihapus'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'statusCode' => 500, 
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
