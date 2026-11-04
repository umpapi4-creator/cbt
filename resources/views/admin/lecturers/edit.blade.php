@extends('layouts.app')
@section('title','Edit Data Dosen')
@section('page-title','Edit Data Dosen')
@section('page-subtitle','Admin dapat memperbarui identitas akun dan mereset password dosen.')
@section('back-url', route('admin.dashboard'))
@section('back-label','Dashboard Admin')

@section('content')
<div class="card form-card">
    <div class="card-head">
        <div>
            <h3>{{ $lecturer->name }}</h3>
            <p>Perubahan profil tidak menghapus mata kuliah, kelas, soal, ujian, atau hasil milik dosen.</p>
        </div>
        <span class="badge role-lecturer">Dosen</span>
    </div>

    <form method="post" action="{{ route('admin.lecturers.update', $lecturer) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="form-grid">
            <div class="span-2">
                <label>Nama Lengkap</label>
                <input name="name" value="{{ old('name', $lecturer->name) }}" required>
            </div>

            <div>
                <label>NIDN / NIP / Nomor Dosen <span class="optional">(opsional)</span></label>
                <input name="lecturer_number" value="{{ old('lecturer_number', $lecturer->lecturer_number) }}" placeholder="Contoh: 0123456789">
            </div>

            <div>
                <label>Username</label>
                <input name="username" value="{{ old('username', $lecturer->username) }}" required>
            </div>

            <div class="span-2">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email', $lecturer->email) }}" required>
            </div>
        </div>


        <h4 class="section-title">Foto Profil</h4>
        <div class="profile-photo-editor">
            <div class="profile-photo-preview">
                @if($lecturer->profile_photo_path)
                    <img src="{{ \App\Services\UploadStorage::url($lecturer->profile_photo_path) }}" alt="Foto profil {{ $lecturer->name }}">
                @else
                    <span>{{ strtoupper(substr($lecturer->name,0,1)) }}</span>
                @endif
            </div>
            <div class="profile-photo-fields">
                <label>Upload Foto Baru <span class="optional">(opsional)</span></label>
                <input type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp">
                <small class="field-help">Format JPG, PNG, atau WEBP. Maksimal 2 MB.</small>
                @if($lecturer->profile_photo_path)
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
            <strong>Catatan</strong>
            <p>Password lama tidak dapat dilihat karena tersimpan dalam bentuk hash. Mengubah profil atau mereset password dosen tidak mengubah kepemilikan mata kuliah, kelas, soal, ujian, maupun hasil ujian yang sudah ada.</p>
        </div>

        <div class="form-actions">
            <a class="btn ghost" href="{{ route('admin.dashboard') }}">Batal</a>
            <button class="btn primary" type="submit">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection
