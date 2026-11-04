<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\{Exam, ExamRetakePermission};

class DashboardController extends Controller
{
    public function __invoke()
    {
        $student = auth()->user();
        $exam = null;
        $attempt = null;
        $retakeEnabled = false;

        $unlockedExamId = session('unlocked_exam_id');
        if ($unlockedExamId) {
            $exam = Exam::whereKey($unlockedExamId)
                ->with(['course', 'classroom'])
                ->withCount('questions')
                ->first();

            if ($exam) {
                $attempt = $exam->attempts()
                    ->where('student_id', $student->id)
                    ->orderByRaw("CASE WHEN status = 'in_progress' THEN 0 ELSE 1 END")
                    ->orderByDesc('attempt_number')
                    ->orderByDesc('id')
                    ->first();

                $retakeEnabled = ExamRetakePermission::where('exam_id', $exam->id)
                    ->where('student_id', $student->id)
                    ->where('is_enabled', true)
                    ->exists();
            } else {
                session()->forget('unlocked_exam_id');
            }
        }

        return view('student.dashboard', compact('exam', 'attempt', 'retakeEnabled'));
    }
}
