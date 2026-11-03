<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

class Administrator extends Authenticatable
{
    use HasFactory, Notifiable;
    use HasApiTokens;

    protected $fillable = [
        'username',
        'password',
        'last_login_at'
    ];
    protected $hidden = ['password'];

    public function casts()
    {
        return ['password' => 'hashed', 'last_login_at' => 'datetime'];
    }
    // public function tokens()
    // {
    //     return $this->morphMany(PersonalAccessToken::class, 'tokenable');
    // }
}
