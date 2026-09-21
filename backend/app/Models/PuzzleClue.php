<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PuzzleClue extends Model
{
    use HasFactory;

    protected $fillable = [
        'puzzle_id', 'number', 'direction', 'start_row', 'start_col', 'answer', 'clue_text',
    ];

    public function puzzle(): BelongsTo
    {
        return $this->belongsTo(Puzzle::class);
    }

    /**
     * Coordinates of every cell this clue occupies, in order.
     * @return array<int, array{row:int col:int}>
     */
    public function cellPath(): array
    {
        $path = [];
        $length = strlen($this->answer);
        for ($i = 0; $i < $length; $i++) {
            $path[] = [
                'row' => $this->start_row + ($this->direction === 'down' ? $i : 0),
                'col' => $this->start_col + ($this->direction === 'across' ? $i : 0),
            ];
        }
        return $path;
    }
}
