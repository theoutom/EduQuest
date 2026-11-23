# EduQuest Alpha

Admin operasional masuk melalui autentikasi PHP yang sama dan hanya dapat membaca ringkasan kuis serta aktivitas keamanan di `/admin`. Role admin diperiksa dari PostgreSQL untuk setiap permintaan. Provisioning akun, reset kata sandi dan perubahan role dilakukan dari CLI. Data kuis beta dipertahankan dan kata sandi demo yang pernah ditampilkan di halaman login dinonaktifkan.

- [x] Tambah tes HTTP untuk akses admin, pemisahan role, kebocoran kredensial, pembatasan login dan proteksi lintas situs.
- [x] Migrasikan role akun, sesi, throttle dan audit. Simpan hash Argon2id serta hash token sesi. Batasi usia sesi dan cabut sesi saat akun diperbarui.
- [x] Tambah pengelolaan akun dari CLI, dashboard admin baca-saja, dan role PostgreSQL untuk proses web dengan izin minimum.
- [x] Jalankan tes dengan PostgreSQL dan Chrome, uji role database, migrasikan beta lokal, dan verifikasi alpha di loopback. Deploy publik dan MFA admin berada di luar rilis lokal ini.

Tes regresi gagal pada kredensial default yang tampil di login beta, lalu lulus setelah bootstrap akun dipindah ke CLI. Tinjauan terpisah menghasilkan perbaikan migrasi akun lama, pembatasan grant berbasis kolom dan provisioning SCRAM tanpa kata sandi di SQL. Verifikasi lokal mencakup login admin melalui role web terbatas, penolakan login PostgreSQL tanpa kata sandi, dan pemeliharaan data percobaan beta.
