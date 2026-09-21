<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Puzzle;
use App\Models\PuzzleClue;
use App\Services\GridBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PuzzleController extends Controller
{
    // GET /api/puzzles  -- browse list (no answers)
    public function index(Request $request)
    {
        $puzzles = Puzzle::query()
            ->where('is_published', true)
            ->when($request->query('category'), fn ($q, $cat) => $q->where('category', $cat))
            ->orderByDesc('created_at')
            ->get(['id', 'title', 'category', 'rows', 'cols', 'created_at']);

        return response()->json($puzzles);
    }

    // GET /api/puzzles/{puzzle} -- playable definition, answers stripped out
    public function show(Puzzle $puzzle)
    {
        $puzzle->load('clues');

        $built = GridBuilder::build(
            $puzzle->clues->map(fn ($c) => [
                'direction' => $c->direction,
                'start_row' => $c->start_row,
                'start_col' => $c->start_col,
                'answer' => $c->answer,
                'clue_text' => $c->clue_text,
            ])->all(),
            $puzzle->rows,
            $puzzle->cols
        );

        $grid = GridBuilder::toPlayableGrid($built, $puzzle->rows, $puzzle->cols);

        $clues = [
            'across' => $puzzle->clues->where('direction', 'across')
                ->sortBy('number')
                ->map(fn ($c) => ['number' => $c->number, 'text' => $c->clue_text, 'length' => strlen($c->answer)])
                ->values(),
            'down' => $puzzle->clues->where('direction', 'down')
                ->sortBy('number')
                ->map(fn ($c) => ['number' => $c->number, 'text' => $c->clue_text, 'length' => strlen($c->answer)])
                ->values(),
        ];

        return response()->json([
            'id' => $puzzle->id,
            'title' => $puzzle->title,
            'category' => $puzzle->category,
            'rows' => $puzzle->rows,
            'cols' => $puzzle->cols,
            'grid' => $grid,
            'clues' => $clues,
        ]);
    }

    // POST /api/puzzles -- create a puzzle (admin / puzzle-builder feature)
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'rows' => 'required|integer|min:2|max:40',
            'cols' => 'required|integer|min:2|max:40',
            'is_published' => 'boolean',
            'clues' => 'required|array|min:1',
            'clues.*.direction' => 'required|in:across,down',
            'clues.*.start_row' => 'required|integer|min:0',
            'clues.*.start_col' => 'required|integer|min:0',
            'clues.*.answer' => 'required|string|min:1|max:64',
            'clues.*.clue_text' => 'required|string',
        ]);

        // Validate the whole layout (checks bounds + overlap conflicts) before saving anything.
        try {
            $built = GridBuilder::build($data['clues'], $data['rows'], $data['cols']);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['clues' => $e->getMessage()]);
        }

        // Re-derive numbers so each clue gets the correct shared number.
        $startCells = collect($data['clues'])
            ->map(fn ($c) => ['row' => $c['start_row'], 'col' => $c['start_col']])
            ->unique(fn ($c) => "{$c['row']}-{$c['col']}")
            ->sortBy([['row', 'asc'], ['col', 'asc']])
            ->values();

        $numberFor = [];
        $next = 1;
        foreach ($startCells as $cell) {
            $numberFor["{$cell['row']}-{$cell['col']}"] = $next++;
        }

        $puzzle = DB::transaction(function () use ($data, $numberFor) {
            $puzzle = Puzzle::create([
                'title' => $data['title'],
                'category' => $data['category'] ?? null,
                'rows' => $data['rows'],
                'cols' => $data['cols'],
                'is_published' => $data['is_published'] ?? true,
            ]);

            foreach ($data['clues'] as $c) {
                PuzzleClue::create([
                    'puzzle_id' => $puzzle->id,
                    'number' => $numberFor["{$c['start_row']}-{$c['start_col']}"],
                    'direction' => $c['direction'],
                    'start_row' => $c['start_row'],
                    'start_col' => $c['start_col'],
                    'answer' => strtoupper(preg_replace('/[^A-Za-z]/', '', $c['answer'])),
                    'clue_text' => $c['clue_text'],
                ]);
            }

            return $puzzle;
        });

        return response()->json(['id' => $puzzle->id], 201);
    }

    // GET /api/puzzles/{puzzle}/edit -- admin-only. Full definition, answers included, for editing.
    public function edit(Puzzle $puzzle)
    {
        $puzzle->load('clues');

        return response()->json([
            'id' => $puzzle->id,
            'title' => $puzzle->title,
            'category' => $puzzle->category,
            'rows' => $puzzle->rows,
            'cols' => $puzzle->cols,
            'is_published' => $puzzle->is_published,
            'clues' => $puzzle->clues->map(fn ($c) => [
                'direction' => $c->direction,
                'start_row' => $c->start_row,
                'start_col' => $c->start_col,
                'answer' => $c->answer,
                'clue_text' => $c->clue_text,
            ]),
        ]);
    }

    // PUT /api/puzzles/{puzzle} -- admin-only. Replaces the puzzle's fields + its whole clue list.
    public function update(Request $request, Puzzle $puzzle)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'rows' => 'required|integer|min:2|max:40',
            'cols' => 'required|integer|min:2|max:40',
            'is_published' => 'boolean',
            'clues' => 'required|array|min:1',
            'clues.*.direction' => 'required|in:across,down',
            'clues.*.start_row' => 'required|integer|min:0',
            'clues.*.start_col' => 'required|integer|min:0',
            'clues.*.answer' => 'required|string|min:1|max:64',
            'clues.*.clue_text' => 'required|string',
        ]);

        try {
            GridBuilder::build($data['clues'], $data['rows'], $data['cols']);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['clues' => $e->getMessage()]);
        }

        $startCells = collect($data['clues'])
            ->map(fn ($c) => ['row' => $c['start_row'], 'col' => $c['start_col']])
            ->unique(fn ($c) => "{$c['row']}-{$c['col']}")
            ->sortBy([['row', 'asc'], ['col', 'asc']])
            ->values();

        $numberFor = [];
        $next = 1;
        foreach ($startCells as $cell) {
            $numberFor["{$cell['row']}-{$cell['col']}"] = $next++;
        }

        DB::transaction(function () use ($puzzle, $data, $numberFor) {
            $puzzle->update([
                'title' => $data['title'],
                'category' => $data['category'] ?? null,
                'rows' => $data['rows'],
                'cols' => $data['cols'],
                'is_published' => $data['is_published'] ?? $puzzle->is_published,
            ]);

            // Simplest safe approach: drop the old clue set and recreate it.
            // Any in-progress play_sessions for this puzzle keep their grid_state,
            // but re-checking against the edited answers is what you want anyway.
            $puzzle->clues()->delete();

            foreach ($data['clues'] as $c) {
                PuzzleClue::create([
                    'puzzle_id' => $puzzle->id,
                    'number' => $numberFor["{$c['start_row']}-{$c['start_col']}"],
                    'direction' => $c['direction'],
                    'start_row' => $c['start_row'],
                    'start_col' => $c['start_col'],
                    'answer' => strtoupper(preg_replace('/[^A-Za-z]/', '', $c['answer'])),
                    'clue_text' => $c['clue_text'],
                ]);
            }
        });

        return response()->json(['id' => $puzzle->id]);
    }

    // DELETE /api/puzzles/{puzzle} -- admin-only.
    public function destroy(Puzzle $puzzle)
    {
        $puzzle->delete();
        return response()->json(null, 204);
    }
}
