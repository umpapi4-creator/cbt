@extends('layouts.app')
@section('title','Dashboard Student')
@section('page-title','Masuk Ujian')
@section('page-subtitle','Masukkan kode dari dosen. Ujian hanya muncul setelah kode berhasil ditemukan.')
@section('content')
<div class="student-access-wrap">
    <div class="card access-card">
        <div class="access-icon">⌨</div>
        <h2>Masukkan Kode Ujian</h2>
        <p>Ketik kode yang diberikan oleh dosen/pengawas. Mahasiswa tidak perlu mencari ujian dari daftar.</p>
        <form method="post" action="{{ route('student.exams.lookup') }}" class="exam-code-form">
            @csrf
            <input name="code" value="{{ old('code') }}" maxlength="20" autocomplete="off" placeholder="Contoh: 123456" required autofocus>
            <button class="btn primary">Cari Ujian</button>
        </form>
        <small class="access-note">Kode tidak peka huruf besar/kecil.</small>
    </div>

    @if($exam)
        @php
            $now = now();
            $isOpen = $now->gte($exam->starts_at) && $now->lt($exam->ends_at);
            $hasQuestions = ($exam->questions_count ?? 0) > 0;
        @endphp
        <div class="card unlocked-exam">
            <div class="exam-card-top">
                <div>
                    <span class="course-code">{{ $exam->course->code }}</span>
                    <h3>{{ $exam->title }}</h3>
                    <p>{{ $exam->course->name }} · {{ $exam->classroom->name }}</p>
                </div>
                <span class="badge {{ $isOpen && $hasQuestions ? 'published' : 'soft' }}">
                    @if(!$hasQuestions)
                        Belum Ada Soal
                    @elseif($isOpen)
                        Siap Dimulai
                    @elseif($now->lt($exam->starts_at))
                        Belum Dimulai
                    @else
                        Ditutup
                    @endif
                </span>
            </div>

            @if($exam->description)
                <div class="exam-description">{{ $exam->description }}</div>
            @endif

            <div class="exam-info">
                <span><small>Tanggal</small>{{ $exam->starts_at->format('d M Y') }}</span>
                <span><small>Waktu</small>{{ $exam->starts_at->format('H:i') }}–{{ $exam->ends_at->format('H:i') }}</span>
                <span><small>Durasi</small>{{ $exam->duration_minutes }} menit</span>
                <span><small>Jumlah Soal</small>{{ $exam->questions_count ?? 0 }} soal</span>
            </div>

            @if($attempt && $attempt->status === 'submitted')
                <div class="dashboard-result-actions">
                    <a class="btn primary" href="{{ route('student.attempts.result',$attempt) }}">Lihat Hasil Ujian ke-{{ $attempt->attempt_number }}</a>
                    @if($retakeEnabled)
                        <a class="btn success" href="{{ route('student.history') }}">Riwayat & Ulangi Ujian</a>
                    @endif
                </div>
            @elseif($attempt)
                <a class="btn primary full" href="{{ route('student.attempts.show',$attempt) }}">Lanjutkan Ujian</a>
            @elseif(!$hasQuestions)
                <div class="not-available">Kode sudah benar, tetapi dosen belum menambahkan soal ke ujian ini.</div>
            @elseif($isOpen)
                <form id="startExamForm" method="post" action="{{ route('student.exams.join',$exam) }}">
                    @csrf
                    <input type="hidden" name="rules_completed" id="rulesCompletedInput" value="0">
                    <button type="button" class="btn primary full" id="openRulesBtn">Mulai Ujian</button>
                </form>
            @elseif($now->lt($exam->starts_at))
                <div class="not-available">Kode benar. Ujian baru dapat dimulai pada {{ $exam->starts_at->format('d M Y H:i') }}.</div>
            @else
                <div class="not-available">Kode benar, tetapi jadwal ujian sudah berakhir.</div>
            @endif
        </div>
    @endif
</div>

