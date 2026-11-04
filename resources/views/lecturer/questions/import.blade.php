@extends('layouts.app')
@section('back-url', route('lecturer.exams.questions',$exam))
@section('back-label','Kelola Soal')
@section('title','Import Soal')
@section('page-title','Import Soal Otomatis')
@section('page-subtitle',$exam->title.' · semua soal hasil import langsung masuk ke ujian ini.')
@section('content')
<div class="grid-2 import-layout">
    <form method="post" action="{{ route('lecturer.exams.questions.import.store',$exam) }}" enctype="multipart/form-data" class="card">
        @csrf
        <div class="card-head"><div><h3>Upload File Soal</h3><p>DOCX atau TXT · maksimal 5 MB. CSV lama tetap didukung.</p></div></div>
        <div class="exam-info" style="margin-bottom:18px">
            <span><small>Ujian Tujuan</small>{{ $exam->title }}</span>
            <span><small>Mata Kuliah</small>{{ $exam->course->code }} - {{ $exam->course->name }}</span>
        </div>
        <div style="margin-bottom:16px">
            <label>File Soal</label>
            <input type="file" name="file" accept=".docx,.txt,.csv" required>
            <small class="field-help">Semua soal yang valid akan langsung menjadi soal ujian ini.</small>
        </div>
        <div class="import-warning">Sistem memeriksa seluruh file lebih dahulu. Jika ada satu soal tidak lengkap, import dibatalkan agar data tidak setengah masuk.</div>
        <div class="form-actions">
            <a class="btn ghost" href="{{ route('lecturer.exams.questions',$exam) }}">Kembali</a>
            <button class="btn primary">Import Soal</button>
        </div>
    </form>

    <div class="card import-guide">
        <div class="card-head"><div><h3>Format Word / TXT</h3><p>Copy format berikut ke Word lalu simpan sebagai <b>.docx</b>.</p></div></div>
        <pre class="format-example">1. Kode ICD-10 untuk hipertensi esensial adalah?

*A. I10
B. I11.9
C. I12.9
D. I15
E. I20.9

2. Diabetes melitus tipe 2 tanpa komplikasi dikodekan sebagai?

A. E10.9
*B. E11.9
C. E12.9
D. E13.9
E. E14.9</pre>
        <p class="field-help"><b>Cukup beri tanda * pada pilihan yang benar.</b> Tidak perlu menulis Jawaban, Pembahasan, atau Kesulitan. Setiap soal tetap harus memiliki pilihan A–E.</p>
        <div class="import-warning" style="margin-top:12px">Contoh: <b>*C. Jawaban benar</b>. Hanya boleh ada satu opsi bertanda * pada setiap soal.</div>
        <a class="btn ghost" href="{{ asset('templates/template_import_soal.txt') }}" download>↓ Download Template TXT</a>
    </div>
</div>
@endsection
