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
    // GET /api/puzzles -- admin-only. Every puzzle, with its share code and open/closed state.
    public function index()
    {
        return response()->json(
            Puzzle::orderByDesc('created_at')
                ->get(['id', 'share_code', 'title', 'category', 'rows', 'cols', 'is_published', 'created_at'])
        );
    }

    // GET /api/puzzles/{code} -- playable definition, answers stripped out
    public function show(Request $request, Puzzle $puzzle)
    {
        // Closed puzzles are only visible to the host (so they can preview them).
        if (!$puzzle->is_published && !$request->user('sanctum')?->is_admin) {
            abort(404);
        }

        $puzzle->load('clues');

        $built = GridBuilder::build(
            $puzzle->clues->map(fn ($c) => [
                'direction' => $c->direction,
                'start_row' => $c->start_row,
                'start_col' => $c->start_col,
                'answer' => $c->answer,
                'clue_text' => $c->clue_text,
                'number' => $c->number,
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
            'points_per_word' => (int) config('scoring.points_per_word'),
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
            'clues.*.number' => 'nullable|integer|min:1',
        ]);

        // Validate the whole layout (bounds, overlap conflicts, duplicate numbers) before saving anything.
        try {
            GridBuilder::build($data['clues'], $data['rows'], $data['cols']);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['clues' => $e->getMessage()]);
        }

        // Use the numbers you typed (if every clue has one), otherwise auto-number.
        $numbers = GridBuilder::assignNumbers($data['clues']);

        $puzzle = DB::transaction(function () use ($data, $numbers) {
            $puzzle = Puzzle::create([
                'title' => $data['title'],
                'category' => $data['category'] ?? null,
                'rows' => $data['rows'],
                'cols' => $data['cols'],
                'is_published' => $data['is_published'] ?? true,
            ]);

            foreach ($data['clues'] as $i => $c) {
                PuzzleClue::create([
                    'puzzle_id' => $puzzle->id,
                    'number' => $numbers[$i],
                    'direction' => $c['direction'],
                    'start_row' => $c['start_row'],
                    'start_col' => $c['start_col'],
                    'answer' => GridBuilder::cleanAnswer($c['answer']),
                    'clue_text' => $c['clue_text'],
                ]);
            }

            return $puzzle;
        });

        return response()->json(['id' => $puzzle->id, 'share_code' => $puzzle->share_code], 201);
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
                'number' => $c->number,
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
            'clues.*.number' => 'nullable|integer|min:1',
        ]);

        try {
            GridBuilder::build($data['clues'], $data['rows'], $data['cols']);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['clues' => $e->getMessage()]);
        }

        $numbers = GridBuilder::assignNumbers($data['clues']);

        DB::transaction(function () use ($puzzle, $data, $numbers) {
            $puzzle->update([
                'title' => $data['title'],
                'category' => $data['category'] ?? null,
                'rows' => $data['rows'],
                'cols' => $data['cols'],
                'is_published' => $data['is_published'] ?? $puzzle->is_published,
            ]);

            // Simplest safe approach: drop the old clue set and recreate it.
            $puzzle->clues()->delete();

            foreach ($data['clues'] as $i => $c) {
                PuzzleClue::create([
                    'puzzle_id' => $puzzle->id,
                    'number' => $numbers[$i],
                    'direction' => $c['direction'],
                    'start_row' => $c['start_row'],
                    'start_col' => $c['start_col'],
                    'answer' => GridBuilder::cleanAnswer($c['answer']),
                    'clue_text' => $c['clue_text'],
                ]);
            }
        });

        return response()->json(['id' => $puzzle->id]);
    }

    // PATCH /api/puzzles/{puzzle}/publish -- admin-only. Open or close a puzzle for players.
    public function setPublished(Request $request, Puzzle $puzzle)
    {
        $data = $request->validate(['is_published' => 'required|boolean']);
        $puzzle->update(['is_published' => $data['is_published']]);

        return response()->json(['id' => $puzzle->id, 'is_published' => $puzzle->is_published]);
    }

    // DELETE /api/puzzles/{puzzle} -- admin-only.
    public function destroy(Puzzle $puzzle)
    {
        $puzzle->delete();
        return response()->json(null, 204);
    }
}
