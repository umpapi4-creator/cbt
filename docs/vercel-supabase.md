# Deploy CBT Campus ke Vercel + Supabase

Proyek tetap Laravel 10 / PHP 8.1. Vercel memakai runtime komunitas
`vercel-php@0.5.5` (PHP 8.1), bukan runtime PHP resmi Vercel.
MySQL lokal tetap tersedia; `.env` lokal tidak perlu diganti.

## 1. Siapkan Supabase

1. Buat project dan simpan password database.
2. Buka **Connect → Session pooler**. Salin host, port, database, dan username
   persis dari dashboard. Session pooler mendukung IPv4; umumnya port 5432.
3. Buat bucket Storage **privat** bernama `cbt-uploads`. Batasi ukuran objek
   ke 2 MB dan MIME ke image/jpeg, image/png, image/webp, image/gif,
   image/bmp, image/avif sesuai format gambar yang dipakai.
4. Ambil service role key dari pengaturan API. Key hanya untuk server;
   jangan masukkan ke JavaScript, GitHub, atau chat.

Aplikasi tetap memakai login Laravel, bukan Supabase Auth. Gambar dibaca
melalui URL bertanda tangan yang berlaku 24 jam. Orang yang memperoleh URL
tersebut dapat membaca gambar selama URL masih berlaku.

## 2. Environment variables di Vercel

Import repository GitHub sebagai project Vercel. Pilih framework **Other**,
root directory repository ini. Biarkan Build Command dan Install Command
default runtime PHP; output directory `public` sudah diatur di `vercel.json`.
Tidak perlu npm build karena CSS/JS sudah berada di public.

Tambahkan nilai berikut melalui **Settings → Environment Variables**:

```dotenv
APP_NAME="CBT Campus"
APP_KEY=base64:ISI_APP_KEY
APP_URL=https://nama-project.vercel.app
APP_TIMEZONE=Asia/Jakarta
DB_HOST=HOST_SESSION_POOLER
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.PROJECT_REFERENCE
DB_PASSWORD=PASSWORD_DATABASE
SUPABASE_URL=https://PROJECT_REFERENCE.supabase.co
SUPABASE_SERVICE_ROLE_KEY=KEY_SERVER
SUPABASE_STORAGE_BUCKET=cbt-uploads
```

Gunakan APP_KEY yang tetap untuk semua deploy. Untuk instalasi baru,
`php artisan key:generate --show` menghasilkan key tanpa mengubah `.env`.
Jangan tambahkan DATABASE_URL jika menggunakan variabel DB_* di atas.
Jangan upload `.env` atau cache konfigurasi lokal.

`vercel.json` sudah memilih production, debug off, HTTPS cookie, session
database, log stderr, cache array, queue sync, PostgreSQL SSL require,
dan Supabase Storage. Cache array hanya bertahan selama satu request.
Blade dikompilasi di `/tmp`; session dan upload tidak disimpan di `/tmp`.

## 3. Membuat schema (langkah manual, perlu persetujuan pemilik database)

### Pilihan A: SQL Editor Supabase (tanpa PHP CLI)

Untuk project Supabase baru yang belum berisi tabel aplikasi:

1. Buka **SQL Editor → New query**, pilih role **postgres**.
2. Salin seluruh isi [01-schema.sql](../database/supabase/01-schema.sql), lalu
   klik **Run**. Script membuat 14 tabel dalam satu transaksi, mengaktifkan
   RLS, dan mencatat 16 migration yang sudah diwakili schema tersebut.
3. Buat query baru dari [02-admin.sql](../database/supabase/02-admin.sql).
   Ganti `admin_email`, `admin_username`, dan `admin_password` di editor.
   Password minimal 12 karakter, maksimal 72 byte. Jika mengandung petik
   tunggal, tulis dua petik tunggal (`''`) dalam string SQL.
4. Klik **Run**, lalu login aplikasi memakai username/email dan password itu.
   Tidak ada akun demo atau password bawaan pada jalur SQL ini.
5. Buat bucket privat `cbt-uploads` melalui Storage seperti langkah 1 di atas,
   lalu isi environment variables Vercel dan redeploy.

Jalankan keseluruhan file, bukan beberapa baris terpilih. Jangan jalankan
01-schema.sql pada database yang sudah mempunyai tabel aplikasi. Jika muncul
`relation already exists`, jangan hapus tabel; periksa schema existing dulu.
Jika transaksi gagal dan editor menyatakan transaksi aborted, jalankan
`ROLLBACK;` sebelum mencoba query yang sudah diperbaiki.

