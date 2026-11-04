<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\{ExamAttempt, ExamRetakePermission};

class HistoryController extends Controller
{
    public function __invoke()
    {
        $studentId = auth()->id();

        $attempts = ExamAttempt::where('student_id', $studentId)
            ->with(['exam.course', 'exam.classroom', 'tabEvents'])
            ->orderByDesc('created_at')
            ->get();

        $groups = $attempts
            ->groupBy('exam_id')
            ->map(fn ($items) => $items->sortBy('attempt_number')->values());

        $permissions = ExamRetakePermission::where('student_id', $studentId)
            ->where('is_enabled', true)
            ->pluck('is_enabled', 'exam_id');

        return view('student.history', compact('groups', 'permissions'));
    }
}
