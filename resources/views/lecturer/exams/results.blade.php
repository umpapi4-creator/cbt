@extends('layouts.app')
@section('back-url', route('lecturer.exams.index'))
@section('back-label','Daftar Ujian')
@section('title','Hasil Ujian')
@section('page-title','Hasil Ujian')
@section('page-subtitle',$exam->title.' · '.$exam->classroom->name)
@section('content')
@php
    $studentGroups = $exam->attempts->groupBy('student_id');
    $permissions = $exam->retakePermissions->keyBy('student_id');
    $submittedAttempts = $exam->attempts->where('status','submitted');
@endphp

<div class="stats">
    <div class="stat"><span class="ico blue">♙</span><div><small>Peserta</small><strong>{{ $studentGroups->count() }}</strong></div></div>
    <div class="stat"><span class="ico green">↺</span><div><small>Total Percobaan</small><strong>{{ $exam->attempts->count() }}</strong></div></div>
    <div class="stat"><span class="ico orange">✓</span><div><small>Percobaan Selesai</small><strong>{{ $submittedAttempts->count() }}</strong></div></div>
    <div class="stat"><span class="ico purple">!</span><div><small>Pelanggaran Tab</small><strong>{{ $exam->attempts->sum('tab_switch_count') }}</strong></div></div>
    <div class="stat"><span class="ico blue">↪</span><div><small>Logout Saat Ujian</small><strong>{{ $exam->attempts->sum('logout_count') }}</strong></div></div>
</div>

