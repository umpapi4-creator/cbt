<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            // The original unique index is also used by the foreign keys.
            // Remove those constraints before dropping the index.
            $table->dropForeign(['exam_id']);
            $table->dropForeign(['student_id']);
            $table->dropUnique(['exam_id', 'student_id']);
        });

        // A failed MySQL ALTER can leave earlier DDL changes applied. Keep the
        // retry safe when attempt_number already exists.
        Schema::table('exam_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_attempts', 'attempt_number')) {
                $table->unsignedInteger('attempt_number')->default(1)->after('student_id');
            }

            $table->unique(['exam_id', 'student_id', 'attempt_number'], 'exam_attempts_number_unique');
            $table->index(['exam_id', 'student_id'], 'exam_attempts_student_exam_index');
            $table->foreign('exam_id')->references('id')->on('exams')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('exam_retake_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('enabled_by_lecturer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            $table->unique(['exam_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_retake_permissions');

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropForeign(['exam_id']);
            $table->dropForeign(['student_id']);
            $table->dropUnique('exam_attempts_number_unique');
            $table->dropIndex('exam_attempts_student_exam_index');
            $table->dropColumn('attempt_number');
            $table->unique(['exam_id', 'student_id']);
            $table->foreign('exam_id')->references('id')->on('exams')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
