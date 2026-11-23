# Provider Tutor EduQuest

## 9Router lokal

EduQuest memakai `http://127.0.0.1:20128/v1/chat/completions` untuk 9Router lokal. Alias `lower-models` tersedia pada katalog instalasi ini dan menjadi contoh konfigurasi. Pilih alias lain dari dashboard jika ingin mengubah model atau kuota yang digunakan. [Integrasi 9Router](https://github.com/decolua/9router/blob/master/gitbook/content/en/integration/other-tools.md)

```powershell
$env:EDUQUEST_TUTOR_PROVIDER = '9router'
$env:EDUQUEST_TUTOR_9ROUTER_PORT = '20128'
$env:EDUQUEST_TUTOR_9ROUTER_MODEL = 'lower-models'
$key = Read-Host '9Router API key' -AsSecureString
$env:EDUQUEST_9ROUTER_API_KEY = [pscredential]::new('9router', $key).GetNetworkCredential().Password
Remove-Variable key
php scripts/tutor-gateway-check.php
```

Gunakan key dari dashboard 9Router. Pilihan upstream dan pemakaiannya mengikuti pengaturan alias di 9Router. Pemeriksaan di atas hanya memvalidasi environment PHP. Perintah `php scripts/tutor-gateway-check.php --live` mengirim satu permintaan sintetis yang dapat memakai kuota upstream. Jalankan server PHP dari shell yang sama supaya konfigurasi terbaca. Sumber prompt tetap dibatasi pada kategori dan jenis bantuan, dengan panduan bawaan saat koneksi atau balasan gagal.

Koneksi EduQuest selalu memakai loopback untuk 9Router, tanpa proxy HTTP. Jika router hanya digunakan di perangkat ini, ia bisa dijalankan dengan `9router --host 127.0.0.1 --port 20128 --no-browser`. Pengaturan ini berlaku saat memulai proses router baru dan dapat memengaruhi klien lain. [Pengaturan 9Router](https://github.com/decolua/9router/blob/master/README.md)

## Alternatif Vercel

Tutor memakai PHP cURL untuk memanggil Chat Completions melalui Vercel AI Gateway. Proses PHP menerima konfigurasi lewat environment. Model hanya menerima kategori konsep dan jenis bantuan. Balasan harus memuat satu indeks 0–2. Tutor menampilkan saran yang sesuai dari katalog internal. Saat koneksi gagal, kuota habis, atau balasan tidak valid, Tutor memakai panduan bawaan. [Kontrak API](https://vercel.com/docs/ai-gateway/sdks-and-apis/openai-chat-completions/structured-outputs)

| Variabel | Kegunaan |
| --- | --- |
| `EDUQUEST_TUTOR_PROVIDER` | Isi `vercel` untuk Vercel AI Gateway, `9router` untuk 9Router, `ollama` untuk Ollama lokal, atau `panduan` untuk panduan bawaan. |
| `EDUQUEST_TUTOR_GATEWAY_MODEL` | Model Gateway dengan format `provider/model`. Default `google/gemini-2.5-flash-lite`, terverifikasi di katalog publik saat persiapan. |
| `AI_GATEWAY_API_KEY` | Rahasia proses PHP lokal. Gunakan key terpisah untuk EduQuest. |
| `VERCEL_OIDC_TOKEN` | Alternatif jika runtime hosting benar-benar menyediakan token ini ke proses PHP sebagai environment variable. Key eksplisit mendapat prioritas jika keduanya terisi. |

[Katalog model](https://vercel.com/ai-gateway/models) · [Autentikasi Gateway](https://vercel.com/docs/ai-gateway/authentication-and-byok)

## Aktivasi lokal

1. Di tim Vercel yang akan menanggung pemakaian EduQuest, buat key khusus dan tetapkan kuota sesuai batas yang disetujui. Pilih periode reset di AI Gateway. [Kuota Gateway](https://vercel.com/docs/ai-gateway/observability-and-spend/budgets)
2. Di PowerShell yang akan menjalankan PHP, isi konfigurasi berikut. Masukkan key melalui prompt tersembunyi. File `.env.example` hanya contoh nama variabel.

   ```powershell
   $env:EDUQUEST_TUTOR_PROVIDER = 'vercel'
   $env:EDUQUEST_TUTOR_GATEWAY_MODEL = 'google/gemini-2.5-flash-lite'
   $key = Read-Host 'AI Gateway key' -AsSecureString
   $env:AI_GATEWAY_API_KEY = [pscredential]::new('gateway', $key).GetNetworkCredential().Password
   Remove-Variable key
   php scripts/tutor-gateway-check.php
   ```

3. Pemeriksaan tanpa flag hanya memvalidasi konfigurasi lokal. Jalankan `php scripts/tutor-gateway-check.php --live` saat siap menggunakan kuota. Perintah ini mengirim satu contoh sintetis dan hanya mencetak kelas hasil, misalnya `ok`, `http_401`, `http_429`, atau `invalid_response`.
4. Mulai proses PHP dari shell yang sama mengikuti README. Proses PHP yang sudah berjalan menjaga environment awalnya. Saat selesai, tutup shell atau hapus variabel lokal dengan `Remove-Item Env:AI_GATEWAY_API_KEY`.

## Operasional dan verifikasi

- Jalankan `php tests/tutor-gateway.php` untuk memeriksa kontrak HTTP dan parsing tanpa key. `npm test` menambahkan regresi Tutor, role, browser dan kuis.
- Periksa ketersediaan serta dukungan JSON Schema sebelum mengganti model. Adapter membatasi waktu tunggu, jumlah token, ukuran respons, dan panggilan Gateway pada satu percobaan. Saat status `http_402` atau `http_429` muncul, periksa kuota di Gateway.
- Untuk deployment, gunakan secret server-side dan periksa project budget jika memilih OIDC. Uji dari preview yang benar-benar membawa variabel ke PHP. `EDUQUEST_TUTOR_PROVIDER=panduan` menyediakan jalur rollback yang bisa diaktifkan lewat konfigurasi.
- Gunakan panduan **Vercel AI Gateway** untuk model, autentikasi dan kuota, serta **Vercel Environment Variables** untuk mengelola secret dan pengaturan preview/production.
