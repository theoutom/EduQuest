# Gamifikasi untuk pilot

Perubahan ini menambahkan quest terjadwal, XP, mastery tree, Susun Langkah, dan arena kuis untuk dua siswa sekelas. Streak tetap menghitung hari dengan percobaan kuis yang selesai dalam WIB.

Quest mendukung penyelesaian kuis, konfirmasi membaca pembahasan, dan jumlah soal terjawab pada kuis yang dikumpulkan. Jawaban dihitung hanya bila pilihan terakhir tersimpan selama quest aktif. Jawaban lama yang tidak memiliki waktu penyimpanan tidak dihitung. XP dicatat sekali per siswa dan quest. Bila jatah percobaan kuis habis, dashboard memberi tahu siswa agar menghubungi guru. Penghentian quest tidak menghapus XP yang sudah diperoleh.

Kompetensi menyimpan versi aturan dan kuis; menerbitkan versi kuis baru mengharuskan guru memperbarui aturan kompetensi. Susun Langkah dinilai di server dan menyediakan hasil pribadi setelah pengiriman. Arena memakai polling lima detik, waktu persiapan 12 detik, dan tenggat server yang sama untuk kedua siswa. Hasil tidak mengubah nilai akademik.

## Pengujian

Dengan PostgreSQL lokal dan variabel pengujian seperti pada README, jalankan `npm test` dan `npm run test:gamification`. Perintah gamifikasi membuat database uji terpisah untuk tiap suite dan juga menguji upgrade. Suite PvP menjalankan browser dua akun dan membutuhkan browser Chromium yang dikonfigurasi melalui `CHROME_PATH`. Konfigurasikan role pengujian runtime dan editor untuk memeriksa grant terbatas.

## Penerapan pilot

Migrasi 013–016 dan grant runtime/editor harus diterapkan dengan kredensial server yang sesuai sebelum kode baru menerima trafik. Jangan menaruh kredensial database di browser. Lakukan backup dan uji migrasi pada database terpisah sebelum menerapkan ke pilot; verifikasi penggunaan peran terbatas setelahnya. Aplikasi dan database pilot, demo, dan produksi harus tetap terpisah sesuai konfigurasi masing-masing.

Arena belum diuji beban atau latensinya dengan jaringan siswa di pilot. Papan peringkat mingguan tidak diaktifkan; pembahasan dan pilihan tersebut memerlukan keputusan sekolah. Ketika melakukan rollout, uji pertandingan dengan dua akun sintetis, koneksi terputus dan tenggat sebelum digunakan oleh kelas.
