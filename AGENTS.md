# Project Overview

CBT Campus adalah aplikasi Computer Based Test untuk Admin, Dosen, dan Mahasiswa. Fitur utamanya mencakup pengelolaan mata kuliah/kelas/ujian/soal, import soal, pengerjaan dan penilaian otomatis, riwayat/ujian ulang, serta monitoring perpindahan tab.

# Technology Stack

- PHP 8.1 (terpasang: 8.1.10; composer platform: 8.1.25)
- Laravel 10 (terpasang: 10.50.3)
- Database default: MySQL melalui konfigurasi `.env`
- Server-rendered frontend: Blade
- CSS/JavaScript: asset langsung di `public/css` dan `public/js`
- Dependency utama: `laravel/framework`, `laravel/tinker`, Guzzle; dikelola Composer
- Tidak ditemukan `package.json`, Vite, atau Laravel Mix

# Architecture

Laravel monolith: route di `routes/web.php` -> middleware `auth` dan `role` -> controller berdasarkan role -> Eloquent model/migration -> database MySQL -> Blade view. API hanya memiliki endpoint `/api/ping`. Auth menggunakan session guard dan role `admin`, `lecturer`, atau `student`.

Data akademik Dosen dipisahkan dengan `lecturer_id`. Relasi utama meliputi Course, Classroom, Exam, Question, ExamAttempt, jawaban, event perpindahan tab, dan izin ujian ulang. JavaScript di `public/js/exam.js` menangani autosave, timer, submit, dan monitoring tab; `lecturer-monitor.js` melakukan polling notifikasi.

# Important Project Rules

1. Jangan upgrade framework atau runtime/bahasa tanpa diminta.
2. Jangan mengubah fitur yang tidak berkaitan dengan request atau menghapus fitur lama yang masih digunakan.
3. Jangan mengubah `.env`, `vendor/`, atau `node_modules/`.
4. Jangan menjalankan command destructive tanpa izin, termasuk migrate, operasi database/reset, dan penghapusan data massal.
5. Sebelum mengubah fitur, telusuri route, middleware, controller, model, migration/database, view, JS, dan CSS yang relevan.
6. Gunakan pola dan struktur yang sudah ada; reuse kode/komponen/helper dan hindari duplikasi tabel, kolom, route, controller, atau modul.
7. Lakukan perubahan seminimal mungkin dan pertahankan kompatibilitas versi saat ini.
8. Jika diminta tidak mengubah bagian lain, pertahankan seluruh bagian yang tidak terkait.

# Database Rules

- Periksa migration lama, model, relationship, foreign key, nama tabel, dan nama kolom sebelum membuat perubahan.
- Jangan membuat duplicate table/column atau menghapus data user tanpa izin.
- Gunakan perubahan database yang aman dan minimal.

# UI Rules

- Pertahankan style, layout, komponen existing, dan responsive layout.
- Jangan mengganti keseluruhan UI untuk perubahan kecil.

# Debugging Rules

- Cari root cause sebelum memperbaiki error; jangan sekadar menyembunyikannya.
- Periksa dampak perubahan terhadap fitur lain dan gunakan perubahan terkecil yang menyelesaikan masalah.

# Verification

Command yang relevan:

```bash
php artisan --version
php artisan route:list
php artisan optimize
php artisan test
```

Saat menguji perubahan database, minta izin terlebih dahulu dan periksa migration/schema yang sudah ada sebelum menjalankan command database.