SQL dan Artisan di bawah adalah **dua alternatif instalasi**, bukan langkah
yang dijalankan berurutan. Setelah memakai SQL, jangan jalankan ulang migration
lama, `migrate:fresh`, `migrate:reset`, atau rollback batch baseline.
Migration baru ke depan tetap bisa dijalankan lewat Artisan sesudah review.
Tabel dilindungi dari Data API anon/authenticated; Laravel mengaksesnya lewat
koneksi database `postgres` (pemilik tabel). Ini tidak memakai Supabase Auth.
Script tidak mengimpor data dari MySQL lokal dan tidak membuat bucket Storage.

### Pilihan B: Artisan dari terminal lokal

Migration TIDAK dijalankan oleh build atau request website.
Periksa target database dan backup lebih dahulu. Gunakan database Supabase
baru/kosong, PHP CLI 8.1 dengan ekstensi pdo_pgsql, dan dependency Composer.
Di terminal PowerShell baru, set environment berikut untuk sesi terminal itu:

```powershell
$env:APP_ENV = 'production'
$env:DB_CONNECTION = 'pgsql'
$env:DB_HOST = 'HOST_SESSION_POOLER'
$env:DB_PORT = '5432'
$env:DB_DATABASE = 'postgres'
$env:DB_USERNAME = 'postgres.PROJECT_REFERENCE'
$env:DB_PASSWORD = 'PASSWORD_DATABASE'
$env:DB_SSLMODE = 'require'
```

Pastikan tidak ada bootstrap/cache/config.php dari environment lokal
sebelum menjalankan Artisan. Setelah target diperiksa dan disetujui:

```powershell
php artisan migrate --force
```

Tutup terminal setelah selesai agar konfigurasi database remote tidak ikut
terpakai oleh command lokal berikutnya. Jangan menjalankan migrate:fresh.
Migration existing membuat akun admin awal `tesadmin` / `tesadmin`.
Segera ubah password melalui terminal tepercaya **sebelum deploy publik**:
jalankan `php artisan tinker` pada sesi yang masih menarget Supabase, lalu:

```php
\App\Models\User::where('email', 'admin@cbt.test')->firstOrFail()->update(['password' => \Illuminate\Support\Facades\Hash::make('PASSWORD_KUAT_BARU')]);
```

Migration ini hanya membuat schema dan akun awal; data MySQL lokal tidak
otomatis dipindahkan. Migrasi data existing memerlukan proses terpisah,
termasuk sequence PostgreSQL dan file di storage/app/public. Salin file lama
ke bucket dengan path yang sama (profile_photos/... dan questions/...).

Untuk project yang hanya memakai backend Laravel, matikan Data API di
Supabase jika tidak dipakai. Jangan expose tabel ujian, jawaban, users, dan
sessions melalui Data API tanpa RLS/pembatasan grants yang sesuai. Gunakan
project terpisah untuk Preview agar tidak menulis ke database production.

## 4. Deploy dan cek

Deploy setelah schema dan password admin siap. Push berikutnya ke branch
production yang terhubung akan memicu deploy otomatis.

- Login, logout, refresh halaman: session harus tetap berlaku.
- Upload/ganti/hapus foto profil dan gambar soal; cek sesudah redeploy.
- Buat kelas, mata kuliah, ujian, dan import soal.
- Kerjakan ujian: autosave, timer, submit, skor, ujian ulang, monitoring tab.
- Pastikan /.env dan /composer.json tidak menampilkan isi file proyek.
- Jika gagal, baca Functions Logs Vercel; jangan menyalakan debug publik.

## Referensi

- https://github.com/vercel-community/php
- https://vercel.com/docs/functions/runtimes
- https://supabase.com/docs/guides/database/connecting-to-postgres
- https://supabase.com/docs/guides/storage

Runtime komunitas versi lama dipilih untuk mempertahankan PHP 8.1 sesuai
aturan proyek. Kompatibilitas build pada infrastruktur Vercel saat ini tetap
harus dibuktikan melalui deployment pertama; jangan menaikkan versi runtime
tanpa memeriksa kompatibilitas dependency dan persetujuan perubahan PHP.
