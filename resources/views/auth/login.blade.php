@extends('layouts.app')
@section('title','Login CBT Campus')
@section('content')
<div class="login-page">
    <div class="login-visual">
        <div class="visual-inner">
            <div class="logo-lg">CB</div>
            <h1>CBT Campus</h1>
            <p>Sistem ujian online untuk admin, dosen, dan mahasiswa.</p>
            <div class="visual-card"><span>✓</span> Soal langsung per ujian & terjadwal</div>
            <div class="visual-card"><span>✓</span> Kode ujian, timer, auto-save & nilai otomatis</div>
            <div class="visual-card"><span>✓</span> Data setiap dosen terpisah dan aman</div>
        </div>
    </div>

    <div class="login-panel">
        <form class="login-card" method="post" action="{{ route('login.submit') }}">
            @csrf
            <h2>Masuk ke Sistem</h2>
            <p>Gunakan akun</p>

            @if(session('registered'))
                <div class="alert success">{{ session('registered') }}</div>
            @endif
            @if($errors->any())
                <div class="alert danger">{{ $errors->first() }}</div>
            @endif

            <label>Username / Email</label>
            <input type="text" name="login" value="{{ old('login') }}" placeholder="Masukkan username atau email" required autofocus>

            <label for="loginPassword">Password</label>
            <input id="loginPassword" type="password" name="password" autocomplete="current-password" placeholder="••••••••" required>
            <label class="checkline"><input type="checkbox" id="showLoginPassword" aria-controls="loginPassword"> Tampilkan Password</label>

            <label class="checkline"><input type="checkbox" name="remember"> Ingat saya</label>
            <button class="btn primary full" type="submit">Masuk</button>

            <div class="register-box">
                <span>Buat Akun</span>
                <a class="btn outline full" href="{{ route('register') }}">Buat Akun Mahasiswa dan Dosen</a>
                <small>Akun dosen hanya dapat diverifikasi oleh Admin.</small>
                <small>hubungi admin</small>
            </div>

            <div class="demo-box">
                <strong>Akun demo</strong>
                <span>Admin: tesadmin / tesadmin</span>
                <span>Dosen: tesdosen / tessdosen</span>
                <span>Mahasiswa: tes / tes</span>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const password = document.getElementById('loginPassword');
    const toggle = document.getElementById('showLoginPassword');
    const updateVisibility = () => {
        password.type = toggle.checked ? 'text' : 'password';
    };
    toggle.addEventListener('change', updateVisibility);
    updateVisibility();
})();
</script>
@endpush