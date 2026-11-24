# Demo publik EduQuest, 23 November 2026

- [Demo guru](https://eduquest-demo.vercel.app) dapat dicoba melalui tombol **Coba demo** tanpa akun.
- [Source di GitHub](https://github.com/theoutom/EduQuest/tree/demo-public-2026-11-23) ditandai dengan tag `demo-public-2026-11-23`.
- [Deployment Vercel](https://vercel.com/satrioz/eduquest-demo/3TThPf8Kwd2TaHrL1fEFLcEEuqGT) menggunakan proyek `satrioz/eduquest-demo`.

## Mengulang build demo

Ekspor direktori `app`, `views`, serta file runtime dan konfigurasi berikut dari tag.

```text
.dockerignore
.vercelignore
Dockerfile.vercel
Caddyfile
vercel.json
package.json
package-lock.json
assets/app.css
scripts/copy-fonts.mjs
deploy/supabase-root-2021.crt
public/index.php
public/assets/app.js
```

Build container menghasilkan CSS dan font dari dependensi yang dikunci di `package-lock.json`. Pertahankan konfigurasi server untuk demo di environment proyek `eduquest-demo` pada Vercel.

Untuk menyiapkan database demo baru, gunakan proyek Supabase khusus demo. Jalankan migrasi bernomor `001` sampai `011` secara berurutan. Data kuis contoh berasal dari migrasi `002`. Buat role runtime terbatas mengikuti [petunjuk database](../../README.md#akun-dan-role-database), lalu jalankan [public-demo.sql](../../database/public-demo.sql) dengan mengganti `__RUNTIME_ROLE__` dengan nama role tersebut. Tetapkan `EDUQUEST_PUBLIC_DEMO=1` di proyek Vercel demo.
