# Operasional Preview pilot

Pemeriksaan 25 November 2026 menunjukkan bahwa `scripts/maintenance.php` menghapus sesi yang melewati masa berlaku atau batas tidak aktif, serta penghitung login yang kedaluwarsa. Penghapusan berjalan dalam satu transaksi.

Role web memiliki izin yang diperlukan untuk tabel sesi dan penghitung. Login tetap memeriksa masa berlaku sesi saat permintaan masuk.

Container Vercel pada commit `30175d3` tidak menyertakan direktori `scripts`. Karena itu, pemeliharaan berkala belum terpasang pada Preview pilot. Jangan menganggap sesi kedaluwarsa sudah dibersihkan otomatis. Untuk menjalankan skrip secara manual, gunakan checkout aplikasi dan proses PHP server-side yang memiliki koneksi role web ke database pilot. Pastikan target database benar sebelum menjalankannya.

Sebelum pilot dipakai kelas, tetapkan pelaksana dan jadwal pemeliharaan, periksa hasilnya, dan uji mekanisme cadangan sesuai layanan yang digunakan. Uji pemulihan lokal dengan data sintetis telah dilakukan. [Hasil validasi](mvp-pilot-local.md)
