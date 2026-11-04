<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title','CBT Campus')</title><link rel="stylesheet" href="{{ asset('css/app.css') }}"></head>
<body>
@if(auth()->check())
<div class="shell">
 <aside class="sidebar">
  <div class="brand"><span class="brand-badge">CB</span><div><strong>CBT Campus</strong><small>Laravel 10</small></div></div>
  <div class="userbox">
   <div class="avatar">
    @if(auth()->user()->profile_photo_path)
     <img src="{{ asset('storage/'.auth()->user()->profile_photo_path) }}" alt="Foto {{ auth()->user()->name }}">
    @else
     {{ strtoupper(substr(auth()->user()->name,0,1)) }}
    @endif
   </div>
   <div><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->role==='admin'?'Admin':(auth()->user()->role==='lecturer'?'Dosen':'Mahasiswa') }}</small></div>
  </div>
  <nav>
   @if(auth()->user()->role==='admin')
    <a class="{{ request()->routeIs('admin.*')?'active':'' }}" href="{{ route('admin.dashboard') }}">⌂ Dashboard</a>
    <a class="{{ request()->routeIs('admin.*')?'active':'' }}" href="{{ route('admin.dashboard') }}#users">♙ Pengguna & Role</a>
   @elseif(auth()->user()->role==='lecturer')
    <a class="{{ request()->routeIs('lecturer.dashboard')?'active':'' }}" href="{{ route('lecturer.dashboard') }}">⌂ Dashboard</a>
    <a class="{{ request()->routeIs('lecturer.courses.*')?'active':'' }}" href="{{ route('lecturer.courses.index') }}">▣ Mata Kuliah</a>
    <a class="{{ request()->routeIs('lecturer.classes.*')?'active':'' }}" href="{{ route('lecturer.classes.index') }}">♙ Kelas</a>
    <a class="{{ request()->routeIs('lecturer.exams.*')?'active':'' }}" href="{{ route('lecturer.exams.index') }}">◫ Ujian</a>
    <a class="{{ request()->routeIs('lecturer.profile.*')?'active':'' }}" href="{{ route('lecturer.profile.edit') }}">♙ Edit Profil</a>
   @else
    <a class="{{ request()->routeIs('student.dashboard')?'active':'' }}" href="{{ route('student.dashboard') }}">⌂ Dashboard</a>
    <a class="{{ request()->routeIs('student.history')?'active':'' }}" href="{{ route('student.history') }}">↺ Riwayat Ujian</a>
    <a class="{{ request()->routeIs('student.profile.*')?'active':'' }}" href="{{ route('student.profile.edit') }}">♙ Edit Profil</a>
   @endif
  </nav>
  <form class="logout" action="{{ route('logout') }}" method="post">@csrf<button type="submit">↪ Logout</button></form>
 </aside>
 <main class="main"><header class="topbar"><div class="topbar-left">
  @hasSection('back-url')
   <a class="back-btn" href="@yield('back-url')" @hasSection('back-confirm') onclick="return confirm('@yield('back-confirm')')" @endif>← @yield('back-label','Kembali')</a>
  @endif
  <h1>@yield('page-title','CBT Campus')</h1><p>@yield('page-subtitle')</p></div><div class="date">{{ now()->translatedFormat('d M Y') }}</div></header>
  @if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
  @if($errors->any())<div class="alert danger"><strong>Periksa kembali:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
  @yield('content')
 </main>
</div>
@else @yield('content') @endif
@if(auth()->check() && auth()->user()->role==='lecturer')
<script>window.CBTLecturerMonitorUrl = @json(route('lecturer.monitoring.tab-notifications'));</script>
<script src="{{ asset('js/lecturer-monitor.js') }}"></script>
@endif
@stack('scripts')
</body></html>
