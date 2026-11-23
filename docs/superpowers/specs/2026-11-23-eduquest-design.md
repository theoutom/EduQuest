# Rancangan EduQuest

Status draf untuk persetujuan.

## Tujuan dan cakupan

EduQuest membantu siswa SMA dan SMK, terutama XI PPLG, berlatih soal dan memahami kesalahan melalui pembahasan. Demo dianggap berhasil ketika siswa dapat memilih kuis, mengerjakannya, menutup atau memuat ulang halaman tanpa kehilangan jawaban tersimpan, lalu melihat nilai dan pembahasan dari percobaan yang sama.

| Layar acuan | Cakupan demo |
| --- | --- |
| Dashboard | Halaman awal yang mengarahkan ke daftar kuis atau sesi aktif. Area statistik lanjutan, tantangan harian, jadwal, dan leaderboard disiapkan sebagai placeholder bila ditampilkan. |
| Daftar Kuis | Katalog PostgreSQL, pencarian judul, filter topik dan tingkat kesulitan, serta tautan ke pengantar kuis. |
| Profil | Placeholder dengan nama akun demo. Pengubahan data akademik dan preferensi menyusul. |
| Pengantar Kuis | Judul, topik, jumlah soal, durasi, nilai lulus, sisa percobaan, dan aksi mulai atau lanjutkan. Riwayat mengarah ke hasil percobaan sebelumnya. |
| Pengerjaan | Soal pilihan ganda, blok kode bila tersedia, hitung mundur, tanda ragu-ragu, navigasi nomor soal, simpan jawaban, dan kumpulkan. |
| Tanya AI | Placeholder yang dapat dibuka dan ditutup dari soal. Informasi singkat menjelaskan bahwa bantuan AI belum tersedia. |
| Hasil dan Pembahasan | Skor, status lulus, jumlah benar, salah, dan kosong, durasi, pembahasan tiap soal, dan filter semua/benar/salah/kosong. |
| Bank Soal Komunitas | Placeholder pada rute tersendiri. Pencarian, kontribusi, diskusi, dan voting menyusul. |
| Cari Mentor | Placeholder pada rute tersendiri. Direktori, pemesanan, chat, dan panggilan menyusul. |

Gunakan soal contoh pilihan ganda dengan pembahasan yang dapat diverifikasi, misalnya materi struktur data dan logika pemrograman. Sediakan data sintetis dan dua akun siswa demo dengan riwayat terpisah agar alur dapat dicoba secara independen. Adaptasi tingkat kesulitan, proctoring, PDF, statistik lintas kelas, serta data pribadi asli berada di luar demo pertama. Tombol dan angka contoh dari mockup hanya muncul jika bisa didukung data atau aksi nyata.

## Pendekatan

