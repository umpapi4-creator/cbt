<?php
namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\ExamTabEvent;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    public function notifications(Request $request)
    {
        $events = ExamTabEvent::query()
            ->whereNull('notified_at')
            ->whereHas('attempt.exam', function ($query) {
                $query->where('lecturer_id', auth()->id());
            })
            ->with(['attempt.student', 'attempt.exam'])
            ->orderBy('id')
            ->limit(20)
            ->get();

        if ($events->isNotEmpty()) {
            ExamTabEvent::whereIn('id', $events->pluck('id'))->update(['notified_at' => now()]);
        }

        return response()->json([
            'events' => $events->map(function (ExamTabEvent $event) {
                $attempt = $event->attempt;
                return [
                    'id' => $event->id,
                    'attempt_id' => $attempt->id,
                    'student' => $attempt->student->name,
                    'student_number' => $attempt->student->student_number,
                    'exam' => $attempt->exam->title,
                    'attempt_number' => (int) $attempt->attempt_number,
                    'count' => (int) $event->count_after,
                    'event_type' => $event->event_type,
                    'occurred_at' => $event->occurred_at->format('H:i:s'),
                    'terminated' => (bool) $attempt->terminated_for_tab_switch,
                    'results_url' => route('lecturer.exams.results', $attempt->exam_id),
                ];
            })->values(),
        ]);
    }
}
