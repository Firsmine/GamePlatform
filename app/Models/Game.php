<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Game extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'created_by'
    ];
    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function version()
    {
        return $this->hasMany(GameVersion::class, 'game_id');
    }
}
