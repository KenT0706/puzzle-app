<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlaySession;
use App\Models\Puzzle;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    // GET /api/puzzles/{code}/leaderboard -- public: names and scores only, never answers.
    public function show(Request $request, Puzzle $puzzle)
    {
        if (!$puzzle->is_published && !$request->user('sanctum')?->is_admin) {
            abort(404);
        }

        $sessions = PlaySession::query()
            ->with('participants')
            ->where('puzzle_id', $puzzle->id)
            ->when($puzzle->round_started_at, fn ($q, $since) => $q->where('started_at', '>=', $since))
            ->get();

        $rows = $sessions->map(function (PlaySession $s) {
            $names = $s->participants->pluck('name')->values();
            $first = $names->first() ?? 'Player';

            return [
                'session_id' => $s->id,
                'name' => $s->mode === 'group'
                    ? $first . '’s team' . ($names->count() > 1 ? ' (' . $names->count() . ')' : '')
                    : $first,
                'mode' => $s->mode,
                'score' => (int) $s->score,
                'solved' => count($s->solved_clues ?? []),
                'completed' => $s->status === 'completed',
                'finished_ts' => $s->completed_at?->timestamp,
            ];
        })->all();

        // Highest score first; if tied, whoever finished the puzzle first; then who joined first.
        usort($rows, function ($a, $b) {
            if ($a['score'] !== $b['score']) {
                return $b['score'] <=> $a['score'];
            }
            $ta = $a['finished_ts'] ?? PHP_INT_MAX;
            $tb = $b['finished_ts'] ?? PHP_INT_MAX;
            if ($ta !== $tb) {
                return $ta <=> $tb;
            }
            return $a['session_id'] <=> $b['session_id'];
        });

        foreach ($rows as $i => &$row) {
            $row['rank'] = $i + 1;
            unset($row['finished_ts']);
        }
        unset($row);

        return response()->json([
            'puzzle' => ['title' => $puzzle->title, 'category' => $puzzle->category],
            'total_words' => $puzzle->clues()->count(),
            'points_per_word' => (int) config('scoring.points_per_word'),
            'entries' => $rows,
        ]);
    }

    // PATCH /api/puzzles/{code}/new-round -- admin-only. Clears the board for the next group.
    public function newRound(Puzzle $puzzle)
    {
        $puzzle->update(['round_started_at' => now()]);

        return response()->json(['round_started_at' => $puzzle->round_started_at]);
    }
}
