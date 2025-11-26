<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitorTd extends Model
{
    use HasFactory, HasUuids;
    protected $table = 'monitor_td';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id_user',
        'tanggal',
        'sistolik',
        'diastolik',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }
}
