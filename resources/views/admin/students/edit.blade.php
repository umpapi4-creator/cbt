@extends('layouts.app')
@section('title','Edit Data Mahasiswa')
@section('page-title','Edit Data Mahasiswa')
@section('page-subtitle','Admin dapat memperbarui identitas akun dan mereset password mahasiswa.')
@section('back-url', route('admin.dashboard'))
@section('back-label','Dashboard Admin')

@section('content')
<div class="card form-card">
    <div class="card-head">
        <div>
            <h3>{{ $student->name }}</h3>
            <p>Perubahan di halaman ini langsung berlaku pada akun mahasiswa.</p>
        </div>
        <span class="badge role-student">Mahasiswa</span>
    </div>

    <form method="post" action="{{ route('admin.students.update', $student) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="form-grid">
            <div class="span-2">
                <label>Nama Lengkap</label>
                <input name="name" value="{{ old('name', $student->name) }}" required>
            </div>

            <div>
                <label>NIM</label>
                <input name="student_number" value="{{ old('student_number', $student->student_number) }}" required>
            </div>

            <div>
                <label>Username</label>
                <input name="username" value="{{ old('username', $student->username) }}" required>
            </div>

            <div class="span-2">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email', $student->email) }}" required>
            </div>
        </div>


        <h4 class="section-title">Foto Profil</h4>
        <div class="profile-photo-editor">
            <div class="profile-photo-preview">
                @if($student->profile_photo_path)
                    <img src="{{ asset('storage/'.$student->profile_photo_path) }}" alt="Foto profil {{ $student->name }}">
                @else
                    <span>{{ strtoupper(substr($student->name,0,1)) }}</span>
                @endif
            </div>
            <div class="profile-photo-fields">
                <label>Upload Foto Baru <span class="optional">(opsional)</span></label>
                <input type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp">
                <small class="field-help">Format JPG, PNG, atau WEBP. Maksimal 2 MB. Foto akan dipotong secara visual menjadi bentuk persegi/lingkaran.</small>
                @if($student->profile_photo_path)
                    <label class="checkline photo-remove"><input type="checkbox" name="remove_profile_photo" value="1"> Hapus foto profil saat ini</label>
                @endif
            </div>
        </div>

        <h4 class="section-title">Reset Password <span class="optional">(opsional)</span></h4>
        <div class="form-grid">
            <div>
                <label>Password Baru</label>
                <input type="password" name="password" autocomplete="new-password" placeholder="Kosongkan jika password tidak diubah">
            </div>
            <div>
                <label>Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" autocomplete="new-password" placeholder="Ulangi password baru">
            </div>
        </div>

        <div class="management-note">
            <strong>Catatan keamanan</strong>
            <p>Password lama tidak dapat dilihat karena tersimpan dalam bentuk hash. Admin hanya dapat menetapkan password baru. Mengubah profil tidak menghapus kelas, jawaban, maupun riwayat nilai mahasiswa.</p>
        </div>

        <div class="form-actions">
            <a class="btn ghost" href="{{ route('admin.dashboard') }}">Batal</a>
            <button class="btn primary" type="submit">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection
