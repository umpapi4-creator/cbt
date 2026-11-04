<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function edit(User $user)
    {
        $this->studentOnly($user);

        return view('admin.students.edit', [
            'student' => $user,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->studentOnly($user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'student_number' => [
                'required', 'string', 'max:40',
                Rule::unique('users', 'student_number')->ignore($user->id),
            ],
            'username' => [
                'required', 'string', 'min:3', 'max:50', 'alpha_dash',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'required', 'email', 'max:150',
                Rule::unique('users', 'email')->ignore($user->id),
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

        // Password lama tidak pernah ditampilkan. Admin cukup mengisi password baru
        // jika ingin melakukan reset password mahasiswa.
        if (!empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        if ($request->boolean('remove_profile_photo') && $user->profile_photo_path) {
            \App\Services\UploadStorage::delete($user->profile_photo_path);
            $update['profile_photo_path'] = null;
        }

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                \App\Services\UploadStorage::delete($user->profile_photo_path);
            }
            $update['profile_photo_path'] = \App\Services\UploadStorage::store($request->file('profile_photo'), 'profile_photos');
        }

        $user->update($update);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Data mahasiswa ' . $user->name . ' berhasil diperbarui.');
    }

    private function studentOnly(User $user): void
    {
        abort_unless($user->role === 'student', 404);
    }
}
