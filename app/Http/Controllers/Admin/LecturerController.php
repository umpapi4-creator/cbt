<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LecturerController extends Controller
{
    public function edit(User $user)
    {
        $this->lecturerOnly($user);

        return view('admin.lecturers.edit', [
            'lecturer' => $user,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->lecturerOnly($user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'lecturer_number' => [
                'nullable', 'string', 'max:50',
                Rule::unique('users', 'lecturer_number')->ignore($user->id),
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
            'lecturer_number.unique' => 'NIDN/NIP/nomor dosen sudah digunakan oleh akun lain.',
            'username.unique' => 'Username sudah digunakan.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, tanda minus, dan underscore.',
            'email.unique' => 'Email sudah digunakan.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        $lecturerNumber = trim((string) ($data['lecturer_number'] ?? ''));

        $update = [
            'name' => trim($data['name']),
            'lecturer_number' => $lecturerNumber !== '' ? $lecturerNumber : null,
            'username' => strtolower(trim($data['username'])),
            'email' => strtolower(trim($data['email'])),
        ];

        // Password lama tidak pernah ditampilkan. Admin hanya menetapkan
        // password baru bila memang diperlukan.
        if (!empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        if ($request->boolean('remove_profile_photo') && $user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $update['profile_photo_path'] = null;
        }

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }
            $update['profile_photo_path'] = $request->file('profile_photo')->store('profile_photos', 'public');
        }

        $user->update($update);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Data dosen ' . $user->name . ' berhasil diperbarui.');
    }

    private function lecturerOnly(User $user): void
    {
        abort_unless($user->role === 'lecturer', 404);
    }
}
