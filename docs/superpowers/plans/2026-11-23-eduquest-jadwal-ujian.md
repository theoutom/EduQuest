# Jadwal ujian

- [x] Uji akses admin, pembuatan draf, publikasi, perubahan, penarikan, dan penghapusan.
- [x] Simpan rentang ujian dengan waktu WIB dan batasi perubahan jadwal untuk role editor.
- [x] Tampilkan jadwal kini dan mendatang pada dashboard serta halaman siswa.
- [x] Jalankan build, suite regresi, migrasi lokal, dan pemeriksaan kedua peran.

`npm run build` dan `npm test` lulus. Migrasi `011_exam_schedule.sql` diterapkan dan grant role lokal diperbarui. Admin dan siswa bisa membuka halaman masing-masing di localhost.
