<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Indikator extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'indikator';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'type',
        'status',
        'range',
        'color_hex',
    ];
    protected $casts = [
        'range' => 'array',
    ];

    public function tips(): HasMany
    {
        return $this->hasMany(Tips::class, 'id_indikator', 'id');
    }
}
