@extends('layouts.app')
@section('title','Admin CBT Campus')
@section('page-title','Dashboard Admin')
@section('page-subtitle','Kelola akun pengguna dan berikan role Dosen kepada akun yang berwenang.')

@section('content')
<div class="stats">
    <div class="stat"><span class="ico blue">♙</span><div><small>Total Pengguna</small><strong>{{ $totalCount }}</strong></div></div>
    <div class="stat"><span class="ico green">●</span><div><small>Mahasiswa</small><strong>{{ $studentCount }}</strong></div></div>
    <div class="stat"><span class="ico orange">▣</span><div><small>Dosen</small><strong>{{ $lecturerCount }}</strong></div></div>
    <div class="stat"><span class="ico purple">◆</span><div><small>Admin</small><strong>{{ $adminCount }}</strong></div></div>
</div>

<div class="card" id="users">
    <div class="card-head admin-head">
        <div>
            <h3>Manajemen Akun & Role</h3>
            <p>Akun baru dari halaman registrasi selalu menjadi mahasiswa. Admin dapat menaikkannya menjadi Dosen.</p>
        </div>
        <form class="admin-search" method="get" action="{{ route('admin.dashboard') }}">
            <input name="q" value="{{ $search }}" placeholder="Cari nama, username, NIM...">
            <button class="btn ghost">Cari</button>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Pengguna</th>
                <th>Identitas</th>
                <th>Role Saat Ini</th>
                <th>Riwayat</th>
                <th>Edit Data</th>
                <th>Ubah Role</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td>
                        <div class="admin-user-cell">
                            <div class="avatar mini">
                                @if($user->profile_photo_path)
                                    <img src="{{ \App\Services\UploadStorage::url($user->profile_photo_path) }}" alt="Foto {{ $user->name }}">
                                @else
                                    {{ strtoupper(substr($user->name,0,1)) }}
                                @endif
                            </div>
                            <div>
                                <strong>{{ $user->name }}</strong>
                                <small>{{ '@'.$user->username }} · {{ $user->email }}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($user->role === 'lecturer')
                            <strong>{{ $user->lecturer_number ?: 'Nomor dosen belum diisi' }}</strong>
                            <small>Akun dosen</small>
                        @elseif($user->role === 'student')
                            <strong>{{ $user->student_number ?: '-' }}</strong>
                            <small>NIM mahasiswa</small>
                        @else
                            <strong>Administrator</strong>
                            <small>Akun sistem</small>
                        @endif
                    </td>
                    <td>
                        @if($user->role === 'admin')
                            <span class="delete-disabled">—</span>
                        @else
                            <form method="post" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Hapus akun {{ addslashes($user->name) }} secara permanen? Data terkait akun juga dapat ikut terhapus.')">
                                @csrf
                                @method('DELETE')
                                <button class="btn danger small" type="submit">Hapus</button>
                            </form>
                        @endif
                    </td>
                    <td>
                        <span class="badge role-{{ $user->role }}">
                            {{ $user->role === 'lecturer' ? 'Dosen' : ($user->role === 'student' ? 'Mahasiswa' : 'Admin') }}
                        </span>
                    </td>
                    <td>
                        @if($user->role === 'student')
                            {{ $user->attempts_count }} percobaan
                        @elseif($user->role === 'lecturer')
                            {{ $user->exams_count }} ujian dibuat
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if($user->role === 'student')
                            <a class="btn ghost small" href="{{ route('admin.students.edit', $user) }}">Edit Profil</a>
                        @elseif($user->role === 'lecturer')
                            <a class="btn ghost small" href="{{ route('admin.lecturers.edit', $user) }}">Edit Profil</a>
                        @else
                            <span class="delete-disabled">—</span>
                        @endif
                    </td>
                    <td>
                        @if($user->role === 'admin')
                            <span class="delete-disabled">Role admin terkunci</span>
                        @else
                            <form class="role-form" method="post" action="{{ route('admin.users.role.update', $user) }}">
                                @csrf
                                @method('PUT')
                                <select name="role" class="role-select" data-role-select>
                                    <option value="student" @selected($user->role === 'student')>Mahasiswa</option>
                                    <option value="lecturer" @selected($user->role === 'lecturer')>Dosen</option>
                                </select>
                                <input name="lecturer_number" value="{{ $user->lecturer_number }}" placeholder="NIDN/NIP (opsional)" data-lecturer-number>
                                <button class="btn primary small" type="submit" onclick="return confirm('Ubah role akun {{ addslashes($user->name) }}?')">Simpan</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">Pengguna tidak ditemukan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $users->links() }}</div>
</div>

<div class="management-note">
    <strong>Aturan role</strong>
    <p>Registrasi publik hanya membuat akun Mahasiswa. Role Dosen hanya diberikan oleh Admin. Admin tidak dapat dibuat atau diubah melalui halaman ini.</p>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.role-form').forEach(function (form) {
    const select = form.querySelector('[data-role-select]');
    const number = form.querySelector('[data-lecturer-number]');
    function sync() {
        const lecturer = select.value === 'lecturer';
        number.style.display = lecturer ? '' : 'none';
        number.disabled = !lecturer;
    }
    select.addEventListener('change', sync);
    sync();
});
</script>
@endpush
