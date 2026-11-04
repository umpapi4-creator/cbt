<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('student.profile.edit', [
            'student' => $request->user(),
        ]);
    }

    public function update(Request $request)
    {
        $student = $request->user();

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
            \App\Services\UploadStorage::delete($student->profile_photo_path);
            $update['profile_photo_path'] = null;
        }

        if ($request->hasFile('profile_photo')) {
            if ($student->profile_photo_path) {
                \App\Services\UploadStorage::delete($student->profile_photo_path);
            }
            $update['profile_photo_path'] = \App\Services\UploadStorage::store($request->file('profile_photo'), 'profile_photos');
        }

        $student->update($update);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
