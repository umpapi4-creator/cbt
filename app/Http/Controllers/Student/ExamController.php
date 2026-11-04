<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\{Exam,ExamAttempt,ExamAnswer,ExamTabEvent,ExamRetakePermission,Question,QuestionOption};
use App\Services\ScoreCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    public function __construct(private ScoreCalculator $scoreCalculator) {}

    /**
     * Kode ujian adalah kunci akses utama untuk ujian pertama.
     */
    public function lookup(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:20',
        ], [
            'code.required' => 'Masukkan kode ujian terlebih dahulu.',
        ]);

        $code = strtoupper(trim($data['code']));
        $student = auth()->user();

        $exam = Exam::whereRaw('UPPER(token) = ?', [$code])
            ->with(['course', 'classroom'])
            ->withCount('questions')
            ->first();

        if (!$exam) {
            session()->forget('unlocked_exam_id');
            return back()
                ->withInput()
                ->withErrors(['code' => 'Kode ujian tidak ditemukan. Periksa kembali kode dari dosen.']);
        }

        session(['unlocked_exam_id' => $exam->id]);

        $inProgress = $exam->attempts()
            ->where('student_id', $student->id)
            ->where('status', 'in_progress')
            ->latest('id')
            ->first();

        if ($inProgress) {
            return redirect()->route('student.attempts.show', $inProgress);
        }

        $latest = $exam->attempts()
            ->where('student_id', $student->id)
            ->where('status', 'submitted')
            ->orderByDesc('attempt_number')
            ->latest('id')
            ->first();

        if ($latest) {
            if ($this->retakeEnabled($exam, $student->id)) {
                return redirect()->route('student.history')
                    ->with('success', 'Ujian ini sudah pernah Anda kerjakan. Dosen mengizinkan pengulangan tanpa batas; gunakan menu Riwayat Ujian untuk mengulang.');
            }
            return redirect()->route('student.attempts.result', $latest);
        }

        return redirect()->route('student.dashboard')
            ->with('success', 'Kode ujian ditemukan. Periksa detail ujian lalu klik Mulai Ujian saat jadwal sudah dibuka.');
    }

    public function join(Request $request, Exam $exam)
    {
        $request->validate([
            'rules_completed' => 'accepted',
        ], [
            'rules_completed.accepted' => 'Baca seluruh slide peraturan ujian terlebih dahulu sebelum memulai.',
        ]);

        abort_unless((int) session('unlocked_exam_id') === (int) $exam->id, 403, 'Masukkan kode ujian terlebih dahulu.');

        $inProgress = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', auth()->id())
            ->where('status', 'in_progress')
            ->latest('id')
            ->first();

        if ($inProgress) {
            return redirect()->route('student.attempts.show', $inProgress);
        }

        $latestSubmitted = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', auth()->id())
            ->where('status', 'submitted')
            ->orderByDesc('attempt_number')
            ->latest('id')
            ->first();

        if ($latestSubmitted) {
            if ($this->retakeEnabled($exam, auth()->id())) {
                return redirect()->route('student.history')
                    ->with('success', 'Untuk mengulang ujian, gunakan tombol Ulangi Ujian pada menu Riwayat Ujian.');
            }
            return redirect()->route('student.attempts.result', $latestSubmitted);
        }

        if (now()->lt($exam->starts_at)) {
            return back()->withErrors(['code' => 'Ujian belum dimulai. Waktu mulai: '.$exam->starts_at->format('d M Y H:i').'.']);
        }
        if (now()->gte($exam->ends_at)) {
            return back()->withErrors(['code' => 'Ujian sudah ditutup.']);
        }

        $attempt = $this->createAttempt($exam, false);
        return redirect()->route('student.attempts.show', $attempt);
    }

    /**
     * Pengulangan tidak dibatasi jumlah percobaan selama dosen mengaktifkan izin ulang.
     * Jadwal ujian asli tidak membatasi retake; setiap retake mendapat durasi penuh dari saat dimulai.
     */
    public function retake(Request $request, Exam $exam)
    {
        $request->validate([
            'rules_completed' => 'accepted',
        ], [
            'rules_completed.accepted' => 'Baca seluruh slide peraturan ujian terlebih dahulu sebelum memulai ulang.',
        ]);

        $studentId = auth()->id();

        abort_unless($this->retakeEnabled($exam, $studentId), 403, 'Dosen belum mengizinkan Anda mengulang ujian ini.');

        $hasSubmitted = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->where('status', 'submitted')
            ->exists();
        abort_unless($hasSubmitted, 403, 'Ujian pertama harus diselesaikan sebelum dapat diulang.');

        $inProgress = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->where('status', 'in_progress')
            ->latest('id')
            ->first();

        if ($inProgress) {
            return redirect()->route('student.attempts.show', $inProgress);
        }

        $attempt = $this->createAttempt($exam, true);
        return redirect()->route('student.attempts.show', $attempt)
            ->with('success', 'Ujian ke-'.$attempt->attempt_number.' dimulai. Riwayat percobaan sebelumnya tetap tersimpan.');
    }

    public function show(ExamAttempt $attempt)
    {
        $this->ownAttempt($attempt);
        if ($attempt->status === 'submitted') return redirect()->route('student.attempts.result', $attempt);

        if (now()->gte($attempt->deadline_at)) {
            $this->finalize($attempt);
            return redirect()->route('student.attempts.result', $attempt)->with('success', 'Waktu habis. Jawaban otomatis dikirim.');
        }

        $attempt->load('exam.course', 'answers');
        $questions = Question::with('options')
            ->where('exam_id', $attempt->exam_id)
            ->whereIn('id', $attempt->question_order ?? [])
            ->get()->keyBy('id');
        $answers = $attempt->answers->keyBy('question_id');

        return view('student.exam', compact('attempt', 'questions', 'answers'));
    }

    public function answer(Request $request, ExamAttempt $attempt)
    {
        $this->ownAttempt($attempt);
        if ($attempt->status !== 'in_progress' || now()->gte($attempt->deadline_at)) {
            return response()->json(['ok' => false, 'message' => 'Waktu ujian selesai.'], 422);
        }

        $data = $request->validate([
            'question_id' => 'required|integer',
            'option_id' => 'nullable|integer',
        ]);

        abort_unless(in_array((int) $data['question_id'], $attempt->question_order ?? []), 403);
        if ($data['option_id']) {
            abort_unless(
                QuestionOption::where('id', $data['option_id'])
                    ->where('question_id', $data['question_id'])
                    ->exists(),
                422
            );
        }

        ExamAnswer::updateOrCreate(
            ['attempt_id' => $attempt->id, 'question_id' => $data['question_id']],
            ['selected_option_id' => $data['option_id'] ?: null, 'answered_at' => now()]
        );

        return response()->json(['ok' => true, 'saved_at' => now()->format('H:i:s')]);
    }

    public function tabViolation(Request $request, ExamAttempt $attempt)
    {
        $this->ownAttempt($attempt);

        if ($attempt->status !== 'in_progress') {
            return response()->json([
                'ok' => false,
                'submitted' => true,
                'redirect' => route('student.attempts.result', $attempt),
            ], 409);
        }

        if (now()->gte($attempt->deadline_at)) {
            $this->finalize($attempt);
            return response()->json([
                'ok' => false,
                'submitted' => true,
                'message' => 'Waktu ujian telah habis.',
                'redirect' => route('student.attempts.result', $attempt),
            ]);
        }

        $request->validate([
            'event_type' => 'nullable|in:visibility_hidden',
        ]);

        $count = DB::transaction(function () use ($attempt) {
            /** @var ExamAttempt $locked */
            $locked = ExamAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'in_progress') {
                return (int) $locked->tab_switch_count;
            }

            $next = min(3, ((int) $locked->tab_switch_count) + 1);
            $locked->update(['tab_switch_count' => $next]);

            ExamTabEvent::create([
                'attempt_id' => $locked->id,
                'event_type' => 'visibility_hidden',
                'occurred_at' => now(),
                'count_after' => $next,
            ]);

            return $next;
        });

        if ($count >= 3) {
            $attempt->refresh();
            if ($attempt->status === 'in_progress') {
                $this->finalize($attempt, true);
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'ok' => true,
                'count' => 3,
                'terminated' => true,
                'message' => 'Pelanggaran 3 dari 3. Ujian otomatis diselesaikan dan akun Anda telah keluar.',
                'redirect' => route('login'),
            ]);
        }

        return response()->json([
            'ok' => true,
            'count' => $count,
            'terminated' => false,
            'message' => 'Peringatan '.$count.' dari 3: Anda terdeteksi meninggalkan tab ujian.',
        ]);
    }

    public function submit(ExamAttempt $attempt)
    {
        $this->ownAttempt($attempt);
        if ($attempt->status === 'in_progress') $this->finalize($attempt);
        session()->forget('unlocked_exam_id');
        return redirect()->route('student.attempts.result', $attempt)->with('success', 'Ujian ke-'.$attempt->attempt_number.' berhasil dikirim dan nilai telah dihitung otomatis.');
    }

    public function result(ExamAttempt $attempt)
    {
        $this->ownAttempt($attempt);
        if ($attempt->status !== 'submitted') return redirect()->route('student.attempts.show', $attempt);
        session()->forget('unlocked_exam_id');
        $attempt->load('exam.course', 'tabEvents');
        $retakeEnabled = $this->retakeEnabled($attempt->exam, auth()->id());
        return view('student.result', compact('attempt', 'retakeEnabled'));
    }

    private function createAttempt(Exam $exam, bool $isRetake): ExamAttempt
    {
        $questions = $exam->questions()->pluck('id')->all();
        if (!$questions) {
            abort(422, 'Ujian ini belum memiliki soal. Hubungi dosen.');
        }

        if ($exam->shuffle_questions) shuffle($questions);

        $options = [];
        foreach ($questions as $qid) {
            $ids = QuestionOption::where('question_id', $qid)->pluck('id')->all();
            if ($exam->shuffle_options) shuffle($ids);
            $options[$qid] = $ids;
        }

        $deadline = now()->copy()->addMinutes($exam->duration_minutes);
        if (!$isRetake && $deadline->gt($exam->ends_at)) {
            $deadline = $exam->ends_at->copy();
        }

        return DB::transaction(function () use ($exam, $questions, $options, $deadline) {
            $latest = ExamAttempt::where('exam_id', $exam->id)
                ->where('student_id', auth()->id())
                ->orderByDesc('attempt_number')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $attemptNumber = $latest ? ((int) $latest->attempt_number + 1) : 1;

            return ExamAttempt::create([
                'exam_id' => $exam->id,
                'student_id' => auth()->id(),
                'attempt_number' => $attemptNumber,
                'started_at' => now(),
                'deadline_at' => $deadline,
                'status' => 'in_progress',
                'question_order' => $questions,
                'option_orders' => $options,
                'tab_switch_count' => 0,
                'terminated_for_tab_switch' => false,
            ]);
        });
    }

    private function retakeEnabled(Exam $exam, int $studentId): bool
    {
        return ExamRetakePermission::where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->where('is_enabled', true)
            ->exists();
    }

    private function finalize(ExamAttempt $attempt, bool $terminatedForTabSwitch = false)
    {
        DB::transaction(function () use ($attempt, $terminatedForTabSwitch) {
            $attempt->refresh();
            if ($attempt->status === 'submitted') return;

            $correct = 0;
            $wrong = 0;
            $answers = $attempt->answers()->get()->keyBy('question_id');

            foreach ($attempt->question_order ?? [] as $qid) {
                $answer = $answers->get($qid);
                if (!$answer || !$answer->selected_option_id) continue;

                $isCorrect = QuestionOption::where('id', $answer->selected_option_id)
                    ->where('question_id', $qid)
                    ->where('is_correct', true)
                    ->exists();

                $answer->update(['is_correct' => $isCorrect]);
                $isCorrect ? $correct++ : $wrong++;
            }

            $summary = $this->scoreCalculator->calculate(
                count($attempt->question_order ?? []),
                $correct,
                $wrong
            );

            $updates = [
                'submitted_at' => now(),
                'status' => 'submitted',
                'score' => $summary['score'],
                'correct_count' => $summary['correct'],
                'wrong_count' => $summary['wrong'],
                'unanswered_count' => $summary['unanswered'],
            ];

            if ($terminatedForTabSwitch) {
                $updates['terminated_for_tab_switch'] = true;
                $updates['terminated_at'] = now();
                $updates['tab_switch_count'] = max(3, (int) $attempt->tab_switch_count);
            }

            $attempt->update($updates);
        });
    }

    private function ownAttempt(ExamAttempt $attempt)
    {
        abort_unless($attempt->student_id === auth()->id(), 403);
    }
}
