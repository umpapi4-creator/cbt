<?php
namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\{Exam,Course,Classroom,User,ExamRetakePermission};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ExamController extends Controller
{
    public function index()
    {
        return view('lecturer.exams.index', [
            'exams' => Exam::where('lecturer_id',auth()->id())
                ->with('course','classroom')
                ->withCount('questions','attempts')
                ->latest()->get(),
        ]);
    }

    public function create()
    {
        return view('lecturer.exams.form', [
            'exam'=>new Exam,
            'courses'=>Course::where('lecturer_id',auth()->id())->get(),
            'classes'=>Classroom::where('lecturer_id',auth()->id())->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $this->ownedRefs($data);
        $data['lecturer_id'] = auth()->id();
        $data['status'] = 'published';
        $data['token'] = strtoupper(trim($request->token ?: $this->generateUniqueCode()));
        $data = $this->flags($request, $data);
        $exam = Exam::create($data);

        return redirect()->route('lecturer.exams.questions',$exam)
            ->with('success','Ujian dibuat. Sekarang tambahkan soal langsung ke ujian ini.');
    }

    public function edit(Exam $exam)
    {
        $this->own($exam);
        return view('lecturer.exams.form', [
            'exam'=>$exam,
            'courses'=>Course::where('lecturer_id',auth()->id())->get(),
            'classes'=>Classroom::where('lecturer_id',auth()->id())->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Exam $exam)
    {
        $this->own($exam);
        $base = $this->validated($request, $exam);
        $this->ownedRefs($base);
        $data = $this->flags($request, $base);
        $data['status'] = 'published';
        $data['token'] = strtoupper(trim($request->token ?: $exam->token));
        $exam->update($data);
        return redirect()->route('lecturer.exams.index')->with('success','Ujian diperbarui.');
    }

    public function destroy(Exam $exam)
    {
        $this->own($exam);
        $exam->delete();
        return back()->with('success','Ujian dihapus.');
    }

    public function questions(Exam $exam)
    {
        $this->own($exam);
        $exam->load(['course','classroom']);
        $questions = $exam->questions()->with('options')->get();
        return view('lecturer.exams.questions', compact('exam','questions'));
    }

    public function results(Exam $exam)
    {
        $this->own($exam);
        $exam->load([
            'attempts' => fn($q) => $q->with(['student','tabEvents'])->orderBy('student_id')->orderBy('attempt_number')->orderBy('id'),
            'retakePermissions',
        ]);
        return view('lecturer.exams.results',compact('exam'));
    }

    public function toggleRetake(Exam $exam, User $student)
    {
        $this->own($exam);
        abort_unless($student->role === 'student', 404);

        $hasHistory = $exam->attempts()
            ->where('student_id', $student->id)
            ->where('status', 'submitted')
            ->exists();
        abort_unless($hasHistory, 422, 'Mahasiswa belum memiliki riwayat ujian yang selesai.');

        $permission = ExamRetakePermission::firstOrNew([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
        ]);

        $permission->is_enabled = !$permission->exists || !$permission->is_enabled;
        $permission->enabled_by_lecturer_id = auth()->id();
        $permission->save();

        return back()->with('success', $permission->is_enabled
            ? 'Mahasiswa diizinkan mengulang ujian tanpa batas. Setiap percobaan akan tetap tersimpan pada riwayat.'
            : 'Izin mengulang ujian dinonaktifkan. Riwayat percobaan yang sudah ada tetap tersimpan.');
    }

    private function ownedRefs(array $data): void
    {
        abort_unless(Course::where('id',$data['course_id'])->where('lecturer_id',auth()->id())->exists(),403);
        abort_unless(Classroom::where('id',$data['classroom_id'])->where('lecturer_id',auth()->id())->exists(),403);
    }

    private function own(Exam $exam): void
    {
        abort_unless((int) $exam->lecturer_id === (int) auth()->id(), 403);
    }

    private function flags(Request $request,array $data): array
    {
        $data['shuffle_questions']=$request->boolean('shuffle_questions');
        $data['shuffle_options']=$request->boolean('shuffle_options');
        $data['show_result']=$request->boolean('show_result');
        return $data;
    }

    private function validated(Request $request, ?Exam $exam = null): array
    {
        $tokenRule = Rule::unique('exams', 'token');
        if ($exam) $tokenRule->ignore($exam->id);

        return $request->validate([
            'course_id'=>'required|exists:courses,id',
            'classroom_id'=>'required|exists:classrooms,id',
            'title'=>'required|max:180',
            'description'=>'nullable',
            'starts_at'=>'required|date',
            'ends_at'=>'required|date|after:starts_at',
            'duration_minutes'=>'required|integer|min:1|max:600',
            'token'=>['nullable','string','max:12','regex:/^[A-Za-z0-9_-]+$/',$tokenRule],
            'passing_score'=>'required|numeric|min:0|max:100',
        ], [
            'token.unique' => 'Kode ujian sudah dipakai. Gunakan kode lain.',
            'token.regex' => 'Kode ujian hanya boleh berisi huruf, angka, tanda hubung, atau underscore.',
        ]);
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (Exam::whereRaw('UPPER(token) = ?', [$code])->exists());

        return $code;
    }
}
