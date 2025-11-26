<?php

namespace App\Http\Controllers\Pasien;

use App\Http\Controllers\Controller;
use App\Models\Indikator;
use App\Models\ObatLog;
use App\Models\Tips;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PasienController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function dashboard()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->load(['latestMonitorGd', 'latestMonitorImt', 'latestMonitorTd']);

        $indikator = Indikator::all();

        $statusTd = $user->latestMonitorTd->status ?? 'Belum ada data';
        $statusGd = $user->latestMonitorGd->status ?? 'Belum ada data';
        $statusImt = $user->latestMonitorImt->status ?? 'Belum ada data';

        $nilaiTd = $user->latestMonitorTd 
            ? $user->latestMonitorTd->sistolik . '/' . $user->latestMonitorTd->diastolik . ' mmHg' 
            : '--';

        $nilaiGd = $user->latestMonitorGd 
            ? $user->latestMonitorGd->gula_darah . ' mg/dL' 
            : '--';

        $nilaiImt = $user->latestMonitorImt 
            ? $user->latestMonitorImt->imt . ' kg/m²' 
            : '--';
        
        return response()->json([
            'statusCode' => 200,
            'message' => 'Data Dashboard Pasien',
            'data' => [
                'user'      => $user->id,
                'nama'      => $user->nama_lengkap,
                'nilaiTd'   => $nilaiTd,
                'statusTd'  => $statusTd,
                'nilaiGd'   => $nilaiGd,
                'statusGd'  => $statusGd,
                'nilaiImt'  => $nilaiImt,
                'statusImt' => $statusImt,
                'indikator' => $indikator,
            ]
        ], 200);
    }

    public function tipsHidup()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->load(['latestMonitorGd', 'latestMonitorImt', 'latestMonitorTd']);

        $data = [
            'td'  => $this->getTipsByIndikator('td', $user->latestMonitorTd),
            'gd'  => $this->getTipsByIndikator('gd', $user->latestMonitorGd),
            'imt' => $this->getTipsByIndikator('imt', $user->latestMonitorImt),
        ];

        return response()->json([
            'statusCode' => 200,
            'message'    => 'Tips Hidup Sehat Berdasarkan Indikator Terakhir',
            'data'       => $data
        ], 200);
    }

    private function getTipsByIndikator($type, $value)
    {
        if (!$value) {
            return [
                'status_terakhir' => 'Belum ada data',
                'nilai_terakhir'  => null,
                'tips'            => []
            ];
        }

        // 1. Ambil Status dari data monitoring (misal: 'Stadium 1' atau 'Normal')
        $status = $value->status;

        // 2. Cari ID Indikator yang cocok dengan Type dan Status tersebut
        $indikator = Indikator::where('type', $type)
            ->where('status', $status)
            ->first();

        $tipsList = [];

        if ($indikator) {
            $tipsList = Tips::with('gambarTips') // Load gambarnya juga
                ->where('id_indikator', $indikator->id)
                ->first();
        }

        // Susun data agar rapi di frontend
        // Kita perlu menyesuaikan format nilai karena TD punya 2 angka (sys/dia) sedangkan GD/IMT cuma 1
        $nilai = null;
        if ($type === 'td') {
            $nilai = $value->sistolik . '/' . $value->diastolik . ' mmHg';
        } elseif ($type === 'gd') {
            $nilai = $value->gula_darah . ' mg/dL';
        } elseif ($type === 'imt') {
            $nilai = $value->imt . ' kg/m²';
        }

        return [
            'status_terakhir' => $status,
            'nilai_terakhir'  => $nilai,
            'tanggal_cek'     => $value->tanggal, // Opsional: tampilkan kapan terakhir cek
            'tips'            => $tipsList
        ];
    }

    public function listObat()
    {
        $userId = Auth::id();
        $today  = Carbon::today()->format('Y-m-d'); // 2025-11-24

        // 1. Ambil Log Hari Ini milik User yang Login
        $logs = ObatLog::with('obat')
            ->where('jadwal_konsumsi', $today)
            ->whereHas('obat', function($q) use ($userId) {
                $q->where('id_user', $userId);
            })
            ->get();

        // 2. Sorting (Pagi -> Malam)
        $sortedLogs = $logs->sortBy(function ($log) {
            $urutan = ['pagi' => 1, 'siang' => 2, 'sore' => 3, 'malam' => 4];
            return $urutan[strtolower($log->obat->waktu ?? '')] ?? 99;
        })->values();

        return response()->json([
            'statusCode' => 200,
            'message'    => 'Jadwal Obat Hari Ini',
            'tanggal'    => Carbon::now()->format('d-m-Y'),
            'data'       => $sortedLogs
        ]);
    }

    public function checklist(Request $request, string $logId)
    {
        // 1. Cari Log, TAPI pastikan punya user yang login (SECURITY)
        $log = ObatLog::where('id', $logId)
            ->whereHas('obat', function($q) {
                $q->where('id_user', Auth::id());
            })
            ->first();

        if (!$log) {
            return response()->json([
                'statusCode' => 404, 
                'message' => 'Jadwal tidak ditemukan atau bukan milik Anda'
            ], 404);
        }

        // 2. Validasi Input
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:sudah,belum'
        ]);

        if ($validator->fails()) {
            return response()->json(['statusCode'=>422, 'errors'=>$validator->errors()], 422);
        }

        // 3. Update Data
        $log->update([
            'status' => $request->status,
            // Jika 'sudah', catat waktu sekarang. Jika 'belum' (undo), hapus waktunya.
            'waktu_aktual_konsumsi' => $request->status == 'sudah' ? now() : null
        ]);

        return response()->json([
            'statusCode' => 200,
            'message'    => 'Status obat berhasil diperbarui',
            'data'       => $log
        ]);
    }
}
