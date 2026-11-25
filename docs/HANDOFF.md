# Serah terima EduQuest

Perbarui status dari repository dan dashboard sebelum melakukan deployment.

## Lokasi

- GitHub publik [theoutom/EduQuest](https://github.com/theoutom/EduQuest), snapshot terbaru tersedia melalui tag `handoff-2026-11-25`.
- [Demo publik untuk guru](https://eduquest-demo.vercel.app) menyediakan sesi tamu.
- [Preview pilot privat](https://eduquest-pilot-5fgqq36xz-satrioz.vercel.app) memakai autentikasi Vercel dan akun aplikasi.
- Vercel CLI memakai akun `theoutom`, lingkup `satrioz`. Proyek terpisah bernama `eduquest-demo`, `eduquest-pilot`, dan `eduquest`.
- Supabase koneksi `free`, organisasi `pokeeee57's`. Proyek `eduquest-demo` memiliki ref `izhhonwpjcsolqduzfov` dan `eduquest-pilot` memiliki ref `bxgwrfkwuyrmyndcxocx`. Keduanya terpisah.

## Teknologi dan cakupan

PHP native dengan template Tailwind CSS dan pola komponen shadcn, PostgreSQL/Supabase, serta container PHP pada Vercel. Tutor memakai panduan bawaan pada pilot dan sudah disiapkan untuk 9Router. Baca [runbook Tutor](ai-gateway.md) sebelum mengubah provider. Kredensial selalu berada di server.

Alur kuis, pembahasan, admin, komunitas, dan mentor sudah diuji. Admin dapat membuat topik, kuis, soal, kunci dan pembahasan. Peran aplikasi saat ini `student` dan `admin`. Peran guru terbatas dan modul materi bacaan belum diimplementasikan. [Rencana akses guru](rencana-akses-guru.md) · [Roadmap](roadmap.md).

Database pilot memiliki 11 migrasi. Restore snapshot data pilot ke PostgreSQL lokal telah diuji, dengan hash kata sandi dan sesi aktif dikecualikan. Tinjauan materi oleh guru dan penjadwalan maintenance sesi masih terbuka. [Validasi pilot](releases/mvp-pilot-local.md) · [Operasional](releases/operasional-pilot.md).

## Batas deployment

Branch remote `demo` terhubung ke Preview lama dengan database berbeda. Periksa pemetaan dan migrasi sebelum mendorong perubahan ke branch tersebut. `main` menahan auto-deploy. Demo publik dan pilot dibangun melalui CLI dari snapshot `30175d3`. [Pemetaan deployment](releases/p0-deployment-mapping.md).

PRD dan panduan desain teks disertakan dalam snapshot. File mockup HTML dan gambar berada di workspace asal dan belum disertakan karena memuat identitas contoh. Arsip lokal, kredensial dan file konfigurasi lokal yang bersifat rahasia juga berada di luar repository.

Gunakan `npm test` dengan PostgreSQL pengembangan dan role terbatas sesuai README. Jalankan `node tests/run.mjs upgrade` untuk uji upgrade database. Periksa status Git dan lingkungan cloud sebelum membuat perubahan.
