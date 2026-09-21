<?php

namespace App\Services;

use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Builds a crossword grid (and cell numbering) from a set of clue placements,
 * and validates that overlapping answers agree on shared letters.
 *
 * A "clue placement" is an array with keys:
 *   direction ('across'|'down'), start_row, start_col, answer, clue_text
 */
class GridBuilder
{
    /**
     * @return array{
     *   letters: array<string, string>,      // "r-c" => letter, only active cells
     *   numbers: array<string, int>,          // "r-c" => clue number, only start cells
     *   numbered: array<int, array{row:int,col:int,direction:string}> // clue index => its number
     * }
     */
    public static function build(array $placements, int $rows, int $cols): array
    {
        $letters = []; // "r-c" => letter
        $conflicts = [];

        foreach ($placements as $i => $p) {
            $answer = strtoupper(preg_replace('/[^A-Za-z]/', '', $p['answer']));
            if ($answer === '') {
                throw new InvalidArgumentException("Clue #{$i} has an empty answer.");
            }

            $len = strlen($answer);
            for ($k = 0; $k < $len; $k++) {
                $row = $p['start_row'] + ($p['direction'] === 'down' ? $k : 0);
                $col = $p['start_col'] + ($p['direction'] === 'across' ? $k : 0);

                if ($row >= $rows || $col >= $cols || $row < 0 || $col < 0) {
                    throw new InvalidArgumentException(
                        "Clue #{$i} ('{$p['clue_text']}') runs outside the {$rows}x{$cols} grid."
                    );
                }

                $key = "{$row}-{$col}";
                $letter = $answer[$k];

                if (isset($letters[$key]) && $letters[$key] !== $letter) {
                    $conflicts[] = "Clue #{$i} conflicts with another word at row {$row}, col {$col} "
                        . "('{$letters[$key]}' vs '{$letter}').";
                    continue;
                }
                $letters[$key] = $letter;
            }
        }

        if (!empty($conflicts)) {
            throw new InvalidArgumentException(implode(' ', $conflicts));
        }

        // Assign clue numbers: every distinct start cell (in reading order) gets the next number.
        // A cell shared by an across-start and a down-start gets a single number used by both.
        $startCells = collect($placements)
            ->map(fn ($p) => ['row' => $p['start_row'], 'col' => $p['start_col']])
            ->unique(fn ($c) => "{$c['row']}-{$c['col']}")
            ->sortBy([['row', 'asc'], ['col', 'asc']])
            ->values();

        $numberFor = []; // "r-c" => number
        $next = 1;
        foreach ($startCells as $cell) {
            $numberFor["{$cell['row']}-{$cell['col']}"] = $next++;
        }

        $numbers = $numberFor;

        return [
            'letters' => $letters,
            'numbers' => $numbers,
        ];
    }

    /**
     * Build the client-facing grid (no answers) for playing the puzzle:
     * a rows x cols matrix of cells, each either blocked or { number, row, col }.
     */
    public static function toPlayableGrid(array $built, int $rows, int $cols): array
    {
        $grid = [];
        for ($r = 0; $r < $rows; $r++) {
            $rowCells = [];
            for ($c = 0; $c < $cols; $c++) {
                $key = "{$r}-{$c}";
                $active = array_key_exists($key, $built['letters']);
                $rowCells[] = [
                    'row' => $r,
                    'col' => $c,
                    'blocked' => !$active,
                    'number' => $built['numbers'][$key] ?? null,
                ];
            }
            $grid[] = $rowCells;
        }
        return $grid;
    }
}
