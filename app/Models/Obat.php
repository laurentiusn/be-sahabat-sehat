<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Obat extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'obat';
    protected $fillable = [
        'id_user',
        'nama_obat',
        'dosis',
        'start_date',
        'end_date',
        'waktu',
        'keterangan',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ObatLog::class, 'id_obat', 'id')->orderBy('jadwal_konsumsi', 'asc');
    }
}
