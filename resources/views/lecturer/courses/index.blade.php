@extends('layouts.app')
@section('back-url', route('lecturer.dashboard'))
@section('back-label','Dashboard')
@section('title','Mata Kuliah')@section('page-title','Mata Kuliah')@section('page-subtitle','Kelola mata kuliah yang digunakan untuk bank soal dan ujian.')
@section('content')
<div class="grid-2"><div class="card"><div class="card-head"><h3>Tambah Mata Kuliah</h3></div><form method="post" action="{{ route('lecturer.courses.store') }}" class="form-grid">@csrf<div><label>Kode</label><input name="code" placeholder="MIK401" required></div><div><label>Semester</label><input type="number" name="semester" min="1" max="14" value="7" required></div><div class="span-2"><label>Nama Mata Kuliah</label><input name="name" placeholder="Klasifikasi dan Kodifikasi Penyakit" required></div><div class="span-2"><button class="btn primary">Simpan</button></div></form></div>
<div class="card"><div class="card-head"><h3>Daftar Mata Kuliah</h3></div><table><thead><tr><th>Kode</th><th>Nama</th><th>Semester</th><th></th></tr></thead><tbody>@forelse($courses as $course)<tr><td>{{ $course->code }}</td><td>{{ $course->name }}</td><td>{{ $course->semester }}</td><td><form method="post" action="{{ route('lecturer.courses.destroy',$course) }}" onsubmit="return confirm('Hapus mata kuliah?')">@csrf @method('DELETE')<button class="btn danger small">Hapus</button></form></td></tr>@empty<tr><td colspan="4" class="empty">Belum ada mata kuliah.</td></tr>@endforelse</tbody></table></div></div>
@endsection
