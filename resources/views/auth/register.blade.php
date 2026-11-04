@extends('layouts.app')
@section('title','Daftar Mahasiswa - CBT Campus')
@section('content')
<div class="login-page register-page">
    <div class="login-visual">
        <div class="visual-inner">
            <div class="logo-lg">CB</div>
            <h1>Akun Mahasiswa</h1>
            <p>Daftar sekali, lalu gunakan akun yang sama untuk mengikuti ujian dari dosen mana pun melalui kode ujian.</p>
            <div class="visual-card"><span>1</span> Buat akun mahasiswa</div>
            <div class="visual-card"><span>2</span> Login ke CBT Campus</div>
            <div class="visual-card"><span>3</span> Masukkan kode ujian dari dosen</div>
        </div>
    </div>

    <div class="login-panel register-panel">
        <form class="login-card register-card" method="post" action="{{ route('register.submit') }}">
            @csrf
            <a class="back-auth" href="{{ route('login') }}">← Kembali ke Login</a>
            <h2>Daftar Mahasiswa</h2>
            <p>Isi data berikut untuk membuat akun CBT.</p>

            @if($errors->any())
                <div class="alert danger">
                    <strong>Periksa kembali:</strong>
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="auth-grid">
                <div class="span-2">
                    <label>Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Nama lengkap mahasiswa" required autofocus>
                </div>
                <div>
                    <label>NIM</label>
                    <input type="text" name="student_number" value="{{ old('student_number') }}" placeholder="Contoh: 22123456" required>
                </div>
                <div class="span-2">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required>
                </div>
                <div>
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Minimal 6 karakter" required>
                </div>
                <div>
                    <label>Konfirmasi Password</label>
                    <input type="password" name="password_confirmation" placeholder="Ulangi password" required>
                </div>
            </div>

            <button class="btn primary full" type="submit">Daftar & Masuk</button>
        </form>
    </div>
</div>
@endsection