@if($exam && !$attempt && $isOpen && $hasQuestions)
<div class="modal" id="rulesModal">
    <div class="modal-card rules-modal-card rules-slider-card">
        <div class="rules-slider-head">
            <div>
                <span class="rules-kicker">WAJIB DIBACA</span>
                <h3 id="rulesSlideTitle">Pengawasan Ujian</h3>
            </div>
            <span class="rules-step" id="rulesStepLabel">1 / 3</span>
        </div>

        <div class="rules-progress" aria-hidden="true">
            <span class="active"></span><span></span><span></span>
        </div>

        <div class="rules-slide active" data-slide="0">
            <div class="rules-icon">!</div>
            <p class="rules-intro">Lima aturan pengawasan utama berikut berlaku selama ujian berlangsung.</p>
            <ol class="exam-rules">
                <li>Selama ujian, tetap berada pada tab ujian dan jangan meminimalkan browser atau berpindah ke aplikasi/tab lain.</li>
                <li>Setiap kali tab ujian ditinggalkan, sistem akan mencatat <strong>1 pelanggaran</strong> dan dosen akan menerima notifikasi.</li>
                <li>Pelanggaran pertama dan kedua akan menampilkan peringatan saat Anda kembali ke halaman ujian.</li>
                <li>Pada <strong>pelanggaran ke-3</strong>, ujian otomatis diselesaikan, nilai dihitung dari jawaban yang sudah tersimpan, lalu akun otomatis logout.</li>
                <li>Riwayat waktu meninggalkan tab akan tersimpan pada hasil ujian dan dapat dilihat oleh dosen.</li>
            </ol>
        </div>

        <div class="rules-slide" data-slide="1">
            <div class="rules-icon general-rules-icon">✓</div>
            <p class="rules-intro">Selain pengawasan tab, peserta wajib mengikuti ketentuan umum ujian berikut.</p>
            <ol class="exam-rules">
                <li>Kerjakan ujian secara mandiri dan jujur sesuai kemampuan sendiri.</li>
                <li>Dilarang berkomunikasi atau bertukar jawaban dengan peserta lain selama ujian.</li>
                <li>Gunakan buku, catatan, kalkulator, atau sumber lain hanya apabila secara khusus diizinkan oleh dosen.</li>
                <li>Pastikan perangkat, koneksi internet, dan daya/baterai dalam kondisi siap sebelum ujian dimulai.</li>
                <li>Jangan menutup browser atau melakukan refresh halaman tanpa keperluan. Jawaban akan disimpan otomatis ketika dipilih.</li>
                <li>Baca setiap soal dengan teliti, perhatikan sisa waktu, dan pastikan semua jawaban sudah dipilih sebelum mengirim ujian.</li>
                <li>Jika terjadi kendala teknis, segera hubungi dosen/pengawas dan jangan melakukan tindakan yang dapat menghapus sesi ujian.</li>
            </ol>
        </div>

        <div class="rules-slide" data-slide="2">
            <div class="rules-icon ready-rules-icon">✓</div>
            <p class="rules-intro">Periksa kembali sebelum Anda memulai <strong>{{ $exam->title }}</strong>.</p>
            <div class="rules-summary-grid">
                <div><small>Jumlah Soal</small><strong>{{ $exam->questions_count ?? 0 }}</strong></div>
                <div><small>Durasi</small><strong>{{ $exam->duration_minutes }} menit</strong></div>
                <div><small>Batas Pelanggaran</small><strong>3 kali</strong></div>
            </div>
            <ul class="final-rules-list">
                <li>Waktu ujian mulai berjalan segera setelah tombol mulai ditekan.</li>
                <li>Keluar dari halaman tidak menghentikan timer ujian.</li>
                <li>Jawaban yang telah tersimpan akan digunakan apabila waktu habis atau ujian dihentikan otomatis.</li>
            </ul>
            <label class="rules-confirm-check">
                <input type="checkbox" id="rulesConfirmCheck">
                <span>Saya telah membaca seluruh peraturan pada semua slide dan bersedia mematuhinya.</span>
            </label>
            <div class="rules-warning">Tombol mulai hanya dapat digunakan setelah seluruh slide dibaca dan pernyataan di atas dicentang.</div>
        </div>

        <div class="rules-slider-actions">
            <button type="button" class="btn ghost" id="cancelRulesBtn">Batal</button>
            <div class="rules-nav-actions">
                <button type="button" class="btn ghost" id="prevRulesBtn" style="display:none">← Sebelumnya</button>
                <button type="button" class="btn primary" id="nextRulesBtn">Berikutnya →</button>
                <button type="button" class="btn success" id="acceptRulesBtn" style="display:none" disabled>Mulai Ujian</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
@if($exam && !$attempt && $isOpen && $hasQuestions)
<script>
(() => {
    const modal = document.getElementById('rulesModal');
    const open = document.getElementById('openRulesBtn');
    const cancel = document.getElementById('cancelRulesBtn');
    const prev = document.getElementById('prevRulesBtn');
    const next = document.getElementById('nextRulesBtn');
    const accept = document.getElementById('acceptRulesBtn');
    const confirmCheck = document.getElementById('rulesConfirmCheck');
    const completedInput = document.getElementById('rulesCompletedInput');
    const form = document.getElementById('startExamForm');
    const slides = Array.from(document.querySelectorAll('#rulesModal .rules-slide'));
    const progress = Array.from(document.querySelectorAll('#rulesModal .rules-progress span'));
    const stepLabel = document.getElementById('rulesStepLabel');
    const title = document.getElementById('rulesSlideTitle');
    const titles = ['Pengawasan Ujian', 'Ketentuan Umum Ujian', 'Siap Memulai'];
    let current = 0;

    if (!modal || !open || !form || !slides.length) return;

    const render = () => {
        slides.forEach((slide, index) => slide.classList.toggle('active', index === current));
        progress.forEach((dot, index) => dot.classList.toggle('active', index <= current));
        if (stepLabel) stepLabel.textContent = `${current + 1} / ${slides.length}`;
        if (title) title.textContent = titles[current] || 'Peraturan Ujian';
        if (prev) prev.style.display = current === 0 ? 'none' : '';
        if (next) next.style.display = current === slides.length - 1 ? 'none' : '';
        if (accept) accept.style.display = current === slides.length - 1 ? '' : 'none';
    };

    const reset = () => {
        current = 0;
        if (confirmCheck) confirmCheck.checked = false;
        if (completedInput) completedInput.value = '0';
        if (accept) accept.disabled = true;
        render();
    };

    open.addEventListener('click', () => {
        reset();
        modal.classList.add('show');
    });
    cancel?.addEventListener('click', () => modal.classList.remove('show'));
    prev?.addEventListener('click', () => { if (current > 0) { current--; render(); } });
    next?.addEventListener('click', () => { if (current < slides.length - 1) { current++; render(); } });
    confirmCheck?.addEventListener('change', () => {
        if (accept) accept.disabled = !confirmCheck.checked;
    });
    accept?.addEventListener('click', () => {
        if (!confirmCheck?.checked) return;
        if (completedInput) completedInput.value = '1';
        accept.disabled = true;
        accept.textContent = 'Memulai...';
        form.submit();
    });
    render();
})();
</script>
@endif
@endpush
