<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserRoleController extends Controller
{
    public function update(Request $request, User $user)
    {
        if ($user->role === 'admin') {
            return back()->withErrors(['role' => 'Role akun admin tidak dapat diubah dari halaman ini.']);
        }

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['role' => 'Anda tidak dapat mengubah role akun sendiri.']);
        }

        $data = $request->validate([
            'role' => ['required', Rule::in(['student', 'lecturer'])],
            'lecturer_number' => [
                'nullable', 'string', 'max:50',
                Rule::unique('users', 'lecturer_number')->ignore($user->id),
            ],
        ], [
            'role.in' => 'Role yang dipilih tidak valid.',
            'lecturer_number.unique' => 'Nomor dosen sudah digunakan oleh akun lain.',
        ]);

        $newRole = $data['role'];
        $update = ['role' => $newRole];

        if ($newRole === 'lecturer') {
            $lecturerNumber = trim((string) ($data['lecturer_number'] ?? ''));
            $update['lecturer_number'] = $lecturerNumber !== '' ? $lecturerNumber : null;
            // Akun yang sudah menjadi dosen tidak lagi menjadi anggota kelas mahasiswa.
            $user->classrooms()->detach();
        } else {
            $update['lecturer_number'] = null;
        }

        $user->update($update);

        $label = $newRole === 'lecturer' ? 'Dosen' : 'Mahasiswa';

        return back()->with('success', 'Role ' . $user->name . ' berhasil diubah menjadi ' . $label . '.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->role === 'admin') {
            return back()->withErrors(['user' => 'Akun admin tidak dapat dihapus dari halaman ini.']);
        }

        if ((int) $user->id === (int) $request->user()->id) {
            return back()->withErrors(['user' => 'Anda tidak dapat menghapus akun yang sedang digunakan.']);
        }

        if ($user->profile_photo_path) {
            \App\Services\UploadStorage::delete($user->profile_photo_path);
        }

        // Hapus file gambar soal milik dosen agar tidak menjadi file yatim
        // setelah data dosen dan soal dihapus oleh foreign key cascade.
        if ($user->role === 'lecturer') {
            $user->questions()->pluck('image')->filter()->each(function ($path) {
                \App\Services\UploadStorage::delete($path);
            });
        }

        $name = $user->name;
        $user->delete();

        return back()->with('success', 'Akun ' . $name . ' berhasil dihapus.');
    }
}
