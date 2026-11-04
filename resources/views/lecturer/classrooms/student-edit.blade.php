@extends('layouts.app')
@section('back-url', route('lecturer.classes.show',$classroom))
@section('back-label','Kelola Mahasiswa')
@section('title','Edit Profil Mahasiswa')
@section('page-title','Edit Profil Mahasiswa')
@section('page-subtitle', $student->name.' · '.$classroom->name)

@section('content')
<div class="card form-card">
    <div class="card-head">
        <div>
            <h3>Data Mahasiswa</h3>
            <p>Perbarui identitas akun mahasiswa. Kosongkan password jika tidak ingin menggantinya.</p>
        </div>
        <span class="badge soft">Mahasiswa</span>
    </div>

    <form method="post" action="{{ route('lecturer.classes.students.update',[$classroom,$student]) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')


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

        <div class="form-grid">
            <div class="span-2">
                <label>Nama Lengkap</label>
                <input name="name" value="{{ old('name',$student->name) }}" required>
            </div>

            <div>
                <label>NIM</label>
                <input name="student_number" value="{{ old('student_number',$student->student_number) }}" required>
            </div>

            <div>
                <label>Username</label>
                <input name="username" value="{{ old('username',$student->username) }}" required>
            </div>

            <div class="span-2">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email',$student->email) }}" required>
            </div>

            <div>
                <label>Password Baru <span class="optional">(opsional)</span></label>
                <input type="password" name="password" placeholder="Kosongkan jika tidak diganti">
                <small class="field-help">Isi hanya jika dosen ingin mereset password mahasiswa.</small>
            </div>

            <div>
                <label>Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" placeholder="Ulangi password baru">
            </div>
        </div>

        <div class="management-note" style="margin-top:18px">
            <strong>Catatan</strong>
            <p>Riwayat ujian dan nilai mahasiswa tidak berubah ketika profil diedit. Role akun tetap Mahasiswa.</p>
        </div>

        <div class="form-actions">
            <a class="btn ghost" href="{{ route('lecturer.classes.show',$classroom) }}">Batal</a>
            <button class="btn primary">Simpan Profil</button>
        </div>
    </form>
</div>
@endsection
