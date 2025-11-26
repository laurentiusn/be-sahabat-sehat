<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogHarian extends Model
{
    use HasUuids;
    protected $table = 'log_harian';
    protected $fillable = ['id_user', 'tanggal'];

    protected $casts = [
        'tanggal' => 'date:d-m-Y'
    ];

    public function makanan(): HasMany
    {
        return $this->hasMany(LogMakanan::class, 'id_log_harian', 'id');
    }

    public function aktivitas(): HasMany
    {
        return $this->hasMany(LogAktivitas::class, 'id_log_harian', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }
}
