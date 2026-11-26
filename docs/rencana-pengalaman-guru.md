# Rencana pengalaman guru dan kesiapan penggunaan

Tanggal analisis: 26 November 2026. Dasar: branch kelas dan gamifikasi serta perubahan ekspor guru. Ini adalah rencana, bukan klaim fitur telah selesai. Verifikasi pada Preview tetap diperlukan sebelum demonstrasi.

## Kondisi yang ditemukan

- Guru mendapat kelas dari admin, membuat kuis untuk satu kelas, mengelola draf, soal, penerbitan, dan melihat hasil kuis yang dimilikinya. Akses diperiksa di server berdasarkan pemilik dan penugasan kelas. Hasil dan soal mendapat ekspor Excel serta tampilan cetak/PDF pada branch ini.
- Ruang Guru menampilkan daftar kelas dan tautan ke kuis, quest, Susun Langkah, dan Mastery Tree. Daftar kelas belum menjadi halaman detail kelas yang menunjukkan siswa, tugas, dan progres dalam satu tempat.
- Pembuatan kuis meminta kode/slug manual dan beberapa pengaturan sebelum guru mulai menulis soal. Editor soal sudah memiliki pilihan jawaban dan kunci, tetapi tiap soal disimpan melalui formulir tersendiri. Status, hasil, pengaturan, dan soal berada pada halaman yang sama dan berpotensi membingungkan.
- Daftar kuis guru hanya menampilkan judul. Status draf/terbit, kelas sasaran, jumlah soal, dan hasil belum terlihat langsung di daftar.
- Quest, Susun Langkah, Mastery Tree, arena siswa, jadwal, komunitas, dan mentor memiliki halaman dan layanan; keberadaan kode saja belum membuktikan seluruh alurnya mudah digunakan atau siap demonstrasi.
- Materi bacaan dan izin guru yang lebih rinci masih memerlukan perancangan dan pengujian. Jangan menjanjikan integrasi tutor 9Router sebelum koneksi server dan perilaku gagal diuji.

## Urutan perbaikan setelah baseline pilot diverifikasi

1. **Satu alur inti guru–siswa.** Buat panduan satu alur yang bisa dijalankan: admin menugaskan guru dan siswa ke kelas, guru membuat draf dan soal lalu menerbitkan, siswa kelas yang tepat mengerjakan, siswa membaca pembahasan, guru memeriksa dan mengekspor nilai. Tampilkan sasaran kelas dan status simpan/terbit pada setiap tahap. Uji juga siswa kelas lain, guru lain, perubahan keanggotaan, sesi kedaluwarsa, pengiriman ulang, dan koneksi terputus.
2. **Ruang Guru berpusat pada kelas.** Jadikan kelas dapat dibuka dengan daftar siswa dan ringkasan kuis serta progres. Sediakan keadaan kosong yang menyebut tindakan dan pihak yang dapat melakukannya. Data harus berasal dari Supabase lewat server; hitungan harus sesuai izin guru dan definisi percobaan yang jelas.
3. **Pembuatan kuis dan soal yang lebih mudah.** Beri urutan informasi dasar → kelas → soal → tinjau → terbitkan; buat kode secara otomatis dan beri opsi mengganti bila perlu. Sediakan penambahan pilihan dan soal yang jelas, validasi dekat bidang, indikator perubahan belum tersimpan, serta penjelasan bahwa revisi terbit membuat versi baru. Uji menggunakan keyboard, ponsel, banyak soal, dan jaringan lambat tanpa kehilangan input.
4. **Hasil dan dokumen guru.** Tampilkan kelas, judul, versi, status, jumlah siswa yang selesai/belum, dan definisi nilai; tentukan apakah daftar menampilkan setiap percobaan atau hasil terbaik/terakhir. Pastikan filter sama di layar dan ekspor. Uji file XLSX di aplikasi spreadsheet serta cetak/PDF berhalaman banyak. Soal dengan kunci harus diberi label dan hanya dapat diunduh pengguna berwenang.
5. **Hubungkan aktivitas pendukung secara utuh.** Audit dan uji tiap alur Quest, Susun Langkah, Mastery Tree dan arena dari pembuatan sampai pengalaman siswa dan hasil guru. Jelaskan prasyarat, kelas sasaran, aktif/tidak aktif, serta dampak penerbitan/arsip; tampilkan hanya tindakan yang benar-benar berfungsi. Selesaikan bagian yang terputus berdasarkan temuan, bukan sekadar mengganti label.
6. **Materi bacaan dan peran guru.** Rancang materi sebagai konten nyata yang dibuat/diedit, dipublikasikan untuk kelas, dibaca siswa, dan dapat ditinjau guru. Tentukan format, akses, pengarsipan, pencatatan progres, dan hak atas materi. Rancang matriks izin admin/guru/siswa serta perpindahan kelas sebelum perubahan skema; uji akses langsung dan pencabutan, bukan hanya menyembunyikan tombol.
7. **Kinerja, keamanan dan UX lintas perangkat.** Ukur waktu navigasi dan query pada Preview dengan data representatif; tentukan batas latensi dan perbaiki penyebab yang terukur. Tinjau advisor Supabase, izin database dan RLS sesuai model akses PHP sebelum perubahan. Audit ikon, label, kontras, fokus keyboard, pesan gagal, konfirmasi tindakan dan tampilan ponsel bersama guru.

## Kriteria selesai setiap fitur

Fitur dianggap siap bila alur utama dan kegagalan diuji dari antarmuka dengan akun yang tepat, data tersimpan dan terbaca kembali di Supabase pilot, batas kelas dan peran diuji di server, umpan balik pengguna jelas, tidak ada tombol kosong, dan hasil pengujian/risiko tercatat. Demo publik dan pilot tetap memakai database terpisah; kredensial provider hanya di server.
