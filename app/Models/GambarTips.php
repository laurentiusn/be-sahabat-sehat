<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GambarTips extends Model
{
    use HasUuids, HasFactory;
    protected $table = 'gambar_tips';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id_tips',
        'path_gambar',
    ];

    public function tips(): BelongsTo
    {
        return $this->belongsTo(Tips::class, 'id_tips', 'id');
    }
}
