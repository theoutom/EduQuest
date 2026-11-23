# Cari Mentor

- [x] Uji direktori, slot WIB, konflik waktu, pemesanan, pembatalan, konfirmasi, dan privasi tautan melalui HTTP.
- [x] Tambahkan profil, jadwal, dan permintaan sesi PostgreSQL dengan batasan slot aktif unik serta kepemilikan di aplikasi.
- [x] Buat halaman siswa dan panel admin untuk profil, jadwal, serta keputusan pemesanan. Tautan eksternal hanya ditampilkan kepada siswa pemilik sesi terkonfirmasi.
- [x] Jalankan suite dengan role terbatas, terapkan migrasi lokal, dan verifikasi rute aktif.

Pemesanan tidak menagih biaya. Admin mengatur slot dan tautan panggilan yang sebenarnya. Katalog tidak diberi mentor atau ulasan rekaan.

`npm test` lulus dengan role web dan editor yang dibatasi, termasuk alur pemesanan Chrome pada lebar 390 piksel. `npm run build` lulus. Migrasi `007_mentor.sql` dan grant kedua role diterapkan di instalasi lokal. Rute mentor siswa dan admin merespons HTTP 200 setelah masuk dengan peran yang sesuai.
