<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Puzzle extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'category', 'rows', 'cols', 'is_published'];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function clues(): HasMany
    {
        return $this->hasMany(PuzzleClue::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PlaySession::class);
    }
}
