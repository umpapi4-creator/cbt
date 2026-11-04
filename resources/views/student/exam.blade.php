@extends('layouts.app')
@section('back-url', route('student.dashboard'))
@section('back-label','Dashboard')
@section('back-confirm','Keluar dari halaman ujian akan tercatat sebagai meninggalkan tab/halaman ujian. Yakin ingin keluar?')
@section('title',$attempt->exam->title)
@section('page-title',$attempt->exam->title)
@section('page-subtitle',$attempt->exam->course->name.' · Ujian ke-'.$attempt->attempt_number)
@section('content')
<div class="exam-shell"
     data-deadline="{{ $attempt->deadline_at->timestamp }}"
     data-attempt="{{ $attempt->id }}"
     data-violation-url="{{ route('student.attempts.tab-violation',$attempt) }}"
     data-login-url="{{ route('login') }}"
     data-violation-count="{{ (int)$attempt->tab_switch_count }}">
    <aside class="exam-nav">
        <div class="timer-box">
            <small>Sisa Waktu</small>
            <strong id="timer">--:--:--</strong>
        </div>
        <div class="monitor-box">
            <div>
                <small>Pengawasan Tab</small>
                <strong id="violationCounter">{{ (int)$attempt->tab_switch_count }}/3</strong>
            </div>
            <span class="monitor-dot"></span>
        </div>
        <p class="monitor-note">Jangan pindah tab atau meminimalkan browser selama ujian.</p>
        <h4>Daftar Soal</h4>
        <div class="legend"><span><i class="dot done"></i>Terjawab</span><span><i class="dot blank"></i>Belum</span></div>
        <div class="number-grid">
            @foreach($attempt->question_order as $i=>$qid)
                <button type="button" class="num-btn {{ isset($answers[$qid]) && $answers[$qid]->selected_option_id?'answered':'' }}" data-go="{{ $i }}">{{ $i+1 }}</button>
            @endforeach
        </div>
    </aside>

    <section class="exam-content">
        @foreach($attempt->question_order as $i=>$qid)
            @php
                $q=$questions[$qid];
                $order=$attempt->option_orders[$qid]??$q->options->pluck('id')->all();
                $optionMap=$q->options->keyBy('id');
                $selected=optional($answers->get($qid))->selected_option_id;
            @endphp
            <div class="question-slide {{ $i===0?'active':'' }}" data-index="{{ $i }}">
                <div class="q-head">
                    <span>Soal {{ $i+1 }} dari {{ count($attempt->question_order) }}</span>
                    <span class="save-status" id="save-{{ $qid }}">Tersimpan</span>
                </div>
                <h2>{{ $q->text }}</h2>
                @if($q->image)<img class="q-image" src="{{ asset('storage/'.$q->image) }}">@endif
                <div class="answer-list">
                    @foreach($order as $oid)
                        @php $o=$optionMap[$oid]; @endphp
                        <label class="answer-option">
                            <input type="radio" name="q{{ $qid }}" value="{{ $o->id }}" data-question="{{ $qid }}" @checked($selected==$o->id)>
                            <span class="answer-letter">{{ $o->label }}</span>
                            <span>{{ $o->text }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="exam-actions">
                    <button type="button" class="btn ghost prev" @disabled($i===0)>← Sebelumnya</button>
                    @if($i===count($attempt->question_order)-1)
                        <button type="button" class="btn success" id="finishBtn">Selesai & Kirim</button>
                    @else
                        <button type="button" class="btn primary next">Selanjutnya →</button>
                    @endif
                </div>
            </div>
        @endforeach
    </section>
</div>

<form id="submitExam" method="post" action="{{ route('student.attempts.submit',$attempt) }}">@csrf</form>

<div class="modal" id="confirmModal">
    <div class="modal-card">
        <h3>Kirim jawaban?</h3>
        <p>Setelah dikirim, jawaban tidak dapat diubah lagi.</p>
        <div class="form-actions">
            <button type="button" class="btn ghost" id="cancelFinish">Batal</button>
            <button type="button" class="btn success" id="confirmFinish">Ya, Kirim Ujian</button>
        </div>
    </div>
</div>

<div class="modal" id="violationModal">
    <div class="modal-card violation-modal-card">
        <div class="violation-icon">!</div>
        <h3 id="violationTitle">Peringatan Pengawasan</h3>
        <p id="violationMessage">Anda terdeteksi meninggalkan tab ujian.</p>
        <div class="violation-progress"><span id="violationProgress"></span></div>
        <button type="button" class="btn primary full" id="closeViolation">Saya Mengerti</button>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/exam.js') }}"></script>
@endpush
