<?php

namespace App\Http\Controllers\Pasien;

use App\Http\Controllers\Controller;
use App\Models\MonitorTd;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MonitorTdController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        /** @var \App\Models\User $user */

        $user = Auth::user();
        $latest = $user->load(['latestMonitorTd']);
        $historyTd = MonitorTd::where('id_user', $user->id)
            ->orderBy('tanggal', 'desc')
            ->get();

        return response()->json([
            'statusCode' => 200,
            'message' => 'Data Monitor Tekanan Darah',
            'data' => [
                'latestMonitorTd' => $latest->latestMonitorTd,
                'historyTd'       => $historyTd,
            ]
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sistolik'  => 'required|numeric|min:0',
            'diastolik' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 422,
                'message'    => 'Validasi gagal',
                'errors'     => $validator->errors()
            ], 422);
        }

        $sys = (int) $request->sistolik;
        $dia = (int) $request->diastolik;

        // 2. Ambil Data Indikator & Urutkan Berdasarkan Prioritas Keparahan
        // Kita harus mengecek dari yang terparah dulu (Stadium 2 -> Normal)
        // Karena logika medis: ambil kondisi terburuk.
        $indicators = DB::table('indikator')->where('type', 'td')->get();

        // Mapping prioritas untuk sorting
        $priorityMap = [
            'Stadium 2'      => 4,
            'Stadium 1'      => 3,
            'Pre-hipertensi' => 2,
            'Normal'         => 1
        ];

        $sortedIndicators = $indicators->sortByDesc(function ($item) use ($priorityMap) {
            return $priorityMap[$item->status] ?? 0;
        });

        $finalStatus = 'Tidak Diketahui'; // Default fallback

        // 3. Proses Klasifikasi
        foreach ($sortedIndicators as $indicator) {
            $range = json_decode($indicator->range, true);
            $operator = $range['operator'] ?? 'AND';

            // Cek Range Sistolik
            $isSysMatch = true;
            if (!is_null($range['sys_min']) && $sys < $range['sys_min']) $isSysMatch = false;
            if (!is_null($range['sys_max']) && $sys > $range['sys_max']) $isSysMatch = false;

            // Cek Range Diastolik
            $isDiaMatch = true;
            if (!is_null($range['dia_min']) && $dia < $range['dia_min']) $isDiaMatch = false;
            if (!is_null($range['dia_max']) && $dia > $range['dia_max']) $isDiaMatch = false;

            // Evaluasi Operator (AND vs OR)
            $matched = false;
            if ($operator === 'OR') {
                // Salah satu masuk range (biasanya untuk hipertensi)
                if ($isSysMatch || $isDiaMatch) {
                    $matched = true;
                }
            } else {
                // Keduanya harus masuk range (biasanya untuk Normal)
                if ($isSysMatch && $isDiaMatch) {
                    $matched = true;
                }
            }

            // Jika cocok, set status dan berhenti looping (karena sudah diurutkan dari terparah)
            if ($matched) {
                $finalStatus = $indicator->status;
                break;
            }
        }

        // 4. Simpan ke Database
        try {
            $monitorTd = MonitorTd::create([
                'id_user'   => Auth::id(),
                'tanggal'   => now(),
                'sistolik'  => $sys,
                'diastolik' => $dia,
                'status'    => $finalStatus,
            ]);

            return response()->json([
                'statusCode' => 201,
                'message'    => 'Data berhasil disimpan',
                'data'       => $monitorTd
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
        $latest = $user->load(['latestMonitorTd']);
        $historyTd = MonitorTd::where('id_user', $user->id)
            ->orderBy('tanggal', 'desc')
            ->get();

        return response()->json([
            'statusCode' => 200,
            'message' => 'Data Monitor Tekanan Darah',
            'data' => [
                // 'latestMonitorTd' => $latest->latestMonitorTd,
                'historyTd'       => $historyTd,
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
        $monitorTd = MonitorTd::where('id', $id)
            ->where('id_user', Auth::id())
            ->first();

        if (!$monitorTd) {
            return response()->json([
                'statusCode' => 404,
                'message'    => 'Data Monitor Tekanan Darah tidak ditemukan atau Anda tidak memiliki akses.'
            ], 404);
        }

        try {
            // Karena di migration pakai softDeletes(), ini hanya akan mengisi kolom deleted_at
            $monitorTd->delete();

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
