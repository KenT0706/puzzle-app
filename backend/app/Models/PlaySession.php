<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlaySession extends Model
{
    use HasFactory;

    protected $fillable = [
        'puzzle_id', 'mode', 'join_code', 'grid_state', 'correct_cells',
        'status', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'grid_state' => 'array',
        'correct_cells' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function puzzle(): BelongsTo
    {
        return $this->belongsTo(Puzzle::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(PlaySessionParticipant::class);
    }
}