<div class="card monitoring-info-card">
    <div class="monitoring-info-icon">!</div>
    <div>
        <strong>Riwayat percobaan & pengulangan ujian</strong>
        <p>Dari kolom Riwayat Ujian, dosen dapat mengaktifkan izin ulang tanpa batas untuk mahasiswa tertentu. Setiap pengulangan disimpan sebagai Ujian ke-1, ke-2, ke-3, dan seterusnya; nilai lama tidak dihapus.</p>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <div>
            <h3>Nilai Peserta</h3>
            <p>Semua percobaan dan riwayat meninggalkan tab tersimpan terpisah.</p>
        </div>
        <span class="badge soft">Kode: {{ $exam->token }}</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Mahasiswa</th>
                    <th>Percobaan Terakhir</th>
                    <th>Riwayat Ujian</th>
                    <th>Pengawasan Terakhir</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            @forelse($studentGroups as $studentId => $attempts)
                @php
                    $attempts = $attempts->sortBy('attempt_number')->values();
                    $latest = $attempts->last();
                    $student = $latest->student;
                    $permission = $permissions->get($studentId);
                    $retakeEnabled = (bool)($permission?->is_enabled);
                @endphp
                <tr class="{{ $latest->terminated_for_tab_switch ? 'terminated-row' : '' }}">
                    <td>
                        <strong>{{ $student->name }}</strong>
                        <small>{{ $student->student_number ?: $student->username }}</small>
                    </td>
                    <td>
                        <strong>Ujian ke-{{ $latest->attempt_number }}</strong>
                        @if($latest->status === 'submitted')
                            <small>Nilai: <strong>{{ number_format((float)$latest->score,2) }}</strong></small>
                            <small>{{ $latest->submitted_at?->format('d/m/Y H:i:s') }}</small>
                        @else
                            <small>Sedang berlangsung sejak {{ $latest->started_at?->format('d/m/Y H:i:s') }}</small>
                        @endif
                    </td>
                    <td class="history-cell">
                        <details class="attempt-history" {{ $attempts->count() > 1 ? 'open' : '' }}>
                            <summary>Riwayat {{ $attempts->count() }} percobaan</summary>
                            <div class="attempt-history-list">
                                @foreach($attempts as $a)
                                    <div class="attempt-history-item {{ $a->terminated_for_tab_switch ? 'is-terminated' : '' }}">
                                        <div class="attempt-history-title">
                                            <strong>Ujian ke-{{ $a->attempt_number }}</strong>
                                            @if($a->status === 'in_progress')
                                                <span class="badge draft">Berlangsung</span>
                                            @elseif($a->terminated_for_tab_switch)
                                                <span class="badge violation-critical">Dihentikan</span>
                                            @elseif((float)$a->score >= (float)$exam->passing_score)
                                                <span class="badge published">Lulus</span>
                                            @else
                                                <span class="badge completed">Belum Lulus</span>
                                            @endif
                                        </div>
                                        <div class="attempt-score-line">
                                            @if($a->status === 'submitted')
                                                <span>Nilai <strong>{{ number_format((float)$a->score,2) }}</strong></span>
                                                <span>Benar {{ $a->correct_count }}</span>
                                                <span>Salah {{ $a->wrong_count }}</span>
                                                <span>Kosong {{ $a->unanswered_count }}</span>
                                            @else
                                                <span>Belum diselesaikan</span>
                                            @endif
                                        </div>
                                        @if($a->logout_count > 0)
                                            <small class="violation-warning">Logout saat ujian: {{ $a->logout_count }} kali</small>
                                        @endif

                                        <small>
                                            Mulai {{ $a->started_at?->format('d/m/Y H:i:s') }}
                                            @if($a->submitted_at) · Selesai {{ $a->submitted_at->format('d/m/Y H:i:s') }} @endif
                                        </small>

                                        @if($a->tabEvents->where('event_type', 'visibility_hidden')->isNotEmpty())
                                            <details class="violation-history compact-violation-history">
                                                <summary>Riwayat meninggalkan tab ({{ $a->tabEvents->where('event_type', 'visibility_hidden')->count() }})</summary>
                                                <ol>
                                                    @foreach($a->tabEvents->where('event_type', 'visibility_hidden') as $event)
                                                        <li>
                                                            <strong>Peringatan {{ $event->count_after }}/3</strong>
                                                            <span>{{ $event->occurred_at->format('d/m/Y H:i:s') }}</span>
                                                        </li>
                                                    @endforeach
                                                </ol>
                                            </details>
                                        @else
                                            <small class="no-violation">Tidak ada pelanggaran tab pada percobaan ini.</small>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </details>

                        @if($attempts->where('status','submitted')->isNotEmpty())
                            <form method="post" action="{{ route('lecturer.exams.retake-permission',[$exam,$student]) }}" class="retake-permission-form">
                                @csrf
                                @if($retakeEnabled)
                                    <div class="retake-enabled-note">
                                        <span class="badge published">Ulang Tanpa Batas Aktif</span>
                                        <small>Mahasiswa dapat membuat percobaan baru kapan pun dari tab Riwayat Ujian.</small>
                                    </div>
                                    <button class="btn danger small" type="submit" onclick="return confirm('Nonaktifkan izin mengulang untuk mahasiswa ini? Riwayat yang sudah ada tidak akan dihapus.')">Nonaktifkan Izin Ulang</button>
                                @else
                                    <button class="btn success small" type="submit" onclick="return confirm('Izinkan mahasiswa ini mengulang ujian tanpa batas?')">↺ Izinkan Ulang Tanpa Batas</button>
                                @endif
                            </form>
                        @endif
                    </td>
                    <td>
                        @if((int)$latest->tab_switch_count === 0)
                            <span class="badge soft">0/3</span>
                            <small>Tidak ada pelanggaran</small>
                        @elseif($latest->terminated_for_tab_switch)
                            <span class="badge violation-critical">3/3 · Dihentikan</span>
                        @else
                            <span class="badge violation-warning">{{ $latest->tab_switch_count }}/3</span>
                        @endif
                    </td>
                    <td>
                        @if($latest->status !== 'submitted')
                            <span class="badge draft">Berlangsung</span>
                        @elseif((float)$latest->score >= (float)$exam->passing_score)
                            <span class="badge published">Lulus</span>
                        @else
                            <span class="badge completed">Belum Lulus</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">Belum ada mahasiswa yang memulai ujian.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
