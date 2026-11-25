# Pemetaan deployment EduQuest

Audit 24 November 2026, WIB.

| Lingkungan | GitHub dan Vercel | Database |
| --- | --- | --- |
| Demo guru | Proyek `satrioz/eduquest-demo`. Domain publik menunjuk ke deployment CLI `dpl_3TThPf8Kwd2TaHrL1fEFLcEEuqGT`. Proyek belum terhubung ke GitHub. Source deployment tersedia pada tag `demo-public-2026-11-23`. | Proyek Supabase `eduquest-demo` di akun `free`. Kueri baca mengonfirmasi 11 migrasi aplikasi, tabel tantangan dan jadwal, serta fungsi masuk tamu. |
| Preview pilot privat | Proyek `satrioz/eduquest-pilot`. Kandidat `30175d3` dibangun via CLI dengan target Preview. Akses anonim diarahkan ke autentikasi Vercel. | Proyek Supabase `eduquest-pilot` di akun `free`, dengan 11 migrasi, role web dan editor terpisah, serta tiga akun sintetis. |
| Preview privat lama | Proyek `satrioz/eduquest` terhubung ke `theoutom/EduQuest`. Alias Preview branch `demo` menunjuk ke commit `99e1916`. | Hubungan deployment aktif dengan proyek database Preview dan versi migrasinya masih perlu diverifikasi sebelum branch ini menerima source baru. |
| Production sekolah | Proyek `satrioz/eduquest` menetapkan `main` sebagai production branch. Konfigurasi di `origin/main` menahan auto-deploy branch tersebut. | Database pilot atau Production perlu ditetapkan, dimigrasi, dan diuji pemulihannya sebelum rilis sekolah. |

[Demo guru](https://eduquest-demo.vercel.app) · [Preview pilot](https://eduquest-pilot-5fgqq36xz-satrioz.vercel.app) · [Preview lama](https://eduquest-git-demo-satrioz.vercel.app) · [Catatan rilis](demo-public-2026-11-23.md)

## Kandidat Preview pilot

Arsip lokal `eduquest-pilot-30175d3.zip` berisi 72 file runtime dari commit `30175d3`. Arsip cocok dengan source runtime yang telah diuji. Instalasi dependensi dan build CSS serta font berhasil dari direktori kosong. Arsip hanya berisi source dan konfigurasi build.

Deployment `dpl_3sR5kkB9C94UZmddXvDkqb1dzC4P` memakai tujuh variabel sensitif khusus Preview. Mode tamu dimatikan dan Tutor memakai panduan bawaan. Gunakan target `vercel deploy --target preview` saat membangun ulang kandidat. Build pertama yang ditargetkan ke Production oleh CLI dibatalkan. [Inspektor deployment](https://vercel.com/satrioz/eduquest-pilot/3sR5kkB9C94UZmddXvDkqb1dzC4P) · [Pengaturan proteksi](https://vercel.com/docs/deployment-protection) · [Validasi lokal](mvp-pilot-local.md)

Uji langsung lulus untuk autentikasi Vercel, login aplikasi, penolakan akses admin oleh siswa, simpan jawaban, pengumpulan, dan penolakan akses hasil milik siswa lain. Admin membuat dan menerbitkan jadwal sintetis. Siswa hanya melihat jadwal setelah terbit. Jadwal uji dibersihkan setelah pemeriksaan. Gangguan autentikasi pada pooler teratasi setelah verifier role pilot diperbarui dan alur dijalankan ulang.

Uji lanjutan dengan akun sintetis memeriksa pengajuan, privasi draf, penerbitan, akses pembahasan setelah menjawab, suara, dan arsip pada Bank Soal Komunitas. Pada Cari Mentor, pemesanan kedua untuk slot yang sama ditolak. Tautan pertemuan hanya tampil untuk pemilik, dan siswa lain tidak dapat membatalkan pesanannya. Setelah uji, soal diarsipkan, pemesanan dibatalkan, dan profil mentor dinonaktifkan.

## Sebelum mengaktifkan deployment dari branch

1. Tentukan branch publik demo dan atur pemfilteran branch di kedua proyek Vercel. Uji pemfilteran agar proyek guru hanya membangun branch demo publik dan proyek sekolah mengecualikannya. Vercel secara default dapat membangun branch lain di repositori yang terhubung. [Konfigurasi branch Vercel](https://vercel.com/docs/project-configuration/git-configuration)
2. Cocokkan environment calon deployment dengan proyek Supabase yang ditetapkan. Periksa daftar migrasi di `eduquest_meta.migrations` dan izin role tanpa menyalin nilai kredensial ke laporan. Mode tamu dan role demo hanya dipakai di proyek guru.
3. Uji calon deployment sebelum mengalihkan domain. Di demo guru, periksa tombol **Coba demo**, kuis, pembahasan, pemisahan sesi, dan penolakan akses admin. Di pilot, uji akun siswa serta admin pada database yang sesuai. Catat commit, deployment, dan revisi skema.
4. Simpan ID deployment sebelumnya untuk rollback. Untuk demo publik saat ini, gunakan [catatan rilis](demo-public-2026-11-23.md) sebagai referensi build dan database.
