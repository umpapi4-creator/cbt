# CBT Campus v0.16

CBT berbasis **Laravel 10 + PHP 8.1 + MySQL** dengan tiga role: **Admin**, **Dosen**, dan **Mahasiswa**.

## Akun demo

- Admin: `tesadmin` / `tesadmin`
- Dosen: `tesdosen` / `tessdosen`
- Mahasiswa: `tes` / `tes`
- Kode ujian demo: `CBT123`

## Registrasi Mahasiswa

Pada halaman login tersedia tombol **Buat Akun Mahasiswa**.

Data yang diminta:

- Nama lengkap
- NIM
- Username
- Email
- Password
- Konfirmasi password

NIM, username, dan email harus unik. Setelah registrasi berhasil, mahasiswa langsung login dan diarahkan ke halaman **Masukkan Kode Ujian**.

> Pendaftaran publik hanya dapat membuat role `student`. Role dosen tidak dibaca dari form sehingga mahasiswa tidak dapat mengubah request untuk mendaftarkan dirinya sebagai dosen.

## Pemisahan Data Antar Dosen

Semua data akademik dosen disimpan dengan pemilik `lecturer_id`.

- Mata kuliah Dosen A hanya terlihat oleh Dosen A.
- Kelas Dosen A hanya dapat dikelola oleh Dosen A.
- Ujian Dosen A hanya dapat dibuka/diedit/dihapus oleh Dosen A.
- Soal pada ujian Dosen A hanya dapat ditambah/diedit/dihapus oleh Dosen A.
- Hasil peserta ujian Dosen A hanya dapat dilihat oleh Dosen A.
- Percobaan membuka URL milik dosen lain akan ditolak dengan HTTP 403.

Mahasiswa bersifat global: satu akun mahasiswa dapat mengikuti ujian dari dosen mana pun selama memiliki **kode ujian** yang valid.

## Alur Dosen

1. Buat **Mata Kuliah**.
2. Buat **Kelas**.
3. Buat **Ujian** dan atur jadwal, durasi, kode ujian, KKM, acak soal/pilihan, dan tampilan hasil.
4. Setelah ujian dibuat, sistem langsung membuka **Kelola Soal Ujian**.
5. Tambahkan soal manual atau gunakan **Import Soal**.
6. Mahasiswa memasukkan kode ujian.
7. Jadwal menentukan kapan ujian dapat dimulai.
8. Lihat nilai otomatis di menu **Hasil**.

Tidak ada menu bank soal terpisah. Soal langsung menjadi milik ujian tertentu.

## Alur Mahasiswa

1. Daftar akun dari halaman login atau gunakan akun yang sudah ada.
2. Login.
3. Dashboard tidak menampilkan daftar seluruh ujian.
4. Masukkan **Kode Ujian** dari dosen.
5. Jika kode ditemukan, detail ujian baru muncul.
6. Jika jadwal sudah dibuka dan soal tersedia, klik **Mulai Ujian**.
7. Setiap jawaban tersimpan otomatis.
8. Saat dikirim atau waktu habis, sistem menghitung skor otomatis: `benar / jumlah soal × 100`.

## Import Soal Word / TXT / CSV

Masuk ke **Ujian > Kelola Soal > Import Soal**. Untuk Word/TXT cukup beri tanda `*` pada pilihan yang benar:

```text
1. Kode ICD-10 untuk hipertensi esensial adalah?

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
E. E14.9
```

Setiap soal wajib memiliki pilihan A-E dan tepat **satu** pilihan bertanda `*`. Tidak perlu menulis `Jawaban:`, `Pembahasan:`, atau `Kesulitan:`. Format lama tetap dapat dibaca agar file sebelumnya tidak rusak.

## Instalasi Baru

```bash
composer install
copy .env.example .env
php artisan key:generate
```

Buat database MySQL `cbt_system`, lalu periksa `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cbt_system
DB_USERNAME=root
DB_PASSWORD=
```

Kemudian:

```bash
php artisan migrate --seed
php artisan storage:link
php artisan optimize:clear
php artisan serve
```

Buka `http://127.0.0.1:8000`.

## Upgrade dari v0.6

