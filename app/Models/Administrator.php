<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\PersonalAccessToken;

class Administrator extends Model
{
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
    public function tokens()
    {
        return $this->morphMany(PersonalAccessToken::class, 'tokeneable');
    }
}
