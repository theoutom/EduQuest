# EduQuest Alpha

EduQuest adalah aplikasi latihan untuk siswa SMA dan SMK. Alpha mencakup kuis, pembahasan, profil dan riwayat siswa, bank soal komunitas, Tutor latihan, pemesanan sesi mentor, serta pengelolaan materi dan akun.

## Menjalankan secara lokal

Siapkan PHP 8.2 atau lebih baru dengan `pdo_pgsql`, `mbstring`, dan dukungan Argon2id, PostgreSQL 14 atau lebih baru, serta Node.js 22.12 atau lebih baru. Untuk pengujian browser, siapkan Chrome, Chromium, atau Edge. PostgreSQL perlu menerima koneksi dari PHP.

1. Buat basis data `eduquest` yang dimiliki role lokal `eduquest`, atau gunakan nama database dan role lain melalui variabel lingkungan di bawah.
2. Bangun stylesheet dan font lokal. Asset hasil build tersedia di `public/assets/`.

   ```sh
   npm ci
   npm run build
   ```

3. Atur `EDUQUEST_DB_DSN`, `EDUQUEST_DB_USER`, dan `EDUQUEST_DB_PASSWORD` dari pengelola rahasia lokal. Gunakan koneksi pemilik skema untuk menjalankan `php scripts/migrate.php` dan menyiapkan akun serta role web.

