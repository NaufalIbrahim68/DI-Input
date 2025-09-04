<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // Tentukan primary key yang sesuai tabel
    protected $primaryKey = 'id_user';

    // Jika primary key bukan auto-increment, uncomment ini:
    // public $incrementing = false;
    // protected $keyType = 'int'; // atau 'string' sesuai tipe

    protected $fillable = [
        'name',
        'npk',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    /**
     * Override kolom identifier untuk Auth
     * supaya login pakai 'npk' bukan 'id'
     */
   public function username()
{
    return 'npk';
}
}
