@extends('layouts.app')
@section('back-url', route('student.dashboard'))
@section('back-label','Dashboard')
@section('title','Riwayat Ujian')
@section('page-title','Riwayat Ujian')
@section('page-subtitle','Lihat nilai setiap percobaan dan ulangi ujian jika dosen memberikan izin.')
@section('content')
@if($groups->isEmpty())
<div class="card empty-history-card">
    <div class="access-icon">↺</div>
    <h3>Belum ada riwayat ujian</h3>
    <p>Ujian yang sudah Anda mulai atau selesaikan akan muncul di halaman ini.</p>
    <a class="btn primary" href="{{ route('student.dashboard') }}">Masukkan Kode Ujian</a>
</div>
@else
<div class="history-exam-list">
@foreach($groups as $examId => $attempts)
    @php
        $first = $attempts->first();
        $exam = $first->exam;
        $latest = $attempts->last();
        $inProgress = $attempts->firstWhere('status','in_progress');
        $retakeEnabled = (bool)($permissions[$examId] ?? false);
    @endphp
    <div class="card history-exam-card">
        <div class="card-head history-head">
            <div>
                <span class="course-code">{{ $exam->course->code }}</span>
                <h3>{{ $exam->title }}</h3>
                <p>{{ $exam->course->name }} · {{ $exam->classroom->name }}</p>
            </div>
            <div class="history-head-badges">
                <span class="badge soft">{{ $attempts->count() }} Percobaan</span>
                @if($retakeEnabled)
                    <span class="badge published">Ulang Tanpa Batas Aktif</span>
                @else
                    <span class="badge completed">Ulang Belum Diizinkan</span>
                @endif
            </div>
        </div>

        <div class="table-wrap">
            <table class="attempt-history-table">
                <thead>
                    <tr>
                        <th>Percobaan</th>
                        <th>Mulai</th>
                        <th>Selesai</th>
                        <th>Nilai</th>
                        <th>Benar</th>
                        <th>Salah</th>
                        <th>Pelanggaran Tab</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($attempts as $attempt)
                    <tr class="{{ $attempt->terminated_for_tab_switch ? 'terminated-row' : '' }}">
                        <td><strong>Ujian ke-{{ $attempt->attempt_number }}</strong></td>
                        <td>{{ $attempt->started_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td>{{ $attempt->submitted_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td>
                            @if($attempt->status !== 'submitted')
                                <strong>-</strong>
                            @elseif($exam->show_result)
                                <strong>{{ number_format((float)$attempt->score,2) }}</strong>
                            @else
                                <span class="badge soft">Disembunyikan</span>
                            @endif
                        </td>
                        <td>{{ $attempt->status === 'submitted' ? $attempt->correct_count : '-' }}</td>
                        <td>{{ $attempt->status === 'submitted' ? $attempt->wrong_count : '-' }}</td>
                        <td>
                            @if((int)$attempt->tab_switch_count === 0)
                                <span class="badge soft">0/3</span>
                            @elseif($attempt->terminated_for_tab_switch)
                                <span class="badge violation-critical">3/3 · Dihentikan</span>
                            @else
                                <span class="badge violation-warning">{{ $attempt->tab_switch_count }}/3</span>
                            @endif
                        </td>
                        <td>
                            @if($attempt->status === 'in_progress')
                                <span class="badge draft">Sedang Berlangsung</span>
                            @elseif((float)$attempt->score >= (float)$exam->passing_score)
                                <span class="badge published">Lulus</span>
                            @else
                                <span class="badge completed">Belum Lulus</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="retake-student-actions">
            @if($inProgress)
                <div class="retake-info">
                    <strong>Ujian ke-{{ $inProgress->attempt_number }} masih berjalan.</strong>
                    <span>Selesaikan percobaan ini sebelum membuat percobaan berikutnya.</span>
                </div>
                <a class="btn primary" href="{{ route('student.attempts.show',$inProgress) }}">Lanjutkan Ujian ke-{{ $inProgress->attempt_number }}</a>
            @elseif($retakeEnabled)
                <div class="retake-info">
                    <strong>Dosen mengizinkan pengulangan tanpa batas.</strong>
                    <span>Jika diulang lagi, sistem akan membuat Ujian ke-{{ ((int)$latest->attempt_number) + 1 }} dan nilai sebelumnya tetap tersimpan.</span>
                </div>
                <form method="post" action="{{ route('student.exams.retake',$exam) }}" class="retake-form" data-exam-title="{{ $exam->title }}" data-next-attempt="{{ ((int)$latest->attempt_number) + 1 }}">
                    @csrf
                    <input type="hidden" name="rules_completed" value="0" class="retake-rules-completed">
                    <button type="button" class="btn success open-retake-rules">↺ Ulangi Ujian</button>
                </form>
            @else
                <div class="retake-info muted-retake">
                    <strong>Pengulangan belum diizinkan.</strong>
                    <span>Hubungi dosen jika Anda diminta mengerjakan ujian ini kembali.</span>
                </div>
            @endif
        </div>
    </div>
@endforeach
</div>
@endif

<div class="modal" id="retakeRulesModal">
    <div class="modal-card rules-modal-card rules-slider-card">
        <div class="rules-slider-head">
            <div>
                <span class="rules-kicker">UJIAN ULANG · WAJIB DIBACA</span>
                <h3 id="retakeRulesTitle">Pengawasan Ujian</h3>
            </div>
            <span class="rules-step" id="retakeRulesStep">1 / 3</span>
        </div>
        <p class="retake-rules-context"><strong id="retakeAttemptLabel">Ujian berikutnya</strong> · <span id="retakeRulesExamName">Ujian</span></p>

        <div class="rules-progress retake-rules-progress" aria-hidden="true">
            <span class="active"></span><span></span><span></span>
        </div>

        <div class="rules-slide retake-rules-slide active" data-slide="0">
            <div class="rules-icon">!</div>
            <p class="rules-intro">Lima aturan pengawasan utama tetap berlaku pada setiap percobaan ulang.</p>
            <ol class="exam-rules">
                <li>Selama ujian, tetap berada pada tab ujian dan jangan meminimalkan browser atau berpindah ke aplikasi/tab lain.</li>
                <li>Setiap kali tab ujian ditinggalkan, sistem akan mencatat <strong>1 pelanggaran</strong> dan dosen akan menerima notifikasi.</li>
                <li>Pelanggaran pertama dan kedua akan menampilkan peringatan saat Anda kembali ke halaman ujian.</li>
                <li>Pada <strong>pelanggaran ke-3</strong>, ujian otomatis diselesaikan, nilai dihitung dari jawaban yang sudah tersimpan, lalu akun otomatis logout.</li>
                <li>Riwayat waktu meninggalkan tab akan tersimpan pada hasil ujian dan dapat dilihat oleh dosen.</li>
            </ol>
        </div>

        <div class="rules-slide retake-rules-slide" data-slide="1">
            <div class="rules-icon general-rules-icon">✓</div>
            <p class="rules-intro">Ketentuan umum berikut juga berlaku pada setiap ujian ulang.</p>
            <ol class="exam-rules">
                <li>Kerjakan ujian secara mandiri dan jujur tanpa bertukar jawaban dengan peserta lain.</li>
                <li>Gunakan buku, catatan, kalkulator, atau sumber lain hanya jika diizinkan dosen.</li>
                <li>Pastikan perangkat, internet, dan daya/baterai siap sebelum memulai percobaan baru.</li>
                <li>Jangan menutup browser atau melakukan refresh tanpa keperluan. Jawaban disimpan otomatis ketika dipilih.</li>
                <li>Kelola waktu dengan baik dan baca setiap soal dengan teliti sebelum menentukan jawaban.</li>
                <li>Jika ada kendala teknis, segera hubungi dosen/pengawas.</li>
            </ol>
        </div>

        <div class="rules-slide retake-rules-slide" data-slide="2">
            <div class="rules-icon ready-rules-icon">✓</div>
            <p class="rules-intro">Percobaan baru tidak menghapus nilai atau riwayat percobaan sebelumnya.</p>
            <div class="retake-final-note">
                <strong>Ulang tanpa batas aktif</strong>
                <span>Setiap percobaan akan dicatat sebagai Ujian ke-1, ke-2, ke-3, dan seterusnya beserta nilai dan riwayat pelanggarannya masing-masing.</span>
            </div>
            <ul class="final-rules-list">
                <li>Durasi baru mulai berjalan segera setelah percobaan ini dimulai.</li>
                <li>Keluar dari halaman tidak menghentikan timer.</li>
                <li>Jika pelanggaran mencapai 3 kali, hanya percobaan yang sedang berjalan yang dihentikan.</li>
            </ul>
            <label class="rules-confirm-check">
                <input type="checkbox" id="retakeRulesConfirmCheck">
                <span>Saya telah membaca seluruh peraturan pada semua slide dan siap memulai percobaan baru.</span>
            </label>
            <div class="rules-warning">Nilai percobaan sebelumnya tetap tersimpan dan tidak akan ditimpa.</div>
        </div>

        <div class="rules-slider-actions">
            <button type="button" class="btn ghost" id="cancelRetakeRules">Batal</button>
            <div class="rules-nav-actions">
                <button type="button" class="btn ghost" id="prevRetakeRules" style="display:none">← Sebelumnya</button>
                <button type="button" class="btn primary" id="nextRetakeRules">Berikutnya →</button>
                <button type="button" class="btn success" id="acceptRetakeRules" style="display:none" disabled>Mulai Ujian Ulang</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const modal = document.getElementById('retakeRulesModal');
    const cancel = document.getElementById('cancelRetakeRules');
    const prev = document.getElementById('prevRetakeRules');
    const next = document.getElementById('nextRetakeRules');
    const accept = document.getElementById('acceptRetakeRules');
    const confirmCheck = document.getElementById('retakeRulesConfirmCheck');
    const examName = document.getElementById('retakeRulesExamName');
    const attemptLabel = document.getElementById('retakeAttemptLabel');
    const stepLabel = document.getElementById('retakeRulesStep');
    const title = document.getElementById('retakeRulesTitle');
    const slides = Array.from(document.querySelectorAll('#retakeRulesModal .retake-rules-slide'));
    const progress = Array.from(document.querySelectorAll('#retakeRulesModal .retake-rules-progress span'));
    const titles = ['Pengawasan Ujian', 'Ketentuan Umum Ujian', 'Siap Memulai Ulang'];
    let selectedForm = null;
    let current = 0;

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
        if (accept) accept.disabled = true;
        if (selectedForm) {
            const input = selectedForm.querySelector('.retake-rules-completed');
            if (input) input.value = '0';
        }
        render();
    };

    document.querySelectorAll('.open-retake-rules').forEach(button => {
        button.addEventListener('click', () => {
            selectedForm = button.closest('.retake-form');
            if (!selectedForm || !modal) return;
            if (examName) examName.textContent = selectedForm.dataset.examTitle || 'Ujian';
            if (attemptLabel) attemptLabel.textContent = 'Ujian ke-' + (selectedForm.dataset.nextAttempt || '?');
            reset();
            modal.classList.add('show');
        });
    });

    cancel?.addEventListener('click', () => {
        modal?.classList.remove('show');
        selectedForm = null;
    });
    prev?.addEventListener('click', () => { if (current > 0) { current--; render(); } });
    next?.addEventListener('click', () => { if (current < slides.length - 1) { current++; render(); } });
    confirmCheck?.addEventListener('change', () => {
        if (accept) accept.disabled = !confirmCheck.checked;
    });
    accept?.addEventListener('click', () => {
        if (!selectedForm || !confirmCheck?.checked) return;
        const input = selectedForm.querySelector('.retake-rules-completed');
        if (input) input.value = '1';
        accept.disabled = true;
        accept.textContent = 'Memulai...';
        selectedForm.submit();
    });
    render();
})();
</script>
@endpush
