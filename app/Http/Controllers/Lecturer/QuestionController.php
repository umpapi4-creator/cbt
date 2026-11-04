<?php
namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\{Exam, Question, QuestionOption};
use App\Services\QuestionImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class QuestionController extends Controller
{
    public function create(Exam $exam)
    {
        $this->ownExam($exam);
        return view('lecturer.questions.form', [
            'exam' => $exam->load('course'),
            'question' => new Question,
        ]);
    }

    public function store(Request $request, Exam $exam)
    {
        $this->ownExam($exam);
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $data, $exam) {
            $question = Question::create([
                'lecturer_id' => auth()->id(),
                'course_id' => $exam->course_id,
                'exam_id' => $exam->id,
                'text' => $data['text'],
                'explanation' => $data['explanation'] ?? null,
                'difficulty' => $data['difficulty'],
                'image' => $request->hasFile('image') ? $request->file('image')->store('questions', 'public') : null,
            ]);
            $this->saveOptions($question, $data);
        });

        return redirect()->route('lecturer.exams.questions', $exam)
            ->with('success', 'Soal berhasil ditambahkan langsung ke ujian.');
    }

    public function importForm(Exam $exam)
    {
        $this->ownExam($exam);
        return view('lecturer.questions.import', ['exam' => $exam->load('course')]);
    }

    public function importStore(Request $request, Exam $exam, QuestionImportService $importer)
    {
        $this->ownExam($exam);
        $request->validate(['file' => 'required|file|max:5120|mimes:docx,txt,csv']);

        try {
            $file = $request->file('file');
            $questions = $importer->parse($file->getRealPath(), strtolower($file->getClientOriginalExtension()));

            DB::transaction(function () use ($questions, $exam) {
                foreach ($questions as $item) {
                    $question = Question::create([
                        'lecturer_id' => auth()->id(),
                        'course_id' => $exam->course_id,
                        'exam_id' => $exam->id,
                        'text' => $item['text'],
                        'explanation' => $item['explanation'] ?: null,
                        'difficulty' => $item['difficulty'],
                    ]);

                    foreach (['A', 'B', 'C', 'D', 'E'] as $label) {
                        QuestionOption::create([
                            'question_id' => $question->id,
                            'label' => $label,
                            'text' => $item['options'][$label],
                            'is_correct' => $item['correct'] === $label,
                        ]);
                    }
                }
            });

            return redirect()->route('lecturer.exams.questions', $exam)
                ->with('success', count($questions).' soal berhasil diimport langsung ke ujian.');
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['file' => $e->getMessage()]);
        }
    }

    public function edit(Exam $exam, Question $question)
    {
        $this->ownQuestion($exam, $question);
        $question->load('options');
        return view('lecturer.questions.form', [
            'exam' => $exam->load('course'),
            'question' => $question,
        ]);
    }

    public function update(Request $request, Exam $exam, Question $question)
    {
        $this->ownQuestion($exam, $question);
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $data, $question) {
            $payload = [
                'text' => $data['text'],
                'explanation' => $data['explanation'] ?? null,
                'difficulty' => $data['difficulty'],
            ];
            if ($request->hasFile('image')) {
                if ($question->image) Storage::disk('public')->delete($question->image);
                $payload['image'] = $request->file('image')->store('questions', 'public');
            }
            $question->update($payload);
            $question->options()->delete();
            $this->saveOptions($question, $data);
        });

        return redirect()->route('lecturer.exams.questions', $exam)->with('success', 'Soal diperbarui.');
    }

    public function destroy(Exam $exam, Question $question)
    {
        $this->ownQuestion($exam, $question);
        if ($question->image) Storage::disk('public')->delete($question->image);
        $question->delete();
        return back()->with('success', 'Soal dihapus dari ujian.');
    }

    private function ownExam(Exam $exam): void
    {
        abort_unless((int) $exam->lecturer_id === (int) auth()->id(), 403);
    }

    private function ownQuestion(Exam $exam, Question $question): void
    {
        $this->ownExam($exam);
        abort_unless(
            (int) $question->lecturer_id === (int) auth()->id() &&
            (int) $question->exam_id === (int) $exam->id,
            403
        );
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'text' => 'required|string',
            'image' => 'nullable|image|max:2048',
            'explanation' => 'nullable|string',
            'difficulty' => 'required|in:easy,medium,hard',
            'option_A' => 'required|string',
            'option_B' => 'required|string',
            'option_C' => 'required|string',
            'option_D' => 'required|string',
            'option_E' => 'required|string',
            'correct' => 'required|in:A,B,C,D,E',
        ]);
    }

    private function saveOptions(Question $question, array $data): void
    {
        foreach (['A', 'B', 'C', 'D', 'E'] as $label) {
            QuestionOption::create([
                'question_id' => $question->id,
                'label' => $label,
                'text' => $data['option_'.$label],
                'is_correct' => $data['correct'] === $label,
            ]);
        }
    }
}
