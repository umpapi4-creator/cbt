@extends('layouts.app')
@section('back-url', route('student.dashboard'))
@section('back-label','Dashboard')
@section('title','Hasil Ujian')
@section('page-title','Hasil Ujian')
@section('page-subtitle',$attempt->exam->title.' · Ujian ke-'.$attempt->attempt_number)
@section('content')
@if($attempt->terminated_for_tab_switch)
<div class="card termination-card">
    <div class="termination-icon">!</div>
    <div>
        <h3>Ujian dihentikan karena pelanggaran pengawasan</h3>
        <p>Anda tercatat meninggalkan tab ujian sebanyak <strong>{{ $attempt->tab_switch_count }}/3</strong>. Pada pelanggaran ke-3, sistem otomatis mengirim jawaban yang sudah tersimpan.</p>
        @if($attempt->tabEvents->isNotEmpty())
            <div class="student-violation-times">
                @foreach($attempt->tabEvents as $event)
                    <span>Peringatan {{ $event->count_after }} · {{ $event->occurred_at->format('H:i:s') }}</span>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endif

@if(!$attempt->exam->show_result)
<div class="card result-hidden">
    <div class="result-icon">✓</div>
    <h2>Ujian berhasil diselesaikan</h2>
    <p>Nilai disembunyikan oleh dosen dan akan diumumkan kemudian.</p>
    <div class="result-action-row">
        <a class="btn primary" href="{{ route('student.dashboard') }}">Kembali ke Dashboard</a>
        <a class="btn ghost" href="{{ route('student.history') }}">Riwayat Ujian</a>
        @if($retakeEnabled)
            <a class="btn success" href="{{ route('student.history') }}">↺ Ulangi Ujian</a>
        @endif
    </div>
</div>
@else
<div class="result-wrap">
    <div class="card result-main">
        <span class="course-code">{{ $attempt->exam->course->code }}</span>
        <h2>{{ $attempt->exam->title }}</h2>
        <p>{{ $attempt->exam->course->name }} · <strong>Ujian ke-{{ $attempt->attempt_number }}</strong></p>
        <div class="score-circle">
            <small>Nilai Anda</small>
            <strong>{{ number_format((float)$attempt->score,2) }}</strong>
            <span class="{{ $attempt->score >= $attempt->exam->passing_score?'pass':'fail' }}">{{ $attempt->score >= $attempt->exam->passing_score?'LULUS':'BELUM LULUS' }}</span>
        </div>
        <div class="result-stats">
            <div><small>Jumlah Soal</small><strong>{{ $attempt->correct_count+$attempt->wrong_count+$attempt->unanswered_count }}</strong></div>
            <div><small>Benar</small><strong>{{ $attempt->correct_count }}</strong></div>
            <div><small>Salah</small><strong>{{ $attempt->wrong_count }}</strong></div>
            <div><small>Tidak Dijawab</small><strong>{{ $attempt->unanswered_count }}</strong></div>
        </div>
        <div class="result-action-row">
            <a class="btn primary" href="{{ route('student.dashboard') }}">Kembali ke Dashboard</a>
            <a class="btn ghost" href="{{ route('student.history') }}">Riwayat Ujian</a>
            @if($retakeEnabled)
                <a class="btn success" href="{{ route('student.history') }}">↺ Ulangi Ujian</a>
            @endif
        </div>
    </div>
</div>
@endif
@endsection