Tidak ada perubahan tabel database pada v0.7. Jika data dari v0.6 ingin dipertahankan, cukup timpa source code **kecuali file `.env` lama**, lalu jalankan:

```bash
composer dump-autoload
php artisan optimize:clear
php artisan serve
```

Tidak perlu `migrate:fresh` dan tidak perlu menghapus database.

## Skor Otomatis

Saat mahasiswa memilih jawaban, browser melakukan autosave ke server. Ketika tombol **Selesai & Kirim** ditekan, halaman menunggu autosave yang masih berjalan sebelum submit. Server menghitung jumlah benar, salah, kosong, dan nilai akhir.

## Tombol Kembali

Tombol **Kembali** tersedia pada halaman dosen dan mahasiswa yang relevan. Saat mahasiswa sedang mengerjakan ujian, tombol kembali meminta konfirmasi agar tidak keluar tanpa sengaja.


## v0.8 - Format Import Soal Sederhana
Untuk DOCX/TXT cukup tulis nomor soal, pilihan A-E, lalu beri tanda `*` di depan pilihan yang benar. Contoh: `*A. I10`. Tidak perlu baris `Jawaban:`, `Pembahasan:`, atau `Kesulitan:`. Format lama tetap didukung.

## v0.9 - Kelola Mahasiswa per Kelas

Menu **Kelas Mahasiswa** sekarang menggunakan alur:

1. Buat kelas.
2. Klik **Kelola Mahasiswa**.
3. Buat akun mahasiswa baru langsung pada kelas, atau masukkan akun mahasiswa yang sudah ada menggunakan NIM/username/email.
4. Gunakan **Keluarkan** untuk menghapus mahasiswa dari kelas tanpa menghapus akun.
5. Gunakan **Hapus Akun** untuk menghapus akun mahasiswa yang dibuat oleh dosen tersebut. Demi menjaga riwayat nilai, akun yang sudah mempunyai riwayat ujian tidak dapat dihapus permanen.

Upgrade dari v0.8:

```bash
php artisan migrate
php artisan optimize:clear
```

## v0.10 - Edit Profil Mahasiswa

Pada halaman **Kelola Mahasiswa**, daftar mahasiswa sekarang memiliki kolom **Edit Profil**. Dosen dapat mengubah nama, NIM, username, email, serta mereset password mahasiswa. Jika password baru dikosongkan, password lama tetap digunakan. Mengedit profil tidak menghapus riwayat ujian ataupun nilai mahasiswa.

Upgrade dari v0.9 tidak memerlukan migration baru. Timpa source code kecuali `.env`, kemudian jalankan:

```bash
composer dump-autoload
php artisan optimize:clear
php artisan serve
```


## v0.11 - Profil Mahasiswa & Admin Role
- Mahasiswa dapat mengedit profil sendiri dari menu **Edit Profil**.
- Registrasi publik selalu membuat akun dengan role `student`.
- Admin mengelola role melalui **Dashboard Admin > Pengguna & Role**.
- Admin hanya dapat memberikan role `student` atau `lecturer`; role `admin` tidak tersedia pada form.
- Saat akun dijadikan Dosen, keanggotaan akun tersebut pada kelas mahasiswa dilepas agar role tidak tumpang tindih.

Akun demo admin:
- Username: `tesadmin`
- Password: `tesadmin`

Upgrade dari v0.10:
```bash
composer dump-autoload
php artisan migrate
php artisan optimize:clear
php artisan serve
```


## Update v0.12
- Admin dapat membuka Edit Profil untuk akun mahasiswa.
- Admin dapat mengubah nama, NIM, username, email, dan mereset password mahasiswa.
- Password lama tidak ditampilkan; password hanya diganti bila field password baru diisi.
- Tidak membutuhkan migration baru dari v0.11.


## Update v0.13
- Admin dapat membuka **Edit Profil** untuk akun Dosen.
- Admin dapat mengubah nama, NIDN/NIP/nomor dosen, username, email, dan mereset password dosen.
- Password lama tidak ditampilkan; isi password baru hanya jika ingin meresetnya.
- Mengedit akun Dosen tidak mengubah kepemilikan mata kuliah, kelas, soal, ujian, atau hasil ujian.
- Tidak membutuhkan migration baru dari v0.12.

