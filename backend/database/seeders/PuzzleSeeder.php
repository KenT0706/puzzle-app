<?php

namespace Database\Seeders;

use App\Models\Puzzle;
use App\Models\PuzzleClue;
use Illuminate\Database\Seeder;

/**
 * A small illustrative example so you can see the data shape end-to-end.
 * Swap this out for your real puzzles via the "New Puzzle" screen, or by
 * POSTing the same JSON shape to /api/puzzles.
 */
class PuzzleSeeder extends Seeder
{
    public function run(): void
    {
        $puzzle = Puzzle::create([
            'title' => 'Payroll Basics',
            'category' => 'Payroll',
            'rows' => 8,
            'cols' => 8,
            'is_published' => true,
        ]);

        // "OVERTIME" runs down, "WAGES" runs across through its 3rd letter (E).
        PuzzleClue::create([
            'puzzle_id' => $puzzle->id,
            'number' => 1,
            'direction' => 'down',
            'start_row' => 0,
            'start_col' => 2,
            'answer' => 'OVERTIME',
            'clue_text' => 'Hours worked in excess of normal daily hours.',
        ]);

        PuzzleClue::create([
            'puzzle_id' => $puzzle->id,
            'number' => 2,
            'direction' => 'across',
            'start_row' => 5,
            'start_col' => 3,
            'answer' => 'WAGES',
            'clue_text' => 'What an employee is paid for their work.',
        ]);
    }
}
