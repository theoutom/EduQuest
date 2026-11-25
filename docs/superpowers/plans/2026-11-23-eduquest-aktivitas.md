# Streak dan tantangan harian

- [x] Uji streak pada hari berturut-turut dalam WIB, hari kosong, dan duplikasi percobaan.
- [x] Uji bahwa hanya admin yang dapat menjadwalkan atau mencabut tantangan dari kuis terbit berdurasi maksimal lima menit.
- [x] Tampilkan hasil penjadwalan pada tanggal WIB yang sesuai, lanjutkan sesi yang sudah aktif, dan tandai selesai bila ada hasil pada hari itu.
- [x] Jalankan suite, migrasi lokal, dan verifikasi rute siswa serta admin.

`npm run build`, `npm test`, dan suite `public-demo` lulus. Migrasi `010_daily_challenges.sql` serta grant web dan editor diterapkan di PostgreSQL lokal. Dashboard siswa dan halaman penjadwalan admin merespons HTTP 200.
