<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nama_lengkap',
        'tanggal_lahir',
        'tempat_lahir',
        'jenis_kelamin',
        'alamat',
        'nik',
        'no_hp',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date:d-m-Y',
        ];
    }

    public function monitorGd(): HasMany
    {
        return $this->hasMany(MonitorGd::class, 'id_user', 'id');
    }
    public function monitorTd(): HasMany
    {
        return $this->hasMany(MonitorTd::class, 'id_user', 'id');
    }
    public function monitorImt(): HasMany
    {
        return $this->hasMany(MonitorImt::class, 'id_user', 'id');
    }

    public function obat(): HasMany
    {
        return $this->hasMany(Obat::class, 'id_user', 'id')->orderBy('created_at', 'desc');
    }

    public function logHarian(): HasMany
    {
        return $this->hasMany(LogHarian::class, 'id_user', 'id')->orderBy('tanggal', 'desc');
    }

    //Eager Load

    public function latestMonitorGd(): HasOne
    {
        // latestOfMany() otomatis mengurutkan berdasarkan ID/created_at desc
        return $this->hasOne(MonitorGd::class, 'id_user', 'id')->ofMany('created_at', 'max');
    }

    public function latestMonitorTd(): HasOne
    {
        return $this->hasOne(MonitorTd::class, 'id_user', 'id')->ofMany('created_at', 'max');
    }

    public function latestMonitorImt(): HasOne
    {
        return $this->hasOne(MonitorImt::class, 'id_user', 'id')->ofMany('created_at', 'max');
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
