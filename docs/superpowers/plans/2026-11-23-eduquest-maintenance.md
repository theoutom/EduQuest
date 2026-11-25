# Prioritas fitur dan maintenance EduQuest

## Sebelum demo publik

1. Hubungkan proyek Supabase kosong yang khusus demo. Terapkan migrasi dan role web terbatas. Verifikasi bahwa demo dan Production memakai proyek database yang berbeda.
2. Jalankan uji publik tanpa login Vercel. Dua pengunjung harus dapat membuka sesi kuis terpisah. Pastikan rute admin tertutup dan uji ulang pengumpulan serta pembahasan dari ponsel.
3. Siapkan database Production secara terpisah sebelum mengaktifkan auto-deploy `main`. Uji login siswa dan admin, migrasi dan grant, serta pemulihan cadangan di database uji.

## Fitur berikutnya

| Prioritas | Pekerjaan | Kriteria selesai |
| --- | --- | --- |
| 1 | Kata sandi mandiri dan riwayat akun | Siswa memasukkan kata sandi saat ini, pergantian mencabut sesi lama, percobaan gagal dibatasi, dan kredensial tidak muncul di respons atau log. |
| 2 | Notifikasi dalam aplikasi | Jadwal ujian, keputusan mentor, dan moderasi menghasilkan pemberitahuan untuk penerima yang berhak. Daftar mendukung status dibaca; pengiriman ulang tidak menggandakan entri. |
| 3 | Ulasan mentor | Satu ulasan per pemesanan selesai, izin diperiksa di server, dan admin dapat memoderasi tampilan publik. |
| Setelah kebutuhan kelas jelas | Kelas dan peringkat | Admin mengatur keanggotaan. Definisi skor dan seri terdokumentasi. Siswa hanya bisa melihat kelas yang boleh diakses. |
| Setelah disetujui | Proctoring, chat mentor, dan Tutor live | Tentukan kebutuhan dan batas data sebelum implementasi. Tutor 9Router memerlukan kredensial server serta uji provider eksplisit. |

Rating kuis, visual materi khusus, prestasi, verifikasi identitas, dan analitik lintas percobaan tercatat di matriks MVP sebagai backlog. Prioritaskan berdasarkan kebutuhan penggunaan setelah demo.

## Maintenance yang disiapkan

| Kapan | Pemeriksaan |
| --- | --- |
| Setiap rilis | Build, suite PostgreSQL dengan role terbatas, dan smoke test Preview untuk login, kuis, pembahasan, akses admin, serta jadwal. Catat commit dan deployment yang diuji. |
| Mingguan saat layanan aktif | Periksa HTTP 5xx, koneksi database, error autentikasi, request Tutor yang jatuh ke fallback, dan pemakaian kuota. Pantau pembersihan sesi kedaluwarsa menggunakan skrip maintenance yang sudah ada. |
| Bulanan dan sebelum migrasi berisiko | Periksa pembaruan keamanan PHP, container, dan dependensi. Uji restore cadangan ke database uji. Catat waktu pulih dan periksa data sampel tanpa memindahkan data pribadi ke demo. |
| Jika rilis bermasalah | Kembalikan deployment terakhir yang sehat setelah mengecek kompatibilitas skema. Jika migrasi ikut bermasalah, perbaiki atau pulihkan dari cadangan sesuai dampak dan verifikasi data sebelum membuka akses kembali. |

Jadwal pemeriksaan ini adalah rencana operasional. Pemantauan dan cadangan otomatis perlu dikonfigurasi saat Production dipersiapkan.
