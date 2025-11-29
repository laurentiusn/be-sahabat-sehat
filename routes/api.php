<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\IndikatorController;
use App\Http\Controllers\Admin\ObatController;
use App\Http\Controllers\Admin\TipsController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Pasien\CatatanHarianController;
use App\Http\Controllers\Pasien\MonitorGdController;
use App\Http\Controllers\Pasien\MonitorImtController;
use App\Http\Controllers\Pasien\MonitorTdController;
use App\Http\Controllers\Pasien\PasienController;
use App\Http\Middleware\Admin;
use App\Http\Middleware\Pasien;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::middleware(Admin::class)->group(function () {
            //Dashboard
            Route::get('/dashboard-admin', [AdminController::class, 'index']);
            Route::post('/pasien', [AdminController::class, 'store']);
            Route::get('/pasien/{id}', [AdminController::class, 'show']);
            Route::delete('/pasien/{id}', [AdminController::class, 'destroy']);

            //Ubah Indikator
            Route::get('/indikator', [IndikatorController::class, 'index']);
            Route::post('/indikator', [IndikatorController::class, 'store']);
            Route::get('/indikator/{id}', [IndikatorController::class, 'show']);
            Route::put('/indikator/{id}', [IndikatorController::class, 'update']);
            Route::delete('/indikator/{id}', [IndikatorController::class, 'destroy']);

            //Tips
            Route::get('/tips', [TipsController::class, 'index']);
            // Route::post('/tips', [TipsController::class, 'store']);
            Route::get('/tips/{id}', [TipsController::class, 'show']);
            Route::put('/tips/{id}', [TipsController::class, 'update']);
            Route::patch('/tips/{id}', [TipsController::class, 'update']);
            // Route::delete('/tips/{id}', [TipsController::class, 'destroy']);

            
            //Lihat Monitoring User
            Route::get('/monitor-td/{id}', [MonitorTdController::class, 'show']);
            Route::get('/monitor-gd/{id}', [MonitorGdController::class, 'show']);
            Route::get('/monitor-imt/{id}', [MonitorImtController::class, 'show']);

            //Obat Management
            Route::get('/riwayat-obat', [ObatController::class, 'index']);
            Route::post('/obat', [ObatController::class, 'store']);
            Route::get('/daftar-obat', [ObatController::class, 'listMasterObat']);
            Route::post('/obat/{id}/stop', [ObatController::class, 'stop']);
            Route::delete('/obat/{id}', [ObatController::class, 'destroy']);
        });

        Route::middleware(Pasien::class)->group(function () {
            Route::get('/dashboard-pas', [PasienController::class, 'dashboard']);

            //Monitoring Pasien
            //Latest & History
            Route::get('/monitor-td', [MonitorTdController::class, 'index']);
            Route::get('/monitor-gd', [MonitorGdController::class, 'index']);
            Route::get('/monitor-imt', [MonitorImtController::class, 'index']);
            //Store New Monitoring Data
            Route::post('/monitor-td', [MonitorTdController::class, 'store']);
            Route::post('/monitor-gd', [MonitorGdController::class, 'store']);
            Route::post('/monitor-imt', [MonitorImtController::class, 'store']);

            //Obat Pasien
            Route::get('/obat-hari-ini', [PasienController::class, 'listObat']);
            Route::put('/checklist-obat/{id}', [PasienController::class, 'checklist']);
            Route::get('/tips-hidup', [PasienController::class, 'tipsHidup']);

            //Catatn Harian
            Route::get('/catatan-harian', [CatatanHarianController::class, 'index']);
            Route::post('/catatan-harian/makanan', [CatatanHarianController::class, 'storeMakanan']);
            Route::post('/catatan-harian/aktivitas', [CatatanHarianController::class, 'storeAktivitas']);
            Route::delete('/catatan-harian/makanan/{id}', [CatatanHarianController::class, 'destroyMakanan']);
            Route::delete('/catatan-harian/aktivitas/{id}', [CatatanHarianController::class, 'destroyAktivitas']);
        });

    });
});
