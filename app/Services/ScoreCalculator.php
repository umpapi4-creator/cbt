<?php

namespace App\Services;

class ScoreCalculator
{
    public function calculate(int $total, int $correct, int $wrong): array
    {
        $total = max(0, $total);
        $correct = max(0, min($correct, $total));
        $wrong = max(0, min($wrong, $total - $correct));
        $unanswered = max(0, $total - $correct - $wrong);
        $score = $total > 0 ? round(($correct / $total) * 100, 2) : 0.0;

        return [
            'score' => $score,
            'correct' => $correct,
            'wrong' => $wrong,
            'unanswered' => $unanswered,
        ];
    }
}
