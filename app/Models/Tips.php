<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tips extends Model
{
    use HasUuids, HasFactory;
    protected $table = 'tips';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id_indikator',
        'konten',
    ];

    public function indikator(): BelongsTo
    {
        return $this->belongsTo(Indikator::class, 'id_indikator', 'id');
    }

    public function gambarTips(): HasMany
    {
        return $this->hasMany(GambarTips::class, 'id_tips', 'id');
    }
}
