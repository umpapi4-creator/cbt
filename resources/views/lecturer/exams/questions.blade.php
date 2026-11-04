@extends('layouts.app')
@section('back-url', route('lecturer.exams.index'))
@section('back-label','Daftar Ujian')
@section('title','Kelola Soal')
@section('page-title','Kelola Soal Ujian')
@section('page-subtitle',$exam->title.' · '.$exam->course->name)
@section('content')
<div class="card">
    <div class="card-head">
        <div>
            <h3>Soal Ujian</h3>
            <p>Semua soal di bawah ini langsung menjadi bagian dari ujian <b>{{ $exam->title }}</b>. Tidak ada bank soal terpisah.</p>
        </div>
        <div class="actions">
            <a class="btn ghost" href="{{ route('lecturer.exams.edit',$exam) }}">Edit Ujian</a>
            <a class="btn ghost" href="{{ route('lecturer.exams.questions.import',$exam) }}">⇧ Import Soal</a>
            <a class="btn primary" href="{{ route('lecturer.exams.questions.create',$exam) }}">+ Tambah Soal</a>
        </div>
    </div>

    <div class="exam-info" style="margin-bottom:18px">
        <span><small>Mata Kuliah</small>{{ $exam->course->code }} - {{ $exam->course->name }}</span>
        <span><small>Jumlah Soal</small>{{ $questions->count() }} soal</span>
        <span><small>Kode Ujian</small>{{ $exam->token }}</span>
    </div>

    <div class="question-list">
        @forelse($questions as $q)
            <div class="question-item">
                <div class="q-num">{{ $loop->iteration }}</div>
                <div class="q-body">
                    <div class="q-meta"><span class="badge soft">{{ ucfirst($q->difficulty) }}</span></div>
                    <h4>{{ $q->text }}</h4>
                    @if($q->image)<img class="q-image" src="{{ \App\Services\UploadStorage::url($q->image) }}" alt="Gambar soal">@endif
                    <div class="option-mini">
                        @foreach($q->options as $o)
                            <span class="{{ $o->is_correct?'correct':'' }}"><b>{{ $o->label }}.</b> {{ $o->text }} @if($o->is_correct) ✓@endif</span>
                        @endforeach
                    </div>
                </div>
                <div class="actions">
                    <a class="btn ghost small" href="{{ route('lecturer.exams.questions.edit',[$exam,$q]) }}">Edit</a>
                    <form method="post" action="{{ route('lecturer.exams.questions.destroy',[$exam,$q]) }}" onsubmit="return confirm('Hapus soal ini dari ujian?')">
                        @csrf @method('DELETE')
                        <button class="btn danger small">Hapus</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="empty">
                <strong>Belum ada soal pada ujian ini.</strong><br>
                Klik <b>Tambah Soal</b> untuk input manual atau <b>Import Soal</b> untuk memasukkan banyak soal sekaligus.
            </div>
        @endforelse
    </div>
</div>
@endsection
