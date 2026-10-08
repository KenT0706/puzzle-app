<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Builds a crossword grid (and cell numbering) from a set of clue placements,
 * and validates that overlapping answers agree on shared letters.
 *
 * A "clue placement" is an array with keys:
 *   direction ('across'|'down'), start_row, start_col, answer, clue_text,
 *   number (optional — see assignNumbers())
 *
 * Answers may contain letters and hyphens (e.g. OPEN-ENDED); a hyphen
 * occupies its own cell.
 */
class GridBuilder
{
    public static function cleanAnswer(string $answer): string
    {
        return strtoupper(preg_replace('/[^A-Za-z-]/', '', $answer));
    }

    /**
     * Clue numbers, in the same order as $placements.
     *
     * - If EVERY placement has a `number`, those are used as-is (numbers are
     *   per direction, like "Across 1 / Down 1" in a printed puzzle). Two clues
     *   in the same direction may not share a number.
     * - Otherwise numbers are auto-assigned: one shared number per start cell,
     *   in reading order (classic crossword numbering).
     *
     * @return int[]
     */
    public static function assignNumbers(array $placements): array
    {
        $placements = array_values($placements);

        $allExplicit = count($placements) > 0 && collect($placements)->every(
            fn ($p) => isset($p['number']) && $p['number'] !== '' && $p['number'] !== null
        );

        if ($allExplicit) {
            $seen = [];
            foreach ($placements as $p) {
                $k = $p['direction'] . '-' . (int) $p['number'];
                if (isset($seen[$k])) {
                    throw new InvalidArgumentException(
                        "Two {$p['direction']} clues are both numbered {$p['number']}."
                    );
                }
                $seen[$k] = true;
            }
            return array_map(fn ($p) => (int) $p['number'], $placements);
        }

        $startCells = collect($placements)
            ->map(fn ($p) => ['row' => $p['start_row'], 'col' => $p['start_col']])
            ->unique(fn ($c) => "{$c['row']}-{$c['col']}")
            ->sortBy([['row', 'asc'], ['col', 'asc']])
            ->values();

        $numberFor = [];
        $next = 1;
        foreach ($startCells as $cell) {
            $numberFor["{$cell['row']}-{$cell['col']}"] = $next++;
        }

        return array_map(fn ($p) => $numberFor["{$p['start_row']}-{$p['start_col']}"], $placements);
    }

    /**
     * @return array{
     *   letters: array<string, string>,   // "r-c" => letter, only active cells
     *   labels: array<string, array<int, array{number:int,direction:string}>> // "r-c" => numbers shown in that cell
     * }
     */
    public static function build(array $placements, int $rows, int $cols): array
    {
        $placements = array_values($placements);
        $letters = [];
        $conflicts = [];

        foreach ($placements as $i => $p) {
            $answer = self::cleanAnswer($p['answer']);
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

        $numbers = self::assignNumbers($placements);

        $labels = [];
        foreach ($placements as $i => $p) {
            $key = "{$p['start_row']}-{$p['start_col']}";
            $labels[$key][] = ['number' => $numbers[$i], 'direction' => $p['direction']];
        }

        return ['letters' => $letters, 'labels' => $labels];
    }

    /**
     * Client-facing grid (no answers): rows x cols matrix of cells, each
     * either blocked or active, with the clue number(s) that start there.
     */
    public static function toPlayableGrid(array $built, int $rows, int $cols): array
    {
        $grid = [];
        for ($r = 0; $r < $rows; $r++) {
            $rowCells = [];
            for ($c = 0; $c < $cols; $c++) {
                $key = "{$r}-{$c}";
                $rowCells[] = [
                    'row' => $r,
                    'col' => $c,
                    'blocked' => !array_key_exists($key, $built['letters']),
                    'labels' => $built['labels'][$key] ?? [],
                ];
            }
            $grid[] = $rowCells;
        }
        return $grid;
    }
}