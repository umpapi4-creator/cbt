@extends('layouts.app')
@section('back-url', route('lecturer.classes.index'))
@section('back-label','Daftar Kelas')
@section('title','Kelola Mahasiswa')
@section('page-title','Kelola Mahasiswa')
@section('page-subtitle', $classroom->name.' · '.$classroom->academic_year)

@section('content')
<div class="classroom-summary card">
    <div class="card-head">
        <div>
            <h3>{{ $classroom->name }}</h3>
            <p>{{ $classroom->academic_year }} · {{ $classroom->students->count() }} mahasiswa</p>
        </div>
    </div>

    <form method="post" action="{{ route('lecturer.classes.update',$classroom) }}" class="form-grid compact-form">
        @csrf
        @method('PUT')
        <div>
            <label>Nama Kelas</label>
            <input name="name" value="{{ old('name',$classroom->name) }}" required>
        </div>
        <div>
            <label>Tahun Akademik</label>
            <input name="academic_year" value="{{ old('academic_year',$classroom->academic_year) }}" required>
        </div>
        <div class="span-2"><button class="btn ghost small">Simpan Perubahan Kelas</button></div>
    </form>
</div>

<div class="grid-2 student-management-grid">
    <div class="card">
        <div class="card-head">
            <div>
                <h3>+ Tambah Mahasiswa Baru</h3>
                <p>Buat akun mahasiswa dan langsung masukkan ke kelas ini.</p>
            </div>
        </div>

        <form method="post" action="{{ route('lecturer.classes.students.store',$classroom) }}">
            @csrf
            <label>Nama Lengkap</label>
            <input name="name" value="{{ old('name') }}" placeholder="Nama mahasiswa" required>

            <div class="form-grid" style="margin-top:14px">
                <div>
                    <label>NIM</label>
                    <input name="student_number" value="{{ old('student_number') }}" placeholder="Contoh: 23123456" required>
                </div>
                <div>
                    <label>Username</label>
                    <input name="username" value="{{ old('username') }}" placeholder="Contoh: alvin23" required>
                </div>
                <div>
                    <label>Email <span class="optional">(opsional)</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="mahasiswa@email.com">
                    <small class="field-help">Jika kosong, sistem membuat email lokal otomatis.</small>
                </div>
                <div>
                    <label>Password Awal <span class="optional">(opsional)</span></label>
                    <input type="text" name="password" placeholder="Kosongkan = sama dengan NIM">
                    <small class="field-help">Jika kosong, password awal otomatis menggunakan NIM.</small>
                </div>
            </div>

            <button class="btn primary" style="margin-top:16px">+ Simpan Mahasiswa</button>
        </form>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h3>Masukkan Akun yang Sudah Ada</h3>
                <p>Mahasiswa yang mendaftar sendiri dapat dimasukkan tanpa membuat akun baru.</p>
            </div>
        </div>

        <form method="post" action="{{ route('lecturer.classes.students.attach',$classroom) }}">
            @csrf
            <label>NIM / Username / Email</label>
            <div class="attach-student-form">
                <input name="student_identity" value="{{ old('student_identity') }}" placeholder="Cari menggunakan NIM, username, atau email" required>
                <button class="btn primary">Tambahkan</button>
            </div>
        </form>

        <div class="management-note">
            <strong>Bedanya Keluarkan dan Hapus Akun</strong>
            <p><b>Keluarkan</b> hanya menghapus mahasiswa dari kelas. <b>Hapus Akun</b> benar-benar menghapus akun dari database dan hanya tersedia untuk akun yang dibuat oleh Anda serta belum pernah mengikuti ujian.</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <div>
            <h3>Daftar Mahasiswa</h3>
            <p>Semua mahasiswa yang saat ini tergabung dalam {{ $classroom->name }}.</p>
        </div>
        <span class="badge soft">{{ $classroom->students->count() }} mahasiswa</span>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Mahasiswa</th>
                    <th>NIM</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Riwayat Ujian</th>
                    <th>Edit Profil</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($classroom->students as $student)
                    <tr>
                        <td>
                            <strong>{{ $student->name }}</strong>
                            @if((int)$student->created_by_lecturer_id === (int)auth()->id())
                                <small>Dibuat oleh Anda</small>
                            @else
                                <small>Akun mahasiswa umum</small>
                            @endif
                        </td>
                        <td>{{ $student->student_number ?: '-' }}</td>
                        <td>{{ $student->username ?: '-' }}</td>
                        <td>{{ $student->email }}</td>
                        <td>{{ $student->attempts_count }} percobaan</td>
                        <td>
                            <a class="btn outline small" href="{{ route('lecturer.classes.students.edit',[$classroom,$student]) }}">Edit Profil</a>
                        </td>
                        <td>
                            <div class="actions">
                                <form method="post" action="{{ route('lecturer.classes.students.detach',[$classroom,$student]) }}" onsubmit="return confirm('Keluarkan {{ addslashes($student->name) }} dari kelas ini? Akunnya tidak akan dihapus.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn ghost small">Keluarkan</button>
                                </form>

                                @if((int)$student->created_by_lecturer_id === (int)auth()->id())
                                    @if($student->attempts_count === 0)
                                        <form method="post" action="{{ route('lecturer.classes.students.account.destroy',[$classroom,$student]) }}" onsubmit="return confirm('HAPUS AKUN {{ addslashes($student->name) }} secara permanen dari database? Tindakan ini tidak dapat dibatalkan.')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn danger small">Hapus Akun</button>
                                        </form>
                                    @else
                                        <span class="delete-disabled" title="Akun tidak dapat dihapus karena sudah memiliki riwayat ujian">Tidak dapat hapus akun</span>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">Belum ada mahasiswa di kelas ini. Tambahkan mahasiswa menggunakan form di atas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
