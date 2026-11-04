<?php
namespace Database\Seeders;

use App\Models\{User, Course, Classroom, Question, QuestionOption, Exam};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@cbt.test'],
            ['name' => 'Admin CBT', 'username' => 'tesadmin', 'password' => Hash::make('tesadmin'), 'role' => 'admin', 'student_number' => null, 'lecturer_number' => null]
        );

        $lecturer = User::updateOrCreate(
            ['email' => 'dosen@cbt.test'],
            ['name' => 'Dosen Demo', 'username' => 'tesdosen', 'password' => Hash::make('tessdosen'), 'role' => 'lecturer', 'lecturer_number' => 'DSN001']
        );
        $student = User::updateOrCreate(
            ['email' => 'student@cbt.test'],
            ['name' => 'Student Demo', 'username' => 'tes', 'password' => Hash::make('tes'), 'role' => 'student', 'student_number' => 'MHS001']
        );
        $student2 = User::updateOrCreate(
            ['email' => 'student2@cbt.test'],
            ['name' => 'Siti Aisyah', 'username' => 'siti.demo', 'password' => Hash::make('password'), 'role' => 'student', 'student_number' => 'MHS002']
        );

        $course = Course::updateOrCreate(
            ['lecturer_id' => $lecturer->id, 'code' => 'MIK401'],
            ['name' => 'Klasifikasi dan Kodifikasi Penyakit', 'semester' => 7]
        );
        $class = Classroom::firstOrCreate([
            'lecturer_id' => $lecturer->id,
            'name' => 'D4 MIK 7A',
            'academic_year' => '2026/2027',
        ]);
        $class->students()->syncWithoutDetaching([$student->id, $student2->id]);

        $exam = Exam::updateOrCreate(
            ['lecturer_id' => $lecturer->id, 'title' => 'UTS Demo CBT'],
            [
                'course_id' => $course->id,
                'classroom_id' => $class->id,
                'description' => 'Ujian demo untuk mencoba alur CBT.',
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addDays(30),
                'duration_minutes' => 60,
                'token' => 'CBT123',
                'passing_score' => 60,
                'shuffle_questions' => true,
                'shuffle_options' => false,
                'show_result' => true,
                'status' => 'published',
            ]
        );

        if ($exam->questions()->count() === 0) {
            $samples = [
                ['Kode ICD-10 untuk hipertensi esensial adalah?', ['I10','I11.9','I12.9','I15','I20.9'], 0],
                ['Kode ICD-10 yang umum untuk diabetes melitus tipe 2 tanpa komplikasi adalah?', ['E10.9','E11.9','E12.9','E13.9','E14.9'], 1],
                ['Pada ICD-10, pneumonia unspecified dikodekan sebagai?', ['J18.9','J15.9','J12.9','J44.9','J06.9'], 0],
                ['Tujuan utama rekam medis adalah?', ['Menyimpan data klinis dan administratif pasien','Membuat iklan rumah sakit','Menggantikan seluruh komunikasi klinis','Menyimpan data keuangan saja','Menghapus kebutuhan identifikasi pasien'], 0],
                ['Jika waktu ujian habis, sistem CBT seharusnya?', ['Menghapus jawaban','Memulai ulang timer','Mengirim jawaban secara otomatis','Menambah waktu 30 menit','Keluar tanpa menyimpan'], 2],
            ];

            foreach ($samples as $sample) {
                $q = Question::create([
                    'lecturer_id' => $lecturer->id,
                    'course_id' => $course->id,
                    'exam_id' => $exam->id,
                    'text' => $sample[0],
                    'difficulty' => 'medium',
                ]);
                foreach (['A','B','C','D','E'] as $i => $label) {
                    QuestionOption::create([
                        'question_id' => $q->id,
                        'label' => $label,
                        'text' => $sample[1][$i],
                        'is_correct' => $i === $sample[2],
                    ]);
                }
            }
        }
    }
}
