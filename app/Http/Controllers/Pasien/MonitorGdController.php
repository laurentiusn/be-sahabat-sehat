<?php

namespace App\Http\Controllers\Pasien;

use App\Http\Controllers\Controller;
use App\Models\MonitorGd;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MonitorGdController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        /** @var \App\Models\User $user */

        $user = Auth::user();
        $latest = $user->load(['latestMonitorGd']);
        $historyGd = MonitorGd::where('id_user', $user->id)
            ->orderBy('tanggal', 'desc')
            ->get();

        return response()->json([
            'statusCode' => 200,
            'message' => 'Data Monitor Gula Darah',
            'data' => [
                'latestMonitorGd' => $latest->latestMonitorGd,
                'historyGd'       => $historyGd,
            ]
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gula_darah' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 422,
                'message'    => 'Validasi gagal',
                'errors'     => $validator->errors()
            ], 422);
        }

        $gulaDarahValue = (float) $request->gula_darah;

        // 2. Ambil Data Indikator & Urutkan Prioritas
        // Urutan prioritas penting: TINGGI -> WASPADA -> NORMAL
        // Agar jika nilai memenuhi kriteria tinggi, langsung terdeteksi tinggi.
        $indicators = DB::table('indikator')->where('type', 'gd')->get();

        $priorityMap = [
            'TINGGI'  => 3,
            'WASPADA' => 2,
            'NORMAL'  => 1
        ];

        $sortedIndicators = $indicators->sortByDesc(function ($item) use ($priorityMap) {
            return $priorityMap[$item->status] ?? 0;
        });

        $finalStatus = 'Tidak Diketahui'; // Default jika angka di luar range seeder (misal < 70 hipoglikemia)

        // 3. Proses Klasifikasi
        foreach ($sortedIndicators as $indicator) {
            $range = json_decode($indicator->range, true);
            $min = $range['min']; // Bisa null
            $max = $range['max']; // Bisa null

            // Cek batas bawah (jika min null, anggap lolos batas bawah)
            $passMin = is_null($min) || $gulaDarahValue >= $min;

            // Cek batas atas (jika max null, anggap lolos batas atas)
            $passMax = is_null($max) || $gulaDarahValue <= $max;

            // Jika masuk dalam range
            if ($passMin && $passMax) {
                $finalStatus = $indicator->status;
                break; // Ketemu status yang cocok, hentikan loop
            }
        }

        // 4. Simpan ke Database
        try {
            $monitorGd = MonitorGd::create([
                'id_user'    => Auth::id(),
                'tanggal'    => now(),
                'gula_darah' => $gulaDarahValue,
                'status'     => $finalStatus,
            ]);

            return response()->json([
                'statusCode' => 201,
                'message'    => 'Data Gula Darah berhasil disimpan',
                'data'       => $monitorGd
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
        $latest = $user->load(['latestMonitorGd']);
        $historyGd = MonitorGd::where('id_user', $user->id)
            ->orderBy('tanggal', 'desc')
            ->get();

        return response()->json([
            'statusCode' => 200,
            'message' => 'Data Monitor Gula Darah',
            'data' => [
                'latestMonitorGd' => $latest->latestMonitorGd,
                'historyGd'       => $historyGd,
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
        // Cari data berdasarkan ID dan pastikan milik user yang sedang login
        $monitorGd = MonitorGd::where('id', $id)
            ->where('id_user', Auth::id())
            ->first();

        if (!$monitorGd) {
            return response()->json([
                'statusCode' => 404,
                'message'    => 'Data Monitor Gula Darah tidak ditemukan atau Anda tidak memiliki akses.'
            ], 404);
        }

        try {
            // Soft delete (mengisi kolom deleted_at)
            $monitorGd->delete();

            return response()->json([
                'statusCode' => 200,
                'message'    => 'Data berhasil dihapus'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'statusCode' => 500,
                'message'    => 'Gagal menghapus data',
                'error'      => $e->getMessage()
            ], 500);
        }
    }
}
