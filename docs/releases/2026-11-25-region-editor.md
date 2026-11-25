# Pemulihan editor dan pengukuran region pilot

Source runtime editor dipulihkan dari deployment `dpl_DCzPsrLumQnL28maVJvefLwACqvR`. Dua file yang berbeda dari snapshot awal diverifikasi memakai SHA-1 dari Vercel. Commit `e502dff` menyimpan kedua file dan pengujian browser yang direkonstruksi. Commit asal `dca9638` belum tersedia, sehingga histori dan tes aslinya belum dipulihkan.

Suite lengkap dengan PostgreSQL lokal, role web dan editor terbatas, browser desktop dan ponsel, serta pemulihan lokal lulus. Suite PHP lokal memakai PHP 8.5. Container Vercel memakai PHP 8.4.

Preview pembanding dibuat dengan `vercel deploy --target preview --regions sin1`. Metadata mencatat `sin1`, dan header respons menunjukkan eksekusi `sin1`. Preview awal editor menunjukkan eksekusi `iad1`. [Preview pembanding](https://eduquest-pilot-6p6gbi6j9-satrioz.vercel.app)

Tiga permintaan halaman masuk melalui Vercel CLI menghasilkan waktu total 2,724; 1,787; dan 1,796 detik pada `iad1`, serta 4,163; 0,483; dan 0,439 detik pada `sin1`. Ini sampel kecil dari satu lokasi, termasuk permintaan dingin, dan belum mengukur editor atau penyimpanan jawaban. Keduanya memakai database pilot yang sama.

Pengukuran tambahan memakai akun admin pilot dan tiga permintaan baca pada tiap halaman. Total respons `/admin` pada `iad1` adalah 4,513; 3,615; dan 3,728 detik, sedangkan pada `sin1` 0,176; 0,210; dan 0,221 detik. Untuk `/admin/kuis`, hasilnya 5,000; 5,226; dan 4,980 detik pada `iad1` serta 0,218; 0,253; dan 0,245 detik pada `sin1`. Pengukuran memakai Vercel CLI dari satu lokasi, sehingga belum menggantikan pengujian browser di jaringan sekolah.

Tiga penyimpanan jawaban dari akun siswa sintetis menghasilkan 6,480; 6,640; dan 6,357 detik di `iad1`, serta 0,205; 0,258; dan 0,233 detik di `sin1`. Masing-masing memakai sesi terpisah pada database pilot yang sama dan telah dikumpulkan setelah pengukuran. Ini belum mengukur beban serentak atau koneksi sekolah.

## Preview kelas dan guru

Migrasi `012_classes.sql` diterapkan pada proyek Supabase `eduquest-pilot` setelah disetujui. Proyek demo memakai database terpisah. Deployment kandidat `dpl_BtXq4Y9rSmja83yRbrWCjZ7dBj7W` memakai source commit `3379c54`, environment Preview dan region `sin1`. [Buka Preview kelas dan guru](https://eduquest-pilot-jm7igciaw-satrioz.vercel.app)

Uji langsung memakai akun sintetis. Admin membuat akun guru dan kelas, menugaskan guru dan satu siswa. Guru membuat serta menerbitkan kuis kelas. Siswa anggota dapat mengerjakan, siswa pembanding mendapat 404, dan guru melihat hasil anggota. Setelah anggota dikeluarkan, hasil tersebut tidak muncul lagi pada guru. Soal uji diarsipkan, kelas uji diarsipkan dan akun guru uji dinonaktifkan.

Uji lokal juga mencakup jadwal per kelas, pergantian sasaran ke semua kelas, migrasi dari skema lama, login dan reset kata sandi guru, CSRF, larangan membuat akun admin melalui role editor serta dua koneksi untuk pencabutan akses. Pengukuran browser pada jaringan sekolah masih diperlukan.
