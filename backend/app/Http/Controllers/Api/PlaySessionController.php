<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Puzzle;
use App\Models\PlaySession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlaySessionController extends Controller
{
    // POST /api/puzzles/{puzzle}/sessions -- start playing, solo or as a group host
    public function store(Request $request, Puzzle $puzzle)
    {
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
            'started_at' => now(),
        ]);

        $participant = $session->participants()->create([
            'name' => $data['name'],
            'joined_at' => now(),
            'last_seen_at' => now(),
        ]);

        return response()->json($this->present($session, $participant->id), 201);
    }

    // POST /api/sessions/join -- join an existing group session with a join code
    public function join(Request $request)
    {
        $data = $request->validate([
            'join_code' => 'required|string',
            'name' => 'required|string|max:60',
        ]);

        $session = PlaySession::where('join_code', strtoupper($data['join_code']))->firstOrFail();

        $participant = $session->participants()->create([
            'name' => $data['name'],
            'joined_at' => now(),
            'last_seen_at' => now(),
        ]);

        return response()->json($this->present($session, $participant->id), 201);
    }

    // GET /api/sessions/{session} -- poll current state (grid + participants), used by group mode
    public function show(PlaySession $session)
    {
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

    // POST /api/sessions/{session}/check -- validate answers, mark complete if all correct
    public function check(PlaySession $session)
    {
        $puzzle = $session->puzzle()->with('clues')->first();
        $grid = $session->grid_state ?? [];

        $correct = [];
        $allCorrect = true;
        $anyFilled = false;

        foreach ($puzzle->clues as $clue) {
            foreach ($clue->cellPath() as $i => $cell) {
                $key = "{$cell['row']}-{$cell['col']}";
                $expected = $clue->answer[$i];
                $actual = $grid[$key] ?? null;

                if ($actual !== null) {
                    $anyFilled = true;
                }

                $isRight = $actual === $expected;
                // If a cell is shared between two clues, only mark it wrong once both agree it's wrong.
                $correct[$key] = ($correct[$key] ?? true) && $isRight;

                if (!$isRight) {
                    $allCorrect = false;
                }
            }
        }

        $session->correct_cells = $correct;

        if ($allCorrect && $anyFilled) {
            $session->status = 'completed';
            $session->completed_at = now();
        }

        $session->save();

        return response()->json($this->present($session) + [
            'is_complete' => $session->status === 'completed',
        ]);
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
            'grid_state' => $session->grid_state ?? [],
            'correct_cells' => $session->correct_cells ?? [],
            'participants' => $session->participants()->orderBy('joined_at')->get(['id', 'name']),
            'you' => $youAreParticipantId,
        ];
    }
}
