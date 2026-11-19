<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Puzzle extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'category', 'rows', 'cols', 'is_published', 'round_started_at'];

    protected $casts = [
        'is_published' => 'boolean',
        'round_started_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Every puzzle gets a private, unguessable code used in its QR link (/p/<code>).
        static::creating(function (Puzzle $puzzle) {
            $puzzle->share_code ??= Str::lower(Str::random(10));
        });
    }

    public function clues(): HasMany
    {
        return $this->hasMany(PuzzleClue::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(PlaySession::class);
    }
}
