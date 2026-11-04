@extends('layouts.app')
@section('title','Login CBT Campus')
@section('content')
<div class="login-page campus-login">
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
            <div class="login-brand"><span class="login-brand-mark" aria-hidden="true">CB</span><div><strong>CBT Campus</strong><small>Portal ujian kampus</small></div></div>
            <span class="login-eyebrow">SIAP UNTUK LANGKAH BERIKUTNYA?</span>
            <h2>Selamat datang kembali<span>.</span></h2>
            <p>Masuk ke akun Anda untuk melanjutkan aktivitas ujian.</p>

            @if(session('registered'))
                <div class="alert success">{{ session('registered') }}</div>
            @endif
            @if($errors->any())
                <div class="alert danger">{{ $errors->first() }}</div>
            @endif

            <label for="loginIdentity">Username / Email</label>
            <input id="loginIdentity" type="text" name="login" autocomplete="username" value="{{ old('login') }}" placeholder="Masukkan username atau email" required autofocus>

            <label for="loginPassword">Password</label>
            <input id="loginPassword" type="password" name="password" autocomplete="current-password" placeholder="••••••••" required>
            <div class="login-options">
                <label class="checkline"><input type="checkbox" id="showLoginPassword" aria-controls="loginPassword"> Tampilkan password</label>
                <label class="checkline"><input type="checkbox" name="remember"> Ingat saya</label>
            </div>
            <button class="btn primary full login-submit" type="submit">Masuk ke akun <span aria-hidden="true">&rarr;</span></button>

            <div class="register-box">
                <span>Baru di CBT Campus?</span>
                <a class="btn outline full" href="{{ route('register') }}">Buat Akun Mahasiswa dan Dosen</a>
                <small>Akun dosen hanya dapat diverifikasi oleh Admin.</small>
            </div>

            <details class="demo-box login-demo">
                <summary>Coba dulu dengan akun demo <span aria-hidden="true">+</span></summary>
                <div class="login-demo-accounts">
                <span>Admin: tesadmin / tesadmin</span>
                <span>Dosen: tesdosen / tessdosen</span>
                <span>Mahasiswa: tes / tes</span>
                </div>
            </details>
            <div class="login-footer">Satu portal untuk setiap langkah ujian Anda.</div>
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
