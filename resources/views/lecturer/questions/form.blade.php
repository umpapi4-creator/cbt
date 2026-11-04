@extends('layouts.app')
@section('back-url', route('lecturer.exams.questions',$exam))
@section('back-label','Kelola Soal')
@section('title',$question->exists?'Edit Soal':'Tambah Soal')
@section('page-title',$question->exists?'Edit Soal':'Tambah Soal')
@section('page-subtitle',$exam->title.' · '.$exam->course->name)
@section('content')
@php $opts=$question->exists?$question->options->keyBy('label'):collect(); @endphp
<form method="post" enctype="multipart/form-data" action="{{ $question->exists ? route('lecturer.exams.questions.update',[$exam,$question]) : route('lecturer.exams.questions.store',$exam) }}" class="card form-card">
    @csrf
    @if($question->exists) @method('PUT') @endif

    <div class="exam-info" style="margin-bottom:18px">
        <span><small>Ujian</small>{{ $exam->title }}</span>
        <span><small>Mata Kuliah</small>{{ $exam->course->code }} - {{ $exam->course->name }}</span>
    </div>

    <div class="form-grid">
        <div><label>Tingkat Kesulitan</label><select name="difficulty"><option value="easy" @selected(old('difficulty',$question->difficulty)=='easy')>Mudah</option><option value="medium" @selected(old('difficulty',$question->difficulty?:'medium')=='medium')>Sedang</option><option value="hard" @selected(old('difficulty',$question->difficulty)=='hard')>Sulit</option></select></div>
        <div class="span-2"><label>Pertanyaan</label><textarea name="text" rows="4" required>{{ old('text',$question->text) }}</textarea></div>
        <div class="span-2"><label>Gambar soal (opsional)</label><input type="file" name="image" accept="image/*">@if($question->image)<small class="field-help">Sudah ada gambar. Upload baru hanya jika ingin menggantinya.</small>@endif</div>
    </div>

    <h3 class="section-title">Pilihan Jawaban</h3>
    <p class="field-help">Klik lingkaran huruf untuk menentukan satu jawaban yang benar.</p>
    <div class="options-editor">
        @foreach(['A','B','C','D','E'] as $label)
            <div class="option-row">
                <label class="radio-correct"><input type="radio" name="correct" value="{{ $label }}" @checked(old('correct', optional($opts->firstWhere('is_correct',true))->label)==$label) required><span>{{ $label }}</span></label>
                <input name="option_{{ $label }}" value="{{ old('option_'.$label, optional($opts->get($label))->text) }}" placeholder="Pilihan {{ $label }}" required>
            </div>
        @endforeach
    </div>
    <label>Pembahasan (opsional)</label>
    <textarea name="explanation" rows="3">{{ old('explanation',$question->explanation) }}</textarea>
    <div class="form-actions">
        <a class="btn ghost" href="{{ route('lecturer.exams.questions',$exam) }}">Batal</a>
        <button class="btn primary">{{ $question->exists?'Simpan Perubahan':'Tambah Soal' }}</button>
    </div>
</form>
@endsection
