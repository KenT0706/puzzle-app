<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Puzzle;
use App\Models\PlaySession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlaySessionController extends Controller
{
    // POST /api/puzzles/{code}/sessions -- start playing, solo or as a group host
    public function store(Request $request, Puzzle $puzzle)
    {
        abort_unless($puzzle->is_published, 404);

        $data = $request->validate([
            'mode' => 'required|in:individual,group',
            'name' => 'required|string|max:60',
        ]);

        $session = PlaySession::create([
            'puzzle_id' => $puzzle->id,
            'mode' => $data['mode'],
            'join_code' => $data['mode'] === 'group' ? $this->generateJoinCode() : null,
            'grid_state' => [],
            'status' => 'in_progress',
            'score' => 0,
            'solved_clues' => [],
            'started_at' => now(),
        ]);

        $participant = $session->participants()->create([
            'name' => $data['name'],
            'joined_at' => now(),
            'last_seen_at' => now(),
        ]);

        return response()->json($this->present($session, $participant->id), 201);
    }

    // POST /api/sessions/join -- join an existing group with its PIN (must belong to the puzzle you scanned)
    public function join(Request $request)
    {
        $data = $request->validate([
            'join_code' => 'required|string',
            'name' => 'required|string|max:60',
            'puzzle_code' => 'required|string',
        ]);

        $session = PlaySession::where('join_code', strtoupper($data['join_code']))
            ->whereHas('puzzle', fn ($q) => $q->where('share_code', $data['puzzle_code']))
            ->firstOrFail();

        $participant = $session->participants()->create([
            'name' => $data['name'],
            'joined_at' => now(),
            'last_seen_at' => now(),
        ]);

        return response()->json($this->present($session, $participant->id), 201);
    }

    // GET /api/sessions/{session} -- poll current state (grid, score, participants); doubles as a heartbeat
    public function show(Request $request, PlaySession $session)
    {
        if ($pid = $request->query('participant')) {
            $session->participants()->where('id', $pid)->update(['last_seen_at' => now()]);
        }

        return response()->json($this->present($session));
    }

    // PATCH /api/sessions/{session}/cell -- set one cell's letter (shared grid for group mode)
    public function updateCell(Request $request, PlaySession $session)
    {
        $data = $request->validate([
            'row' => 'required|integer|min:0',
            'col' => 'required|integer|min:0',
            'letter' => 'nullable|string|max:1',
            'participant_id' => 'required|integer',
        ]);

        $grid = $session->grid_state ?? [];
        $key = "{$data['row']}-{$data['col']}";

        if (empty($data['letter'])) {
            unset($grid[$key]);
        } else {
            $grid[$key] = strtoupper($data['letter']);
        }

        $session->grid_state = $grid;
        $session->save();

        $session->participants()
            ->where('id', $data['participant_id'])
            ->update(['last_seen_at' => now()]);

        return response()->json($this->present($session));
    }

    // POST /api/sessions/{session}/check -- mark answers, award points for newly solved words
    public function check(PlaySession $session)
    {
        // Locked so two teammates pressing "Check" at once can't both collect the same points.
        return DB::transaction(function () use ($session) {
            $session = PlaySession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            $puzzle = $session->puzzle()->with('clues')->first();
            $grid = $session->grid_state ?? [];

            $correct = [];
            $allCorrect = true;
            $anyFilled = false;
            $solved = $session->solved_clues ?? [];
            $newlySolved = [];

            foreach ($puzzle->clues as $clue) {
                $wordRight = true;

                foreach ($clue->cellPath() as $i => $cell) {
                    $key = "{$cell['row']}-{$cell['col']}";
                    $expected = $clue->answer[$i];
                    $actual = $grid[$key] ?? null;

                    if ($actual !== null) {
                        $anyFilled = true;
                    }

                    $isRight = $actual === $expected;
                    $correct[$key] = ($correct[$key] ?? true) && $isRight;

                    if (!$isRight) {
                        $allCorrect = false;
                        $wordRight = false;
                    }
                }

                $id = "{$clue->direction}-{$clue->number}";
                if ($wordRight && !in_array($id, $solved, true)) {
                    $solved[] = $id;
                    $newlySolved[] = ['direction' => $clue->direction, 'number' => $clue->number];
                }
            }

            $points = count($newlySolved) * (int) config('scoring.points_per_word');
            $bonus = 0;

            $session->correct_cells = $correct;
            $session->solved_clues = $solved;

            if ($allCorrect && $anyFilled && $session->status !== 'completed') {
                $session->status = 'completed';
                $session->completed_at = now();
                $bonus = (int) config('scoring.completion_bonus');
            }

            $session->score = (int) $session->score + $points + $bonus;
            $session->save();

            return response()->json($this->present($session) + [
                'is_complete' => $session->status === 'completed',
                'points_awarded' => $points + $bonus,
                'completion_bonus' => $bonus,
                'newly_solved' => $newlySolved,
            ]);
        });
    }

    private function generateJoinCode(): string
    {
        do {
            $code = strtoupper(Str::random(5));
        } while (PlaySession::where('join_code', $code)->exists());

        return $code;
    }

    private function present(PlaySession $session, ?int $youAreParticipantId = null): array
    {
        return [
            'id' => $session->id,
            'puzzle_id' => $session->puzzle_id,
            'mode' => $session->mode,
            'join_code' => $session->join_code,
            'status' => $session->status,
            'score' => (int) $session->score,
            'grid_state' => $session->grid_state ?? [],
            'correct_cells' => $session->correct_cells ?? [],
            'participants' => $session->participants()->orderBy('joined_at')->get(['id', 'name']),
            'you' => $youAreParticipantId,
        ];
    }
}
