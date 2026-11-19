<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlaySession;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // GET /api/dashboard?puzzle_id=&hours= -- admin-only. Live view of recent play sessions.
    public function index(Request $request)
    {
        $data = $request->validate([
            'puzzle_id' => 'nullable|integer',
            'hours' => 'nullable|integer|min:1|max:168',
        ]);

        $since = now()->subHours($data['hours'] ?? 12);
        $activeSince = now()->subMinutes(2);

        $sessions = PlaySession::query()
            ->with(['puzzle.clues', 'participants'])
            ->where('updated_at', '>=', $since)
            ->when($data['puzzle_id'] ?? null, fn ($q, $id) => $q->where('puzzle_id', $id))
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get();

        $totals = []; // puzzle_id => number of answer cells

        $rows = $sessions->map(function (PlaySession $s) use (&$totals, $activeSince) {
            $pid = $s->puzzle_id;
            if (!isset($totals[$pid])) {
                $cells = [];
                foreach ($s->puzzle->clues as $clue) {
                    foreach ($clue->cellPath() as $cell) {
                        $cells["{$cell['row']}-{$cell['col']}"] = true;
                    }
                }
                $totals[$pid] = count($cells);
            }

            $filled = collect($s->grid_state ?? [])->filter(fn ($v) => $v !== null && $v !== '')->count();

            return [
                'id' => $s->id,
                'puzzle_id' => $pid,
                'puzzle_title' => $s->puzzle->title,
                'mode' => $s->mode,
                'join_code' => $s->join_code,
                'status' => $s->status,
                'score' => (int) $s->score,
                'started_at' => $s->started_at,
                'updated_at' => $s->updated_at,
                'filled' => $filled,
                'total' => $totals[$pid],
                'participants' => $s->participants->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'joined_at' => $p->joined_at,
                    'last_seen_at' => $p->last_seen_at,
                    'active' => $p->last_seen_at && $p->last_seen_at->gte($activeSince),
                ])->values(),
            ];
        })->values();

        $online = $rows->sum(fn ($r) => collect($r['participants'])->where('active', true)->count());

        return response()->json(['online_now' => $online, 'sessions' => $rows]);
    }
}