- Pilihan untuk demo adalah menyelesaikan alur kuis sampai pembahasan dengan data persisten, lalu memakai placeholder berlabel untuk modul lain. Ini memungkinkan evaluasi seluruh perjalanan siswa dengan cakupan yang disetujui.
- Membangun semua modul secara bersamaan akan menunjukkan lebih banyak variasi halaman tetapi menambah ketergantungan pada layanan AI, moderasi komunitas, dan pemesanan sesi. Modul tersebut dijadwalkan setelah demo.
- PHP native merender halaman dan komponen HTML dengan Tailwind CSS. Pola komponen shadcn diterapkan pada template PHP, seperti varian tombol, input, tab, dan dialog. Memasang komponen resmi shadcn melalui frontend React akan menambah toolchain dan lapisan antarmuka. [Tailwind CLI](https://tailwindcss.com/docs/installation/tailwind-cli) · [shadcn/ui](https://ui.shadcn.com/docs/installation)

## Arah visual

PRD menentukan cakupan produk, wireframe induk menentukan urutan interaksi, dan Modern Scholastic menjadi sumber token serta tipografi. Sembilan mockup menunjukkan susunan konten tiap layar. Pertahankan struktur desktop-first dengan sidebar dan bar atas. Sidebar navy berasal dari sistem desain, sementara kanvas terang dan panel putih memberi batas yang jelas untuk area pengerjaan. Sidebar berubah menjadi menu ringkas di ponsel. Pada layar soal, stem dan pilihan tampil sebelum navigator dalam urutan baca sempit. [PRD](../../../stitch_educational_learning_platform_prd/eduplatform_project_prd.md) · [Sistem desain](../../../stitch_educational_learning_platform_design/stitch_educational_learning_platform/modern_scholastic/DESIGN.md) · [Wireframe induk](../../../stitch_educational_learning_platform_design/stitch_educational_learning_platform/master_digital_wireframe_eduplatform/screen.png)

Gunakan Plus Jakarta Sans untuk judul, Inter untuk isi, dan monospace untuk kode. Abaikan override Times New Roman pada ekspor dashboard agar tipografi konsisten. Gunakan biru `#2563EB` pada aksi utama dan pilihan aktif, hijau pada hasil benar, amber pada tanda ragu-ragu atau area bantuan, serta merah pada kegagalan. Status menyertakan teks atau ikon berlabel. Radius dasar 8 px berlaku untuk kontrol dan 16 px untuk panel. Fokus keyboard terlihat, blok kode bergulir di dalam panel, dan gerak terbatas pada umpan balik singkat dengan dukungan pengurangan gerak.

Fokus utama berpindah dari aksi melanjutkan di dashboard, ke soal saat pengerjaan, lalu ke perbandingan jawaban siswa dan kunci pada pembahasan. Navigator membawa status kosong, terjawab, dan ragu-ragu dalam bentuk yang konsisten.

## Alur dan aturan kuis

1. Siswa masuk melalui akun demo, memilih kuis, lalu membuka pengantar. Jika kuis itu masih memiliki percobaan aktif, aksi utama membuka percobaan tersebut. Jika batas percobaan sudah tercapai, halaman menautkan riwayat hasil. Kuis yang siap dikerjakan memiliki minimal satu soal dan setiap soal memiliki tepat satu pilihan benar.
2. Memulai kuis membuat satu percobaan aktif per siswa dan kuis, dengan tenggat berdasarkan waktu server serta versi soal tetap. Gunakan satu hitung mundur per sesi. Usulan ini menyelaraskan layar pengerjaan dengan pengantar yang menyebut timer per butir. Aksi mulai berulang atau dari tab lain mengembalikan percobaan aktif yang sama.
3. Siswa menyimpan pilihan dan tanda ragu-ragu per soal. Pada navigasi soal berikutnya atau kumpulkan, browser menunggu perubahan terakhir tersimpan. Tampilkan status menyimpan, tersimpan, atau gagal. Jika gagal sebelum tenggat, pertahankan perubahan di halaman agar siswa bisa mencoba lagi.
4. Pengumpulan manual meminta konfirmasi dan menyebut jumlah soal kosong. Saat tenggat lewat, server menolak perubahan dan memfinalisasi jawaban terakhir yang tersimpan ketika percobaan diakses lagi. Browser yang masih terbuka meminta finalisasi saat hitung mundur berakhir. Kirim ulang permintaan pengumpulan mengembalikan hasil yang sama.
5. Server menghitung nilai 0–100 dengan bobot setara per soal, jawaban kosong bernilai nol, dan pembulatan ke bilangan bulat terdekat. Nilai yang tampil menjadi dasar status lulus. Hasil menampilkan pilihan siswa, kunci resmi, dan penjelasan. Kunci dan pembahasan dikirim hanya setelah percobaan berakhir dan kepada pemilik percobaan. Percobaan ulang pada demo boleh memakai paket soal yang sama untuk latihan.

## Arsitektur dan data

- PHP native memakai satu titik masuk HTTP, controller untuk alur halaman, layanan kuis untuk transaksi, dan template PHP yang berbagi shell. Tailwind dikompilasi saat build. JavaScript ringan memperbarui hitung mundur, status simpan, dan dialog.
- PostgreSQL via `PDO_PGSQL`. Tabel inti mencakup pengguna demo, sesi login, topik, kuis, versi kuis, soal, opsi, percobaan, dan jawaban percobaan. Versi yang sudah dipakai tetap tersedia agar hasil lama bisa ditinjau. Satu jawaban per pasangan percobaan dan soal, dengan referensi yang menjaga opsi sesuai soal.
- Penyimpanan jawaban dan finalisasi melakukan pemeriksaan kepemilikan, status, dan tenggat dalam satu transaksi yang mengunci baris percobaan. Ini menjaga hasil ketika dua tab mencoba menyimpan atau mengumpulkan pada saat yang sama. Indeks mendukung pencarian kuis aktif per siswa dan pembacaan riwayat. Filter serta nomor soal dapat dibuka lewat URL.
- Gunakan akun demo lokal di aplikasi dengan cookie sesi HTTP-only dan penyimpanan sesi di PostgreSQL, agar identitas tetap tersedia antar-instance. Lindungi mutasi dengan token CSRF, lakukan validasi dan otorisasi di server, dan simpan kredensial basis data di variabel lingkungan. Jika database dihubungkan ke Supabase, gunakan skema aplikasi privat dan role database dengan hak minimum. Supabase Auth dapat dipertimbangkan ketika pengelolaan akun masuk cakupan.
- Supabase menjadi target PostgreSQL terkelola dan Vercel menjadi target hosting. Uji PHP native dengan ekstensi `PDO_PGSQL` pada preview Vercel sebelum mengandalkan jalur deploy. Kandidatnya Container Runtime yang masih beta, atau runtime komunitas `vercel-php`. Untuk koneksi serverless lewat transaction pooler Supabase, gunakan TLS dan uji konfigurasi PDO yang menonaktifkan prepared statement di sisi server sambil mempertahankan parameter binding. Migrasi dijalankan terpisah dengan koneksi yang cocok untuk sesi tunggal. [Vercel Container Runtime](https://vercel.com/changelog/deploy-a-container-from-a-plain-dockerfile) · [Runtime komunitas PHP](https://vercel.com/docs/project-configuration/vercel-json) · [Koneksi Supabase](https://supabase.com/docs/guides/database/connecting-to-postgres)

## Kriteria penerimaan

- Siswa dapat membuka katalog, memilih kuis, membaca aturan, dan melihat hasil dari percobaan yang sama.
- Memuat ulang halaman mempertahankan jawaban dan tanda ragu-ragu yang tersimpan. Hitung mundur mengikuti tenggat semula.
- Kegagalan simpan memberi pesan dan kesempatan mencoba lagi. Ketika waktu habis, penilaian memakai perubahan terakhir yang telah diterima server sebelum tenggat.
- Dua permintaan pengumpulan menghasilkan nilai yang sama. Percobaan lain hanya dapat diakses pemiliknya. Kunci tidak muncul dalam HTML atau respons sesi aktif.
- Skor dan filter pembahasan mencakup soal benar, salah, dan kosong. Batas percobaan dan tombol lanjut konsisten di katalog, pengantar, dan hasil.
- Alur utama bisa diselesaikan dengan keyboard dan di ponsel. Placeholder memakai label yang jelas dan memberi jalan kembali ke alur kuis.

Nama rute dan format migrasi diselesaikan saat perencanaan implementasi. Penerimaan demo pertama dilakukan secara lokal memakai skema PostgreSQL yang sama dengan target Supabase. Integrasi Supabase dan preview Vercel menyusul. Pemilihan runtime Vercel mengikuti hasil uji kompatibilitas.
