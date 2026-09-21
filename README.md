# Payroll Puzzle App

A fullstack crossword app for HR/payroll training quizzes (Payroll, Employment
Act, Termination/IR, Talent Management, etc. — matching the categories in
your slide deck). Laravel API backend + React (Vite) frontend.

## What's included

- **Admin-only puzzle management** — creating, editing, and deleting puzzles
  (and their clues/questions) requires an admin login. Everyone else can
  browse and play, but the "+ New Puzzle" link, Edit, and Delete only show
  up once you're signed in as an admin. Enforced on the backend too (not
  just hidden in the UI) via Sanctum tokens + an `admin` middleware check.
- **Puzzle creation & editing** — a form to build a crossword by giving each
  word's direction, starting row/col, answer, and clue text; the same form
  is reused for editing. The server auto-numbers the grid and rejects
  overlaps where two words disagree on a shared letter.
- **Play modes** — solo, or a group: one person "starts a group" and gets a
  join code; teammates enter that code to join and fill in a **shared** grid
  together (polled every ~1.8s — good enough for a training-room activity;
  see "Real-time upgrade" below if you want it instant).
- **Checking + celebration** — a "Check answers" button validates against the
  server (answers are never sent to the browser). Once every cell is correct,
  the frontend fires a confetti burst and a short synthesized success chime
  (`src/components/Celebration.jsx` — no external sound file needed).

## Project layout

```
backend/    Laravel app pieces to drop into a fresh Laravel install
  app/Models/                Puzzle, PuzzleClue, PlaySession, PlaySessionParticipant, User
  app/Services/GridBuilder.php   turns clue placements into a numbered grid + validates overlaps
  app/Http/Controllers/Api/  PuzzleController, PlaySessionController, AuthController
  app/Http/Middleware/EnsureIsAdmin.php   blocks non-admins from create/edit/delete
  database/migrations/
  database/seeders/PuzzleSeeder.php       one small example puzzle
  database/seeders/AdminUserSeeder.php    creates the one admin login
  routes/api.php

frontend/   React (Vite) SPA
  src/pages/       PuzzleList, PuzzleCreate (create + edit), PuzzlePlay, Login
  src/components/  CrosswordGrid, Celebration (confetti + sound)
  src/auth/        AuthContext (token + is_admin state), RequireAdmin (route guard)
```

## Backend setup

```bash
composer create-project laravel/laravel puzzle-backend
cd puzzle-backend

# copy this repo's backend/ files into place (they overlay a fresh install):
#   app/Models/*.php                -> app/Models/  (this replaces the default User.php)
#   app/Services/*.php              -> app/Services/
#   app/Http/Controllers/Api/*.php  -> app/Http/Controllers/Api/
#   app/Http/Middleware/*.php       -> app/Http/Middleware/
#   database/migrations/*.php       -> database/migrations/
#   database/seeders/*.php          -> database/seeders/
#   routes/api.php contents         -> replace/merge into your routes/api.php

composer require laravel/sanctum
php artisan install:api    # if this command exists in your Laravel version; otherwise:
                            #   php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

**Register the `admin` middleware alias** — where this goes depends on your
Laravel version:

- **Laravel 11+** (no `app/Http/Kernel.php`): in `bootstrap/app.php`, inside
  `->withMiddleware(function (Middleware $middleware) { ... })`, add:
  ```php
  $middleware->alias(['admin' => \App\Http\Middleware\EnsureIsAdmin::class]);
  ```
- **Laravel 10 and earlier**: in `app/Http/Kernel.php`, add to
  `$middlewareAliases`:
  ```php
  'admin' => \App\Http\Middleware\EnsureIsAdmin::class,
  ```

Then:

```bash
php artisan migrate

# Set an admin login (defaults to admin@example.com / change-me-now if unset):
echo "ADMIN_EMAIL=you@yourcompany.com" >> .env
echo "ADMIN_PASSWORD=pick-a-real-password" >> .env

php artisan db:seed --class=AdminUserSeeder
php artisan db:seed --class=PuzzleSeeder   # optional example puzzle

# Allow the frontend's origin in config/cors.php (`paths`, `allowed_origins`),
# e.g. allowed_origins => ['http://localhost:5173']

php artisan serve   # http://localhost:8000
```

## Frontend setup

```bash
cd frontend
npm install
cp .env.example .env   # set VITE_API_URL if your backend isn't on localhost:8000
npm run dev            # http://localhost:5173
```

## Logging in as admin

Go to `/login` in the frontend (or click "Admin login" in the header) and
sign in with the `ADMIN_EMAIL` / `ADMIN_PASSWORD` you set above. Once
signed in you'll see "+ New Puzzle" in the header, and Edit/Delete on every
puzzle card. Everyone else — anyone who hasn't logged in — can still browse
and play puzzles, they just can't create, edit, or delete them (the backend
enforces this too, so it's not just a hidden button).

## Data model, in short

- `puzzles` — title, category, grid size.
- `puzzle_clues` — one row per word: direction, start row/col, the answer,
  and the clue text. The grid itself isn't stored directly — it's derived
  from these placements by `GridBuilder`, which also numbers the cells the
  way real crosswords do (a cell gets a number if a word starts there).
- `play_sessions` — one per solo game or group game. `grid_state` is a JSON
  map of `"row-col": "LETTER"` for whatever's been typed so far; for group
  mode every participant reads/writes the same session.
- `play_session_participants` — who's in a given session (used for the
  players list and join flow).

Answers are only ever compared server-side (`PlaySessionController::check`),
so the browser never receives the solution.

## Adding more puzzles

Either use the "+ New Puzzle" screen, or POST the same JSON shape straight to
`/api/puzzles` with an admin's bearer token (`Authorization: Bearer <token>`,
the token you get back from `POST /api/login`):

```json
{
  "title": "Employment Act Basics",
  "category": "Employment Act",
  "rows": 12,
  "cols": 12,
  "clues": [
    { "direction": "down", "start_row": 0, "start_col": 3, "answer": "CONFINEMENT", "clue_text": "..." },
    { "direction": "across", "start_row": 2, "start_col": 0, "answer": "OVERTIME", "clue_text": "..." }
  ]
}
```

## Real-time upgrade (optional)

Group mode currently uses polling, which is simple and needs no extra
infrastructure. If you want instant updates across teammates' screens, swap
the polling `useEffect` in `PuzzlePlay.jsx` for Laravel Echo + a WebSocket
driver (Reverb, Pusher, or Ably), and broadcast an event from
`PlaySessionController::updateCell` instead of relying on the client refetch.

## Ideas for later

- Timer / leaderboard per puzzle (elapsed time, team ranking)
- Hint button (reveal one letter, small score penalty)
- CSV/Excel import for bulk clue entry when authoring long puzzles
