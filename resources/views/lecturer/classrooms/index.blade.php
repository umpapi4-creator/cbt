@extends('layouts.app')
@section('back-url', route('lecturer.dashboard'))
@section('back-label','Dashboard')
@section('title','Kelas')
@section('page-title','Kelas Mahasiswa')
@section('page-subtitle','Buat kelas, lalu tambahkan dan kelola data mahasiswa pada masing-masing kelas.')

@section('content')
<div class="grid-2">
    <div class="card">
        <div class="card-head">
            <div>
                <h3>Tambah Kelas</h3>
                <p>Mahasiswa ditambahkan setelah kelas berhasil dibuat.</p>
            </div>
        </div>

        <form method="post" action="{{ route('lecturer.classes.store') }}">
            @csrf
            <label>Nama Kelas</label>
            <input name="name" value="{{ old('name') }}" placeholder="Contoh: D4 MIK 7A" required>

            <label style="margin-top:14px">Tahun Akademik</label>
            <input name="academic_year" value="{{ old('academic_year','2026/2027') }}" required>

            <button class="btn primary" style="margin-top:16px">+ Buat Kelas</button>
        </form>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h3>Daftar Kelas</h3>
                <p>Klik Kelola Mahasiswa untuk menambah atau menghapus anggota kelas.</p>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Kelas</th>
                        <th>TA</th>
                        <th>Mahasiswa</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($classes as $class)
                        <tr>
                            <td><strong>{{ $class->name }}</strong></td>
                            <td>{{ $class->academic_year }}</td>
                            <td><span class="badge soft">{{ $class->students_count }} mahasiswa</span></td>
                            <td>
                                <div class="actions">
                                    <a class="btn primary small" href="{{ route('lecturer.classes.show',$class) }}">Kelola Mahasiswa</a>
                                    <form method="post" action="{{ route('lecturer.classes.destroy',$class) }}" onsubmit="return confirm('Hapus kelas {{ addslashes($class->name) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn danger small">Hapus Kelas</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">Belum ada kelas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
