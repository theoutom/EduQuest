# Roadmap EduQuest

**Usulan cakupan per 24 November 2026**

EduQuest membantu siswa SMA dan SMK berlatih, memahami kesalahan, dan mengikuti progres belajarnya. Alur utama dimulai dari dashboard, berlanjut ke memilih kuis, membaca aturan, mengerjakan, dan membuka pembahasan. Guru bisa menilai alur tersebut melalui demo publik. Pada pilot sekolah, pengelola juga bisa menerbitkan materi dan jadwal, memoderasi kontribusi, serta mengatur sesi mentor.

Roadmap mengikuti kebutuhan produk, urutan interaksi, dan sistem visual dari dokumen acuan. [PRD EduPlatform](../stitch_educational_learning_platform_prd/eduplatform_project_prd.md) · [Wireframe dan alur induk](../stitch_educational_learning_platform_design/stitch_educational_learning_platform/master_digital_wireframe_eduplatform/screen.png) · [Modern Scholastic](../stitch_educational_learning_platform_design/stitch_educational_learning_platform/modern_scholastic/DESIGN.md)

## Cakupan dan status saat ini

| Lingkungan | Pengguna dan cakupan | Status |
| --- | --- | --- |
| Demo publik | Guru membuka **Coba demo** tanpa akun dan mendapat sesi siswa sendiri. Isi demo meliputi katalog, tiga kuis contoh, progres pribadi, hasil, pembahasan, dan riwayat sesi. | Aktif di proyek Vercel `eduquest-demo` dengan Supabase Free khusus demo. Alur tamu telah diuji di domain publik. [Buka demo](https://eduquest-demo.vercel.app) |
| MVP pilot sekolah | Siswa dan admin memakai akun. Tujuannya menuntaskan alur belajar dari kuis hingga tindak lanjut melalui profil, Tutor terkurasi, komunitas, dan mentor. | Sembilan layar sudah divalidasi lokal. Preview privat terhubung ke Supabase khusus pilot dan lulus uji inti siswa serta admin. Snapshot data pilot berhasil dipulihkan ke PostgreSQL lokal. Pembukaan untuk kelas menunggu tinjauan materi guru dan penetapan pemeliharaan berkala. [Hasil validasi](releases/mvp-pilot-local.md) · [Preview pilot](https://eduquest-pilot-5fgqq36xz-satrioz.vercel.app) |
| Production | Rilis untuk penggunaan sekolah yang sebenarnya, dengan akun dan database tersendiri. | Branch `main` disiapkan untuk Production. Auto-deploy ditahan sampai koneksi, grant, dan pemulihan database siap. |

Source rilis demo publik sudah ditandai dengan tag `demo-public-2026-11-23` pada commit `30175d3`. Arsip tag cocok dengan 72 file runtime yang dipakai oleh deployment Vercel aktif. Deployment dari branch tetap menunggu verifikasi pemetaan proyek dan database. [Source di GitHub](https://github.com/theoutom/EduQuest/tree/demo-public-2026-11-23) · [Catatan rilis](releases/demo-public-2026-11-23.md)

Panduan uji guru siap dibagikan. Audit P0 mengonfirmasi bahwa demo publik memakai proyek dan database tersendiri. Tindak lanjutnya adalah menerima masukan guru dan memverifikasi database Preview privat sebelum mengaktifkan deployment dari branch. [Panduan guru](demo-review-guru.md) · [Pemetaan deployment](releases/p0-deployment-mapping.md)

## Sembilan layar dan definisi MVP

| Layar acuan | MVP pilot sekolah | Setelah MVP |
| --- | --- | --- |
| **1. Dashboard** | Lanjutkan sesi aktif. Tampilkan jumlah kuis selesai, rata-rata, durasi kuis, aktivitas dan streak WIB, tantangan lima menit yang dijadwalkan admin, jadwal ujian, serta sesi mentor terdekat. | Notifikasi yang mengarah ke aktivitas terkait. Peringkat mingguan setelah keanggotaan dan aturan kelas tersedia. |
| **2. Daftar Kuis** | Cari judul, filter topik, tingkat, dan status, lalu buka, lanjutkan, atau simpan kuis secara pribadi. Aksi hapus simpanan menghapus penanda siswa. | Rating dan pencarian lintas modul jika pilot menunjukkan kebutuhan. Rating berasal dari pengguna yang memenuhi syarat. |
| **3. Profil** | Siswa melihat progres dan riwayat serta mengedit sekolah, kelas, jurusan, minat, dan bio pribadi. Admin mengelola identitas akun dan akses. | Ubah kata sandi mandiri, preferensi notifikasi, dan prestasi. Verifikasi identitas, NISN, serta kontak wali kelas menunggu kebutuhan sekolah dan aturan data. |
| **4. Pengantar Kuis** | Tampilkan jumlah soal, durasi, nilai lulus, aturan, percobaan tersisa, riwayat, serta tindakan mulai atau lanjutkan. | Visual target yang memakai nilai lulus sebenarnya. Tentukan kebutuhan ujian terawasi sebelum merancang proctoring. |
| **5. Pengerjaan** | Timer server untuk satu sesi, simpan jawaban, ragu-ragu, peta soal, blok kode, dan konfirmasi pengumpulan. Muat ulang membawa siswa ke percobaan yang sama. | Editor visual materi seperti diagram atau tabel memori ketika dibutuhkan oleh soal. Evaluasi kuis adaptif setelah ada data untuk menilai manfaatnya. |
| **6. Tutor** | Di mode latihan, siswa meminta bantuan terkurasi untuk memulai, mengurai langkah, atau mencoba soal pembanding. Panduan lokal tersedia saat provider belum siap. | Sambungan 9Router setelah diaktifkan dan diuji eksplisit. Chat bebas menunggu batas input, biaya, dan evaluasi kebocoran jawaban. |
| **7. Hasil dan Pembahasan** | Tampilkan skor, status lulus, benar, salah, kosong, durasi, perbandingan jawaban dengan kunci, penjelasan, dan ketepatan topik untuk percobaan itu. Tampilan cetak lengkap bisa disimpan sebagai PDF lewat browser. | Ringkasan penguasaan lintas percobaan dan rekomendasi belajar setelah aturan agregasi disepakati. |
| **8. Bank Soal Komunitas** | Cari soal terbit, kerjakan, lihat pembahasan setelah menjawab, beri suara, tulis komentar, dan ajukan soal melalui moderasi admin. Tampilkan tren dan kontributor dari aktivitas yang tercatat. | Peran guru terpisah bila sekolah perlu mendelegasikan penerbitan atau moderasi. |
| **9. Cari Mentor** | Cari mentor berdasarkan kategori, tarif, dan slot. Siswa memesan satu slot dan admin mengonfirmasi dengan tautan eksternal yang hanya tampil pada pesanan pemilik. | FAQ berdasarkan alur nyata, ulasan setelah sesi, dan spesialisasi tambahan. Chat atau panggilan di dalam aplikasi menunggu kebutuhan dan aturan moderasi. |

### Keputusan untuk menurunkan mockup menjadi fitur

- Pakai alur sembilan layar dari wireframe sebagai panduan navigasi. Pertahankan sidebar desktop, menu ringkas di ponsel, Plus Jakarta Sans untuk judul, Inter untuk isi, dan monospace untuk kode. Status jawaban, fokus, dan error punya tanda yang bisa dibaca selain warna. [Modern Scholastic](../stitch_educational_learning_platform_design/stitch_educational_learning_platform/modern_scholastic/DESIGN.md)
- Standarkan satu tenggat kuis untuk pengantar, timer, dan pengumpulan. Selama pengerjaan, layar mendahulukan soal, pilihan, dan status penyimpanan. Saat giliran pembahasan, kunci dan penjelasan baru tersedia bagi pemilik hasil. [Pengantar kuis](../stitch_educational_learning_platform_design/stitch_educational_learning_platform/4._beranda_kuis/screen.png) · [Pengerjaan](../stitch_educational_learning_platform_design/stitch_educational_learning_platform/5._halaman_pertanyaan_kuis/screen.png) · [Pembahasan](../stitch_educational_learning_platform_design/stitch_educational_learning_platform/7._penjelasan_detail_soal_jawaban/screen.png)
- Gunakan data nyata untuk nilai, streak, ketepatan topik, dan status ketersediaan. Saat data belum ada, tampilkan keadaan kosong dengan langkah yang bisa dilakukan. Tarif mentor bersifat informatif. Pertemuan berlangsung di layanan eksternal melalui sesi yang dikonfirmasi. [Dashboard](../stitch_educational_learning_platform_design/stitch_educational_learning_platform/1._dashboard_ui/screen.png) · [Cari Mentor](../stitch_educational_learning_platform_design/stitch_educational_learning_platform/9._cari_mentor_guru_call_ur_mentor/screen.png)

## Urutan pekerjaan

| Urutan | Pekerjaan | Syarat selesai |
| --- | --- | --- |
| **P0 · Rapikan rilis demo** | Catat rilis aktif dan sinkronkan perubahan terbaru ke GitHub. Tentukan deployment dari branch demo yang bisa diulang. Verifikasi pemetaan proyek Vercel dan database sebelum mengaktifkan deployment dari Git. Kumpulkan masukan guru atas alur kuis. | Commit, deployment, dan migrasi bisa ditelusuri. Dua tamu tanpa login Vercel punya sesi terpisah. Uji katalog, pengerjaan, review, serta penolakan akses admin lulus di URL publik. |
| **P0 · Siapkan MVP pilot** | Siapkan Preview dengan akun uji serta database untuk pilot atau Production sesuai target rilis. Tinjau materi contoh bersama guru. Uji peran siswa dan admin, desktop dan ponsel, serta akses dengan keyboard. Tuntaskan temuan yang menghambat belajar atau membocorkan data. | Admin bisa menerbitkan kuis dan jadwal, siswa bisa menyelesaikan latihan dan membaca pembahasan, serta peran dan lingkungan terisolasi. Cadangan berhasil dipulihkan ke database uji. |
| **P1 · Perbaiki akun dan tindak lanjut** | Kerjakan ubah kata sandi mandiri, lalu notifikasi dalam aplikasi untuk jadwal ujian, moderasi, dan keputusan mentor. Tambahkan FAQ mentor. Ulasan mentor mengikuti setelah ada sesi nyata yang selesai. | Perubahan kata sandi meminta kata sandi lama, membatasi percobaan, dan mencabut sesi lama. Notifikasi hanya terlihat penerima yang berhak dan tidak terduplikasi. Ulasan paling banyak satu per sesi selesai dan bisa dimoderasi. |
| **P2 · Perluas materi dan evaluasi** | Urutkan visual pada soal, analitik lintas percobaan, rating kuis, dan peran guru berdasarkan kebutuhan pilot. Tetapkan definisi cakupan dan data setiap fitur sebelum implementasi. | Setiap fitur memiliki sumber data, aturan akses, serta kriteria penerimaan yang bisa diuji. |
| **Berdasarkan keputusan terpisah** | Kelas dan peringkat, 9Router live, chat mentor, panggilan di aplikasi, dan proctoring. | Sekolah menyepakati tujuan, pihak yang berhak mengakses, dan data yang dibutuhkan. Tutor live memakai kredensial server dan pengujian provider eksplisit. [Runbook Tutor](ai-gateway.md) |

## Kriteria penerimaan kuis dan data

- Siswa bisa mulai, menyimpan jawaban, muat ulang, kumpulkan, dan membuka hasil dari percobaan yang sama. Uji tenggat, simpan ulang, pengumpulan ulang, serta akses dari akun lain.
- Kunci dan pembahasan belum muncul sebelum pengumpulan atau tenggat. Mentor menyediakan tautan hanya untuk pesanan yang terkonfirmasi. Aksi admin diperiksa di server.
- Demo memakai data sintetis dan Supabase terpisah. Kredensial tetap di server dengan role terbatas. Pertahankan PHP native dengan template Tailwind dan pola komponen shadcn, PostgreSQL di Supabase, serta container PHP di Vercel.
- Setiap rilis menjalankan build, suite dengan role terbatas, dan pemeriksaan HTTP pada lingkungan tujuan. Saat pilot aktif, periksa error dan sesi kedaluwarsa secara berkala, uji pemulihan cadangan, dan pertahankan prosedur rollback. [Rencana maintenance](superpowers/plans/2026-11-23-eduquest-maintenance.md)

## Ukur hasil pilot

| Tujuan PRD | Ukuran awal |
| --- | --- |
| Siswa kembali berlatih | Hari aktif berturut-turut dan jumlah percobaan selesai per minggu. Durasi kuis ialah waktu percobaan yang tercatat. |
| Siswa memahami kesalahan | Perubahan nilai pada percobaan ulang dari kuis yang sama. Setelah Tutor live diaktifkan, rancang cara mengukur kesalahan konsep berulang. |
| Komunitas dan mentor dipakai | Jumlah latihan komunitas selesai, suara yang diberikan, serta pesanan mentor terkonfirmasi. Catat penyelesaian sesi bila status tersebut ditambahkan. |