Upgrade dari v0.12:
```bash
composer dump-autoload
php artisan optimize:clear
php artisan serve
```


## Update v0.14 - Foto Profil
- User memiliki kolom `profile_photo_path`.
- Mahasiswa dapat upload, ganti, dan hapus foto profil dari **Edit Profil**.
- Admin dapat mengelola foto profil akun Mahasiswa dan Dosen.
- Dosen dapat mengubah foto profil dan data profilnya sendiri dari menu **Edit Profil**.
- Dosen dapat mengelola foto mahasiswa dari halaman kelas.
- Foto tampil pada avatar sidebar dan daftar akun Admin.
- File disimpan di `storage/app/public/profile_photos`.
- Format JPG/JPEG/PNG/WEBP, maksimal 2 MB.

Upgrade dari v0.13:
```bash
composer dump-autoload
php artisan migrate
php artisan storage:link
php artisan optimize:clear
php artisan serve
```
Jika `storage:link` sudah pernah dijalankan, pesan bahwa link sudah ada dapat diabaikan.

## v0.15 - Pengawasan Tab Ujian

Versi ini menambahkan pengawasan perpindahan tab berbasis browser. Saat mahasiswa meninggalkan tab/minimize halaman ujian, sistem mencatat pelanggaran dan dosen yang sedang login menerima popup notifikasi. Pelanggaran ke-3 otomatis menyelesaikan ujian, menghitung nilai dari jawaban yang telah tersimpan, dan logout akun mahasiswa.

Setelah menimpa source versi sebelumnya, jalankan:

```bash
composer dump-autoload
php artisan migrate
php artisan optimize:clear
php artisan serve
```

Catatan: browser tidak menyediakan cara yang benar-benar dapat mengunci pengguna agar tidak berpindah tab. Sistem ini menggunakan deteksi perubahan visibilitas tab dan menerapkan pencatatan/konsekuensi di server.

---

## Update v0.16 - Ulang Ujian Tanpa Batas

Dosen dapat membuka **Ujian > Hasil > kolom Riwayat Ujian** lalu menekan **Izinkan Ulang Tanpa Batas** untuk mahasiswa tertentu.

Selama izin aktif, mahasiswa mendapatkan tombol **Ulangi Ujian** pada menu **Riwayat Ujian**. Setiap pengulangan menjadi percobaan baru dan nilai lama tidak dihapus:

- Ujian ke-1 → nilai 70.00
- Ujian ke-2 → nilai 85.00
- Ujian ke-3 → nilai 92.00
- dan seterusnya tanpa batas percobaan.

Setiap percobaan mempunyai timer, jawaban, skor, jumlah benar/salah/kosong, dan riwayat pelanggaran tab masing-masing. Dosen dapat menonaktifkan izin ulang kapan saja; percobaan yang sudah tersimpan tetap ada.

### Upgrade dari v0.15 ke v0.16

Pertahankan `.env` dan database lama, timpa source dengan v0.16 lalu jalankan:

```bash
composer dump-autoload
php artisan migrate
php artisan optimize:clear
php artisan serve
```

`php artisan migrate` wajib karena v0.16 mengubah struktur `exam_attempts` agar satu mahasiswa dapat mempunyai banyak percobaan pada ujian yang sama dan menambahkan tabel izin pengulangan.


## v0.17 - Peraturan Ujian Multi-Slide
- Peraturan sebelum ujian dibagi menjadi 3 slide.
- Slide 1 mempertahankan 5 aturan pengawasan pindah tab.
- Slide 2 berisi aturan umum ujian (kejujuran, alat bantu, koneksi, refresh, pengelolaan waktu, kendala teknis).
- Slide 3 berisi ringkasan dan persetujuan akhir.
- Tombol mulai hanya tersedia setelah semua slide dilalui dan checkbox persetujuan dicentang.
- Backend memvalidasi `rules_completed` untuk ujian pertama maupun ujian ulang.
- Ujian ulang tanpa batas dari v0.16 tetap dipertahankan, dengan nilai setiap percobaan tersimpan terpisah.
#   c b t  
 