<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogMakanan extends Model
{
    use HasUuids;
    protected $table = 'log_makanan';
    protected $fillable = ['id_log_harian', 'nama_makanan', 'type', 'jam_makan'];

    public function logHarian(): BelongsTo
    {
        return $this->belongsTo(LogHarian::class, 'id_log_harian', 'id');
    }
}
