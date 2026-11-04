<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\{Classroom, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ClassroomController extends Controller
{
    public function index()
    {
        return view('lecturer.classrooms.index', [
            'classes' => Classroom::where('lecturer_id', auth()->id())
                ->withCount('students')
                ->latest()
                ->get(),
        ]);
    }

    public function show(Classroom $classroom)
    {
        $this->own($classroom);

        $classroom->load(['students' => function ($query) {
            $query->withCount('attempts')->orderBy('name');
        }]);

        return view('lecturer.classrooms.show', compact('classroom'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'academic_year' => ['required', 'string', 'max:20'],
        ]);

        Classroom::create([
            'lecturer_id' => auth()->id(),
            'name' => trim($data['name']),
            'academic_year' => trim($data['academic_year']),
        ]);

        return back()->with('success', 'Kelas berhasil ditambahkan. Silakan kelola mahasiswa pada kelas tersebut.');
    }

    public function update(Request $request, Classroom $classroom)
    {
        $this->own($classroom);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'academic_year' => ['required', 'string', 'max:20'],
        ]);

        $classroom->update([
            'name' => trim($data['name']),
            'academic_year' => trim($data['academic_year']),
        ]);

        return back()->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(Classroom $classroom)
    {
        $this->own($classroom);

        if ($classroom->exams()->exists()) {
            return back()->withErrors([
                'classroom' => 'Kelas tidak dapat dihapus karena sudah digunakan pada ujian. Hapus atau pindahkan ujian terlebih dahulu.',
            ]);
        }

        $classroom->delete();

        return back()->with('success', 'Kelas berhasil dihapus.');
    }

    /**
     * Buat akun mahasiswa baru langsung dari halaman kelas lalu otomatis
     * masukkan ke kelas yang sedang dikelola.
     */
    public function storeStudent(Request $request, Classroom $classroom)
    {
        $this->own($classroom);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'student_number' => ['required', 'string', 'max:40', 'unique:users,student_number'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'alpha_dash', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:3', 'max:100'],
        ], [
            'student_number.unique' => 'NIM sudah digunakan oleh akun lain.',
            'username.unique' => 'Username sudah digunakan.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, tanda minus, dan underscore.',
            'email.unique' => 'Email sudah digunakan.',
        ]);

        $nim = trim($data['student_number']);
        $email = strtolower(trim($data['email'] ?? ''));
        if ($email === '') {
            $email = strtolower(preg_replace('/[^A-Za-z0-9._-]/', '', $nim)) . '@cbt.local';

            if (User::where('email', $email)->exists()) {
                return back()->withErrors(['email' => 'Email otomatis sudah digunakan. Isi email mahasiswa secara manual.'])->withInput();
            }
        }

        $plainPassword = trim($data['password'] ?? '') !== '' ? $data['password'] : $nim;

        $student = User::create([
            'name' => trim($data['name']),
            'student_number' => $nim,
            'username' => strtolower(trim($data['username'])),
            'email' => $email,
            'password' => Hash::make($plainPassword),
            'role' => 'student',
            'lecturer_number' => null,
            'created_by_lecturer_id' => auth()->id(),
        ]);

        $classroom->students()->syncWithoutDetaching([$student->id]);

        return back()->with('success', 'Mahasiswa berhasil dibuat dan dimasukkan ke kelas. Password awal: ' . $plainPassword);
    }

    /**
     * Tambahkan akun mahasiswa yang sudah terdaftar ke kelas menggunakan
     * NIM, username, atau email.
     */
    public function attachStudent(Request $request, Classroom $classroom)
    {
        $this->own($classroom);

        $data = $request->validate([
            'student_identity' => ['required', 'string', 'max:150'],
        ]);

        $identity = trim($data['student_identity']);
        $lower = strtolower($identity);

        $student = User::where('role', 'student')
            ->where(function ($query) use ($identity, $lower) {
                $query->where('student_number', $identity)
                    ->orWhereRaw('LOWER(username) = ?', [$lower])
                    ->orWhereRaw('LOWER(email) = ?', [$lower]);
            })
            ->first();

        if (!$student) {
            return back()->withErrors([
                'student_identity' => 'Mahasiswa tidak ditemukan. Periksa NIM, username, atau email.',
            ])->withInput();
        }

        if ($classroom->students()->where('users.id', $student->id)->exists()) {
            return back()->withErrors([
                'student_identity' => 'Mahasiswa tersebut sudah berada di kelas ini.',
            ]);
        }

        $classroom->students()->attach($student->id);

        return back()->with('success', $student->name . ' berhasil ditambahkan ke kelas.');
    }


    /** Tampilkan form edit profil mahasiswa pada kelas ini. */
    public function editStudent(Classroom $classroom, User $student)
    {
        $this->own($classroom);
        $this->studentInClass($classroom, $student);

        return view('lecturer.classrooms.student-edit', compact('classroom', 'student'));
    }

    /**
     * Perbarui profil mahasiswa. Password hanya diganti jika diisi.
     * Role dan kepemilikan akun tidak dapat diubah dari form ini.
     */
    public function updateStudent(Request $request, Classroom $classroom, User $student)
    {
        $this->own($classroom);
        $this->studentInClass($classroom, $student);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'student_number' => [
                'required', 'string', 'max:40',
                Rule::unique('users', 'student_number')->ignore($student->id),
            ],
            'username' => [
                'required', 'string', 'min:3', 'max:50', 'alpha_dash',
                Rule::unique('users', 'username')->ignore($student->id),
            ],
            'email' => [
                'required', 'email', 'max:150',
                Rule::unique('users', 'email')->ignore($student->id),
            ],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_profile_photo' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:3', 'max:100', 'confirmed'],
        ], [
            'student_number.unique' => 'NIM sudah digunakan oleh akun lain.',
            'username.unique' => 'Username sudah digunakan.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, tanda minus, dan underscore.',
            'email.unique' => 'Email sudah digunakan.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        $update = [
            'name' => trim($data['name']),
            'student_number' => trim($data['student_number']),
            'username' => strtolower(trim($data['username'])),
            'email' => strtolower(trim($data['email'])),
        ];

        if (!empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        if ($request->boolean('remove_profile_photo') && $student->profile_photo_path) {
            Storage::disk('public')->delete($student->profile_photo_path);
            $update['profile_photo_path'] = null;
        }

        if ($request->hasFile('profile_photo')) {
            if ($student->profile_photo_path) {
                Storage::disk('public')->delete($student->profile_photo_path);
            }
            $update['profile_photo_path'] = $request->file('profile_photo')->store('profile_photos', 'public');
        }

        $student->update($update);

        return redirect()->route('lecturer.classes.show', $classroom)
            ->with('success', 'Profil mahasiswa ' . $student->name . ' berhasil diperbarui.');
    }

    /** Keluarkan mahasiswa dari kelas tanpa menghapus akun. */
    public function detachStudent(Classroom $classroom, User $student)
    {
        $this->own($classroom);
        $this->studentInClass($classroom, $student);

        $classroom->students()->detach($student->id);

        return back()->with('success', $student->name . ' dikeluarkan dari kelas. Akunnya tetap dapat digunakan.');
    }

    /**
     * Hapus akun hanya jika dibuat oleh dosen yang sedang login dan belum
     * mempunyai riwayat ujian, agar nilai lama tidak ikut terhapus.
     */
    public function destroyStudentAccount(Classroom $classroom, User $student)
    {
        $this->own($classroom);
        $this->studentInClass($classroom, $student);

        abort_unless($student->role === 'student', 404);
        abort_unless((int) $student->created_by_lecturer_id === (int) auth()->id(), 403);

        if ($student->attempts()->exists()) {
            return back()->withErrors([
                'student' => 'Akun tidak dapat dihapus karena mahasiswa sudah mempunyai riwayat ujian. Gunakan "Keluarkan" jika hanya ingin menghapusnya dari kelas.',
            ]);
        }

        $studentName = $student->name;
        if ($student->profile_photo_path) {
            Storage::disk('public')->delete($student->profile_photo_path);
        }
        $student->delete();

        return redirect()->route('lecturer.classes.show', $classroom)
            ->with('success', 'Akun mahasiswa ' . $studentName . ' berhasil dihapus dari database.');
    }

    private function own(Classroom $classroom): void
    {
        abort_unless((int) $classroom->lecturer_id === (int) auth()->id(), 403);
    }

    private function studentInClass(Classroom $classroom, User $student): void
    {
        abort_unless(
            $student->role === 'student' && $classroom->students()->where('users.id', $student->id)->exists(),
            404
        );
    }
}
