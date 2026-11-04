<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('questions', 'exam_id')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->foreignId('exam_id')->nullable()->after('course_id')->constrained('exams')->cascadeOnDelete();
            });
        }

        // Upgrade aman dari v0.3: salin relasi lama exam_question ke questions.exam_id.
        if (Schema::hasTable('exam_question')) {
            $links = DB::table('exam_question')->orderBy('position')->orderBy('id')->get();
            foreach ($links as $link) {
                DB::table('questions')
                    ->where('id', $link->question_id)
                    ->whereNull('exam_id')
                    ->update(['exam_id' => $link->exam_id]);
            }
        }

        // Jika soal lama belum pernah dipilih ke ujian, pindahkan otomatis bila
        // hanya ada satu ujian milik dosen untuk mata kuliah yang sama.
        $orphans = DB::table('questions')->whereNull('exam_id')->get();
        foreach ($orphans as $question) {
            $matchingExamIds = DB::table('exams')
                ->where('lecturer_id', $question->lecturer_id)
                ->where('course_id', $question->course_id)
                ->pluck('id');

            if ($matchingExamIds->count() === 1) {
                DB::table('questions')
                    ->where('id', $question->id)
                    ->update(['exam_id' => $matchingExamIds->first()]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('questions', 'exam_id')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('exam_id');
            });
        }
    }
};
