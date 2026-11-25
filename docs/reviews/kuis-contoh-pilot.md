# Tinjauan materi kuis contoh EduQuest

Tinjauan teknis 24 November 2026. Cakupan meliputi sembilan soal dalam tiga kuis pada demo publik. Penilaian kesesuaian materi dengan kelas menunggu masukan guru.

Uji sesi tamu di demo publik menghasilkan nilai 100 untuk setiap kuis saat memakai kunci yang diperiksa. Halaman hasil menampilkan tiga pembahasan per kuis.

| Kuis | Hasil tinjauan teknis | Yang perlu dinilai guru |
| --- | --- | --- |
| **Struktur Data & Logika Pemrograman** | Sisa bagi `14 % 5` adalah 4. Stack mengambil aksi terbaru lebih dulu. Akses indeks pada array berukuran elemen tetap berjalan dalam waktu konstan, `O(1)`. [Stack](https://xlinux.nist.gov/dads/HTML/stack.html) · [Array](https://xlinux.nist.gov/dads/HTML/array.html) | Apakah siswa sudah mengenal pseudocode dan notasi kompleksitas? Untuk kelas pemula, pertimbangkan menerangkan `O(1)` sebagai jumlah langkah tetap ketika ukuran array bertambah. |
| **SQL & Basis Data Relasional** | `LEFT JOIN` mempertahankan baris kiri dan mengisi kolom kanan dengan `NULL` jika tidak ada pasangan. `HAVING` dapat menyaring grup berdasarkan hasil `COUNT(*)`. Foreign key menjaga integritas referensi. [Ekspresi tabel PostgreSQL](https://www.postgresql.org/docs/current/queries-table-expressions.html) · [Foreign key](https://www.postgresql.org/docs/current/ddl-constraints.html#DDL-CONSTRAINTS-FK) | Apakah siswa dapat membedakan filter baris dengan filter grup? Minta siswa memperkirakan hasil `LEFT JOIN` untuk dua baris contoh sebelum melihat pembahasan. |
| **Dasar HTTP & REST API** | Tiga soal mencocokkan baca daftar dengan `GET`, berhasil membuat kuis dengan `201 Created`, dan ID yang tidak ditemukan dengan `404 Not Found`. [Semantik HTTP](https://www.rfc-editor.org/rfc/rfc9110.html) | Apakah contoh permintaan sederhana seperti `GET /kuis` akan membantu siswa mengaitkan metode dengan tindakan di aplikasi? |

Ketiga kuis berisi tiga soal dan memakai nilai lulus 67. Aplikasi membulatkan dua jawaban benar menjadi 67 dari 100. Guru bisa menilai apakah ambang dua dari tiga cukup untuk tujuan latihan dan apakah waktu 10 menit untuk Struktur Data dan HTTP serta 15 menit untuk SQL sesuai dengan kelas.

## Catatan masukan guru

Catat nama kuis, nomor soal bila relevan, bagian yang perlu diperjelas, dan perubahan yang diusulkan. Mulai dengan membedakan kendala navigasi, istilah yang perlu dijelaskan, dan materi yang belum diajarkan di kelas. [Panduan mencoba demo](../demo-review-guru.md)
