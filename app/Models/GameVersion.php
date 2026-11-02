<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GameVersion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'game_id',
        'version',
        'storage_path'
    ];

    public function game()
    {
        return $this->belongsTo(Game::class, 'game_id');
    }
    public function scores()
    {
        return $this->hasMany(Score::class, 'game_version_id');
    }
}
