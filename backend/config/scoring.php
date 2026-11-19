<?php

/*
| Scoring rules. Change the numbers here, or override them without touching
| code by setting SCORE_POINTS_PER_WORD / SCORE_COMPLETION_BONUS in Render's
| Environment tab.
*/

return [
    // Points for every word a player (or team) gets right. Paid once per word.
    'points_per_word' => (int) env('SCORE_POINTS_PER_WORD', 100),

    // Extra points when the whole puzzle is solved.
    'completion_bonus' => (int) env('SCORE_COMPLETION_BONUS', 200),
];
