<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => ['nullable','string','max:255'],
            'page'   => ['nullable','integer','min:1']
        ]);
        // 1. Query Dasar
        $query = User::where('role', 'pasien')
            ->with(['latestMonitorTd', 'latestMonitorGd', 'latestMonitorImt']);

        // 2. Logika SEARCH
        if ($request->has('search') && $request->search != '') {
            $keyword = $request->search;
            $query->where('nama_lengkap', 'LIKE', '%' . $keyword . '%');
        }

        // 3. Eksekusi Query dengan PAGINATION (10 per halaman)
        // Ubah angka 10 sesuai kebutuhan
        $pasiens = $query->latest()->paginate(10);

        // 4. Formatting Data (Mengambil collection dari paginator)
        $formattedData = $pasiens->getCollection()->map(function ($user) {
            
            // Format TD
            $td = '-';
            if ($user->latestMonitorTd) {
                $td = $user->latestMonitorTd->sistolik . '/' . $user->latestMonitorTd->diastolik;
            }

            // Format GD & IMT
            $gd = $user->latestMonitorGd ? $user->latestMonitorGd->gula_darah : '-';
            $imt = $user->latestMonitorImt ? $user->latestMonitorImt->imt : '-';

            return [
                'id'           => $user->id,
                'nama_lengkap' => $user->nama_lengkap,
                'nik'          => $user->nik,
                'td_terakhir'  => $td,
                'gd_terakhir'  => $gd,
                'imt_terakhir' => $imt,
            ];
        });

        return response()->json([
            'statusCode' => 200,
            'message'    => 'List Pasien Dashboard Admin',
            'data'       => $formattedData,
            // Metadata Pagination untuk Frontend (Next/Prev button)
            'meta'       => [
                'current_page' => $pasiens->currentPage(),
                'from'         => $pasiens->firstItem(), // Item urutan ke berapa (misal: 11)
                'last_page'    => $pasiens->lastPage(),
                'per_page'     => $pasiens->perPage(),
                'to'           => $pasiens->lastItem(),  // Sampai item ke berapa (misal: 20)
                'total'        => $pasiens->total(),     // Total semua data di DB
            ]
        ]);
    }
    
    /**
     * MENAMBAH PASIEN BARU (Store)
     * Tanpa Password, sesuai schema database.
     */
    public function store(Request $request)
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'nama_lengkap'  => 'required|string|max:255',
            // Validasi NIK: Wajib angka, 16 digit, dan harus unik di tabel users
            'nik'           => 'required|numeric|digits:16|unique:users,nik',
            // Validasi No HP: Harus unik sesuai schema
            'no_hp'         => 'required|string|max:15|unique:users,no_hp',
            'alamat'        => 'required|string',
            // Sesuaikan enum database: 'laki-laki' atau 'perempuan'
            'jenis_kelamin' => 'required|in:laki-laki,perempuan',
            'tanggal_lahir' => 'required|date',
            'tempat_lahir'  => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'statusCode' => 422, 
                'message'    => 'Validasi gagal',
                'errors'     => $validator->errors()
            ], 422);
        }

        try {
            // 2. Buat User Baru (Tanpa Password)
            $user = User::create([
                'nama_lengkap'  => $request->nama_lengkap,
                'nik'           => $request->nik,
                'no_hp'         => $request->no_hp,
                'alamat'        => $request->alamat,
                'jenis_kelamin' => $request->jenis_kelamin,
                'tanggal_lahir' => $request->tanggal_lahir,
                'tempat_lahir'  => $request->tempat_lahir,
                
                // Set Role Pasien
                'role'          => 'pasien',
                
                // HAPUS bagian password karena kolom tidak ada di database
                // 'password' => ... (DIHAPUS)
            ]);

            return response()->json([
                'statusCode' => 201,
                'message'    => 'Pasien berhasil didaftarkan',
                'data'       => $user
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'statusCode' => 500, 
                'message'    => 'Gagal mendaftar pasien', 
                'error'      => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Detail Pasien (Show)
     * Jika Admin meng-klik nama pasien di tabel dashboard
     */
    public function show($id)
    {
        $user = User::with([
            'latestMonitorTd', 
            'latestMonitorGd', 
            'latestMonitorImt',
            'logHarian.makanan',   // Load log -> makanan
            'logHarian.aktivitas'  // Load log -> aktivitas
        ])->find($id);

        if (!$user) {
            return response()->json(['statusCode' => 404, 'message' => 'Pasien tidak ditemukan'], 404);
        }

        // 2. Formatting Data Catatan Harian
        // Logika ini menyalin logic dari CatatanHarianController agar format JSON-nya konsisten
        $formattedCatatanHarian = $user->logHarian->map(function ($log) {
            return [
                'id_log_harian' => $log->id,
                'tanggal'       => $log->tanggal->format('d - m - Y'),
                
                // Filter Makanan Utama
                'makanan_utama' => $log->makanan->where('type', 'utama')->values()->map(function($m) {
                    return [
                        'id' => $m->id, 
                        'nama' => $m->nama_makanan
                    ];
                }),

                // Filter Makanan Selingan
                'makanan_selingan' => $log->makanan->where('type', 'selingan')->values()->map(function($m) {
                    return [
                        'id' => $m->id, 
                        'nama' => $m->nama_makanan
                    ];
                }),

                // List Aktivitas
                'aktivitas_fisik' => $log->aktivitas->values()->map(function($a) {
                    return [
                        'id' => $a->id,
                        'nama' => $a->nama_aktivitas,
                        'durasi' => $a->durasi . ' menit'
                    ];
                }),
            ];
        });

        // 3. Susun Response Akhir
        return response()->json([
            'statusCode' => 200,
            'message'    => 'Detail Data Pasien Lengkap',
            'data' => [
                // Info Dasar Pasien
                'profil' => [
                    'id'            => $user->id,
                    'nama_lengkap'  => $user->nama_lengkap,
                    'nik'           => $user->nik,
                    'no_hp'         => $user->no_hp,
                    'jenis_kelamin' => $user->jenis_kelamin,
                    'alamat'        => $user->alamat,
                    'tanggal_lahir' => $user->tanggal_lahir->format('d-m-Y'),
                ],

                // Info Kesehatan Terakhir
                'monitoring_terakhir' => [
                    'td'  => $user->latestMonitorTd ? ($user->latestMonitorTd->sistolik . '/' . $user->latestMonitorTd->diastolik) : '-',
                    'gd'  => $user->latestMonitorGd ? $user->latestMonitorGd->gula_darah : '-',
                    'imt' => $user->latestMonitorImt ? $user->latestMonitorImt->imt : '-',
                ],

                // Riwayat Catatan Harian (Struktur Tabel)
                'catatan_harian' => $formattedCatatanHarian
            ]
        ]);
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['statusCode' => 404, 'message' => 'Pasien tidak ditemukan'], 404);
        }

        try {
            // 1. Hapus Data Dependensi Manual (Opsional tapi Aman)
            // Jika database Anda sudah set 'ON DELETE CASCADE', langkah ini otomatis dilakukan database.
            // Namun jika 'RESTRICT', Anda wajib menghapus manual relasinya di sini:
            
            $user->monitorTd()->delete();
            $user->monitorGd()->delete();
            $user->monitorImt()->delete();
            $user->obat()->delete(); 
            // $user->logHarian()->delete(); // Jika ada catatan harian

            // 2. Hapus User
            $user->delete();

            return response()->json([
                'statusCode' => 200,
                'message'    => 'Data pasien dan seluruh riwayatnya berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'statusCode' => 500,
                'message'    => 'Gagal menghapus pasien',
                'error'      => $e->getMessage()
            ], 500);
        }
    }
}
