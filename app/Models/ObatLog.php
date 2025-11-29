<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ObatLog extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'obat_log';
    protected $fillable = [
        'id_obat',
        'jadwal_konsumsi',
        'status',
        'waktu_aktual_konsumsi',
    ];

    protected $casts = [
        'jadwal_konsumsi' => 'date',
        'waktu_aktual_konsumsi' => 'datetime',
    ];

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'id_obat', 'id');
    }
}
