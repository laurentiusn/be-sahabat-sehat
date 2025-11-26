<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitorImt extends Model
{
    use HasFactory, HasUuids;
    protected $table = 'monitor_imt';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id_user',
        'tanggal',
        'tinggi_badan',
        'massa_badan',
        'imt',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }
}
