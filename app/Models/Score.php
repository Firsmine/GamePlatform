<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Score extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'game_version_id',
        'score'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function gameVersion()
    {
        return $this->belongsTo(GameVersion::class, 'game_version_id');
    }
}
