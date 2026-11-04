@extends('layouts.app')
@section('title','Edit Profil Mahasiswa')
@section('page-title','Edit Profil')
@section('page-subtitle','Perbarui data akun mahasiswa dan password Anda.')
@section('back-url', route('student.dashboard'))
@section('back-label','Dashboard')

@section('content')
<div class="card form-card">
    <div class="card-head">
        <div>
            <h3>Profil Mahasiswa</h3>
            <p>Perubahan nama, NIM, username, dan email akan langsung digunakan pada akun Anda.</p>
        </div>
        <span class="badge published">Mahasiswa</span>
    </div>

    <form method="post" action="{{ route('student.profile.update') }}" enctype="multipart/form-data">
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
                    <img src="{{ \App\Services\UploadStorage::url($student->profile_photo_path) }}" alt="Foto profil {{ $student->name }}">
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

        <h4 class="section-title">Ganti Password <span class="optional">(opsional)</span></h4>
        <div class="form-grid">
            <div>
                <label>Password Baru</label>
                <input type="password" name="password" placeholder="Kosongkan jika tidak ingin mengganti">
            </div>
            <div>
                <label>Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" placeholder="Ulangi password baru">
            </div>
        </div>

        <div class="management-note">
            <strong>Keamanan akun</strong>
            <p>Role akun tidak dapat diubah dari halaman profil. Perubahan role hanya dapat dilakukan oleh Admin.</p>
        </div>

        <div class="form-actions">
            <a class="btn ghost" href="{{ route('student.dashboard') }}">Batal</a>
            <button class="btn primary" type="submit">Simpan Profil</button>
        </div>
    </form>
</div>
@endsection
