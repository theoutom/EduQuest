# Demo guru, pembatasan kelas, dan ekspor

Tujuan: menyiapkan pilot yang dapat didemonstrasikan tanpa menghapus data lama, dengan dua kelas contoh, guru, kuis kelas, hasil siswa, dan konten gamifikasi. Akun guru dibuat dengan kata sandi baru yang disimpan terenkripsi secara lokal.

1. Audit dan uji akses kuis kelas pada katalog, ID langsung, mulai, simpan, hasil, pengelolaan guru, dan pencabutan. Pertahankan penguncian agar perubahan anggota tidak berpacu dengan pembacaan hasil.
2. Sediakan ekspor nilai dan soal untuk kuis milik guru dalam XLSX asli dan PDF. Pastikan izin diperiksa di server pada setiap permintaan, data nilai dibatasi pada kelas yang ditugaskan, input spreadsheet diperlakukan sebagai teks, dan dokumen tidak mengambil resource eksternal.
3. Perbaiki pengemasan aset polling arena pada container Preview. Uji aset yang benar-benar tersaji dari deployment, bukan hanya server lokal.
4. Siapkan data contoh yang berlabel jelas melalui operasi yang dapat diulang tanpa menghapus atau menimpa data sebelumnya. Jangan memakai kredensial database di frontend.
5. Jalankan pengujian regresi, uji lintas kelas dan ekspor, validasi dokumen, build container/Preview, lalu uji alur guru dan siswa pada Preview pilot. Catat setiap batasan atau pekerjaan manual yang masih diperlukan.
