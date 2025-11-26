<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAktivitas extends Model
{
    use HasUuids;
    protected $table = 'log_aktivitas';
    protected $fillable = ['id_log_harian', 'nama_aktivitas', 'durasi', 'jam_aktivitas'];

    // TAMBAHAN: Relasi Balik ke Parent
    public function logHarian(): BelongsTo
    {
        return $this->belongsTo(LogHarian::class, 'id_log_harian', 'id');
    }
}