4. Tetapkan role PostgreSQL web dan editor sesuai pengaturan di bawah. Jalankan `php -S 127.0.0.1:8080 -t public router.php`, lalu buka [EduQuest lokal](http://127.0.0.1:8080). Admin diarahkan ke `/admin`.

   Jika PHP Windows menyertakan DLL PostgreSQL tetapi driver belum aktif, aktifkan `extension=pdo_pgsql` pada `php.ini`, atau gunakan flag `-d extension=pdo_pgsql` pada kedua perintah PHP. Jika direktori ekstensi belum terdaftar, tambahkan `-d extension_dir="C:/lokasi/php/ext"`.

### Akun dan role database

`php scripts/account.php set <username> <student|admin> <name>` membaca kata sandi dari stdin. Masukkan minimal 15 karakter dan maksimal 256 byte. `php scripts/account.php disable <username>` menonaktifkan akses. Kedua perintah mencabut sesi akun tersebut. Untuk reset atau mengganti role, jalankan kembali `set`. Migrasi alpha juga mencabut sesi beta dan menonaktifkan kata sandi demo lama sambil mempertahankan progres kuis.

`php scripts/runtime-role.php eduquest_web` membuat role PostgreSQL terbatas dengan nama baru dan membaca kata sandi database dari stdin. Proses web memakai `eduquest_web`. Simpan kredensial pemilik skema untuk migrasi dan pengelolaan akun di luar konfigurasi proses web. Untuk memeriksa izin role web, jalankan `php tests/privileges.php` dengan konfigurasi role tersebut. Jalankan `php scripts/maintenance.php` secara berkala untuk membuang sesi dan penghitung login yang kedaluwarsa.

`php scripts/runtime-role.php eduquest_editor editor` membuat role editor terpisah setelah migrasi. Simpan kredensialnya di lingkungan PHP sebagai `EDUQUEST_ADMIN_DB_USER` dan `EDUQUEST_ADMIN_DB_PASSWORD`. Opsional, `EDUQUEST_ADMIN_DB_DSN` mengganti DSN yang dipakai editor. Aplikasi membuka koneksi editor setelah memverifikasi sesi dan peran admin. Pengelolaan akun yang memiliki peran admin tetap melalui CLI.

Saat meningkatkan instalasi yang sudah memiliki kedua role, jalankan `php scripts/migrate.php` dengan koneksi pemilik skema, lalu jalankan `php scripts/runtime-role.php eduquest_web runtime --refresh-grants` dan `php scripts/runtime-role.php eduquest_editor editor --refresh-grants`. Mode ini menerapkan grant baru tanpa menerima kata sandi dan memeriksa bahwa setiap role sesuai dengan lingkup yang diminta.

Di Windows, kredensial lokal yang disiapkan untuk alpha tersedia sebagai PSCredential terenkripsi milik pengguna Windows saat ini di `.local/alpha-operator.xml`, `.local/alpha-student-satrio.xml`, dan `.local/alpha-student-nadia.xml`. Gunakan `Import-Clixml` di PowerShell milikmu, kemudian `$akun.UserName` dan `$akun.GetNetworkCredential().Password` untuk membaca kredensial yang diperlukan. File `.local/` berada di luar document root dan diabaikan Git.

Untuk menyalakan ulang proses web pada instalasi Windows lokal yang memakai PostgreSQL portabel di port 55432:

```powershell
$web = Import-Clixml -LiteralPath '.local/alpha-db-web.xml'
$editor = Import-Clixml -LiteralPath '.local/alpha-db-editor.xml'
$env:EDUQUEST_DB_DSN = 'pgsql:host=127.0.0.1;port=55432;dbname=eduquest'
$env:EDUQUEST_DB_USER = $web.UserName
$env:EDUQUEST_DB_PASSWORD = $web.GetNetworkCredential().Password
$env:EDUQUEST_ADMIN_DB_USER = $editor.UserName
$env:EDUQUEST_ADMIN_DB_PASSWORD = $editor.GetNetworkCredential().Password
$php = (Get-Command php).Source
$ext = Join-Path (Split-Path $php) 'ext'
& $php -d "extension_dir=$ext" -d extension=pdo_pgsql -d display_errors=0 -S 127.0.0.1:8080 -t public router.php
```

## Dashboard siswa

Dashboard menampilkan jumlah kuis unik yang sudah diselesaikan, rata-rata nilai dan durasi dari seluruh percobaan yang sudah dinilai, serta aktivitas tujuh hari kalender dalam WIB. Sesi mentor terkonfirmasi yang paling dekat mengarah ke **Sesi saya** untuk membuka tautan pertemuan.

Di **Daftar Kuis**, gabungkan pencarian, topik, tingkat, dan status. Gunakan **Simpan kuis** untuk menandai kuis secara pribadi, lalu pilih status **Tersimpan** untuk menemukannya lagi. Pada **Hasil & Pembahasan**, pilih **Siapkan cetak** untuk membuka seluruh soal, lalu gunakan dialog browser untuk mencetak atau menyimpan PDF.

Di **Profil dan Riwayat**, siswa dapat menyimpan atau mengosongkan sekolah, kelas, jurusan, minat belajar, dan bio. Isian akademik tampil hanya kepada pemilik akun. Nama akun dan akses masuk dikelola melalui **Akun Siswa** di admin.

## Mengelola materi dan akun

1. Dari [dasbor admin](http://127.0.0.1:8080/admin), buat topik dan draf kuis.
2. Tambahkan pertanyaan, opsi, kunci, dan pembahasan, lalu terbitkan draf. Untuk revisi berikutnya, pilih **Buat draf revisi** agar siswa tetap bisa memakai versi sebelumnya sampai revisi diterbitkan.
3. Arsipkan kuis untuk menghentikan percobaan baru. Sesi berjalan tetap bisa dilanjutkan. Hapus permanen tersedia untuk kuis yang belum pernah terbit dan topik yang belum digunakan.
4. Kelola kata sandi dan status akun dari **Akun Siswa**. Perubahan akses mencabut sesi siswa. Nonaktifkan akun yang memiliki riwayat kuis atau pemesanan mentor untuk mempertahankan riwayatnya.

### Bank soal komunitas

- Siswa mengirim kontribusi lewat **Bagikan soal** dan memantau status di **Pengajuan saya**.
- Admin meninjau jawaban, pembahasan, dan diskusi dari **Moderasi Komunitas**. Persetujuan terkait dengan revisi yang diperiksa. Jika penulis menyimpan perubahan, admin membuka ulang pengajuan sebelum mengambil keputusan.
- Kontribusi terbit muncul di katalog komunitas. Siswa lain melihat pembahasan setelah menjawab. Suara dihitung sekali per akun dan bisa dibatalkan. Komentar dapat ditarik oleh penulis atau disembunyikan lewat moderasi.

### Tutor latihan

Pengaturan 9Router lokal, alternatif Vercel AI Gateway, dan perintah smoke test tersedia di [runbook Tutor AI](docs/ai-gateway.md).

- Tutor tersedia pada soal komunitas terbit. Untuk kuis, admin mencentang **Mode latihan dengan Tutor** dalam pengaturan draf, lalu menerbitkan versinya. Kuis yang sudah ada tetap dalam mode mandiri sampai versi latihan diterbitkan.
- Siswa memilih **Bantu mulai**, **Urai langkah**, atau **Beri latihan pembanding**. Panduan berasal dari katalog petunjuk dan tidak mengungkap pilihan atau kunci. Tutor tersedia melalui dialog atau halaman terpisah jika JavaScript tidak aktif.
- Tutor memakai panduan bawaan ketika koneksi model belum siap. Untuk Ollama lokal, tetapkan `EDUQUEST_TUTOR_PROVIDER=ollama`, `EDUQUEST_TUTOR_MODEL`, dan bila perlu `EDUQUEST_TUTOR_OLLAMA_PORT` (default 11434). [API generate Ollama](https://docs.ollama.com/api/generate)

### Cari Mentor

- Admin mengelola profil, kategori, tarif per jam, verifikasi, dan slot dalam WIB dari **Kelola Mentor**.
- Siswa mencari mentor dan mengajukan slot dari direktori **Cari Mentor**. Satu slot hanya menerima satu pemesanan aktif. Siswa dapat membatalkan dari **Sesi saya** sebelum jadwal dimulai.
- Admin menangani permintaan dari **Pemesanan Mentor** dan memasukkan tautan sesi HTTPS dari Google Meet, Zoom, Microsoft Teams, atau Jitsi Meet saat mengonfirmasi. Tautan tampil di **Sesi saya** milik siswa yang memesan sampai sesi berakhir. Pertemuan dibuka di layanan tersebut. Tarif berfungsi sebagai informasi.

## Pengujian

Jalankan `npm test`. Test runner membuat basis data temporer bernama `eduquest_test_…`, menjalankan migrasi, menyalakan server PHP pada port loopback yang tersedia, lalu menghapus basis data temporer setelah selesai. Gunakan server PostgreSQL khusus pengembangan dengan role yang memiliki izin `CREATEDB`. Atur `EDUQUEST_TEST_DSN` jika koneksi admin lokal berbeda dari `pgsql:host=127.0.0.1;port=5432;dbname=postgres`.

Gunakan `CHROME_PATH` jika browser berada di lokasi nonstandar. `EDUQUEST_SCREENSHOTS=1` menyimpan tangkapan layar ke `test-artifacts/`.

Untuk menguji seluruh alur dengan izin terbatas, atur `EDUQUEST_TEST_RUNTIME_USER`, `EDUQUEST_TEST_RUNTIME_PASSWORD`, `EDUQUEST_TEST_EDITOR_USER`, dan `EDUQUEST_TEST_EDITOR_PASSWORD`. Test runner memasang grant pada database temporer dan menggunakan role itu saat melayani permintaan HTTP. Koneksi pemilik skema dipakai untuk menyiapkan database uji.

## PostgreSQL dan hosting

- `EDUQUEST_DB_DSN`, `EDUQUEST_DB_USER`, dan `EDUQUEST_DB_PASSWORD` dipakai server PHP. Untuk hosting, wajibkan HTTPS, tetapkan `EDUQUEST_SECURE_COOKIES=1`, dan arahkan document root ke `public/`. Simpan kredensial di pengelola rahasia hosting. Konfigurasi TLS, MFA admin, dan pembatasan akses di perimeter perlu diverifikasi sebelum rilis publik.
- Pertahankan batas akses role web dan editor pada deployment. PostgreSQL melindungi isi versi terbit dan memisahkan draf dari role web. Pakai role editor hanya dari proses server.
- Vercel memakai preset Container di `vercel.json`. `Dockerfile.vercel` membangun Tailwind dan menjalankan PHP 8.4 dengan FrankenPHP, PDO PostgreSQL, dan Argon2id. Caddy hanya melayani direktori `public/`. [PHP di Vercel](https://vercel.com/kb/guide/deploy-php-on-vercel-with-docker)
- Untuk preview, tautkan folder ke proyek EduQuest lalu gunakan `vercel deploy --target preview`. Simpan variabel koneksi role web dan editor di lingkungan **Preview** Vercel. Jalankan migrasi, refresh grant, dan pembuatan akun uji dari koneksi pemilik skema di luar Vercel. Uji login, kuis, pembahasan, serta akses admin di Preview sebelum promosi.
- Skema aplikasi PostgreSQL bisa digunakan di Supabase. Gunakan TLS dan uji koneksi PDO. Bila memakai transaction pooler Supabase, `EDUQUEST_DB_EMULATE_PREPARES=1` mengaktifkan emulasi prepared statement sambil mempertahankan parameter binding. Jalankan migrasi dengan koneksi langsung atau session pooler sesuai akses jaringan. [Koneksi Supabase](https://supabase.com/docs/guides/database/connecting-to-postgres)

### Alur demo

Branch `demo` menerbitkan Vercel Preview dan `main` adalah branch Production. Tetapkan `EDUQUEST_DEMO_MODE=1` khusus branch `demo` di Preview. Halaman demo menandai perubahan lingkungan dan menampilkan status jika database belum tersedia.

Gunakan proyek PostgreSQL Supabase terpisah untuk demo. Jalankan migrasi dengan role pemilik skema, siapkan role web dan editor terbatas, lalu buat akun uji baru melalui `scripts/account.php`. Masukkan koneksi web dan editor sebagai variabel sensitif khusus Preview branch `demo`. Gunakan koneksi berbeda untuk Production. Uji login siswa, mulai dan kumpulkan kuis, buka pembahasan, lalu verifikasi akses admin dengan akun uji terpisah.
