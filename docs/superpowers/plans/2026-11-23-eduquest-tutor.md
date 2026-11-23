# Tutor Latihan EduQuest

- [x] Tambahkan tes mode ujian, izin pemilik sesi, kuis latihan, komunitas, input tak didukung, dan batas permintaan.
- [x] Tambahkan pengaturan mode latihan per versi draf. Pertahankan perilaku versi terbit dan kuis yang sudah ada.
- [x] Sediakan panel bantuan dengan tiga permintaan terarah. PHP menentukan keluarga konsep dari soal. Tutor memilih petunjuk dari materi yang dikurasi tanpa mengekspos soal mentah, kunci, opsi, jawaban siswa, identitas, atau pesan bebas ke model.
- [x] Sediakan adaptor Ollama lokal opsional dan panduan bawaan saat model tidak aktif, gagal, atau mengembalikan pilihan di luar daftar. Balasan model hanya memilih indeks strategi. Teks jawaban browser selalu berasal dari katalog panduan.
- [x] Uji alur tanpa model, adaptor dengan fixture HTTP loopback, Chrome, dan server lokal aktif. Direktori Mentor tetap di luar tahap ini.

Verifikasi akhir mencakup penyimpanan pilihan saat membuka Tutor dengan atau tanpa JavaScript, batas per akun, status ujian dan kepemilikan sesi, serta keluaran model yang berada di luar daftar. Suite lengkap dan suite offline lulus. Migrasi 006 serta grant web dan editor diterapkan pada instalasi lokal. Ollama sungguhan tidak aktif pada instalasi yang diuji.
