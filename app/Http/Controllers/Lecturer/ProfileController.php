<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('lecturer.profile.edit', [
            'lecturer' => $request->user(),
        ]);
    }

    public function update(Request $request)
    {
        $lecturer = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'lecturer_number' => [
                'nullable', 'string', 'max:50',
                Rule::unique('users', 'lecturer_number')->ignore($lecturer->id),
            ],
            'username' => [
                'required', 'string', 'min:3', 'max:50', 'alpha_dash',
                Rule::unique('users', 'username')->ignore($lecturer->id),
            ],
            'email' => [
                'required', 'email', 'max:150',
                Rule::unique('users', 'email')->ignore($lecturer->id),
            ],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_profile_photo' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:3', 'max:100', 'confirmed'],
        ], [
            'lecturer_number.unique' => 'NIDN/NIP/nomor dosen sudah digunakan oleh akun lain.',
            'username.unique' => 'Username sudah digunakan.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, tanda minus, dan underscore.',
            'email.unique' => 'Email sudah digunakan.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        $number = trim((string) ($data['lecturer_number'] ?? ''));
        $update = [
            'name' => trim($data['name']),
            'lecturer_number' => $number !== '' ? $number : null,
            'username' => strtolower(trim($data['username'])),
            'email' => strtolower(trim($data['email'])),
        ];

        if (!empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        if ($request->boolean('remove_profile_photo') && $lecturer->profile_photo_path) {
            \App\Services\UploadStorage::delete($lecturer->profile_photo_path);
            $update['profile_photo_path'] = null;
        }

        if ($request->hasFile('profile_photo')) {
            if ($lecturer->profile_photo_path) {
                \App\Services\UploadStorage::delete($lecturer->profile_photo_path);
            }
            $update['profile_photo_path'] = \App\Services\UploadStorage::store($request->file('profile_photo'), 'profile_photos');
        }

        $lecturer->update($update);

        return back()->with('success', 'Profil dosen berhasil diperbarui.');
    }
}
