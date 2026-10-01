# Panduan Deployment ke Heroku (INFENTRA 2.0)

Panduan ini berisi langkah-langkah *deployment* aplikasi rekrutmen INFENTRA 2.0 ke Heroku melalui Windows PowerShell.

> **Perhatian:** Semua data seperti flag HMIF, perubahan nama, dan *candidate* yang ada di database lokal **tidak akan ikut terbawa** ke Heroku. Database *production* akan dimulai dari keadaan kosong (kecuali di-*seed* dengan POH dan aturan statis).

## 1. Setup & Pembuatan Aplikasi

Buka PowerShell di direktori proyek ini, kemudian jalankan:

```powershell
# Login ke akun Heroku
heroku login

# Buat aplikasi baru (Ganti 'infentra-recruitment-app' dengan nama aplikasi yang tersedia)
heroku apps:create infentra-recruitment-app

# Tambahkan Add-on Heroku Postgres (Mini plan - *BERBAYAR* ~$5/bulan, wajib untuk produksi)
heroku addons:create heroku-postgresql:mini
```

## 2. Buildpacks & Config Vars

Aplikasi ini membutuhkan *buildpack* Node.js (untuk kompilasi Tailwind CSS) dan PHP. Node.js harus ditambahkan *sebelum* PHP.

```powershell
# Set buildpacks
heroku buildpacks:add heroku/nodejs
heroku buildpacks:add heroku/php

# Generate APP_KEY lokal untuk digunakan di production
$APP_KEY = php artisan key:generate --show

# Konfigurasi Environment Variables
heroku config:set APP_NAME="INFENTRA 2.0"
heroku config:set APP_ENV="production"
heroku config:set APP_DEBUG="false"
heroku config:set APP_URL="https://infentra-recruitment-app.herokuapp.com"
heroku config:set APP_TIMEZONE="Asia/Jakarta"
heroku config:set APP_LOCALE="id"
heroku config:set LOG_CHANNEL="stderr"
heroku config:set DB_CONNECTION="pgsql"
heroku config:set SESSION_DRIVER="database"
heroku config:set SESSION_SECURE_COOKIE="true"
heroku config:set CACHE_STORE="database"
heroku config:set QUEUE_CONNECTION="sync"
heroku config:set APP_KEY=$APP_KEY

# Convert poh.csv menjadi Base64 untuk POH_CSV_BASE64
$POH_CSV = [Convert]::ToBase64String([IO.File]::ReadAllBytes("database/seeders/data/poh.csv"))
heroku config:set POH_CSV_BASE64=$POH_CSV
```
*(Catatan: Variabel `DATABASE_URL` sudah otomatis diisi oleh Heroku saat Postgres ditambahkan, dan Laravel di Heroku otomatis membaca `DATABASE_URL` ke konfigurasi DB PostgreSQL).*

## 3. Deployment & Setup Database

Sebelum *deploy*, tambahkan script `heroku-postbuild` ke `package.json` Anda. (Atau Anda cukup biarkan Node Buildpack menjalankan `npm run build` jika `build` ada di scripts, karena otomatis dilakukan jika Node buildpack di-load sebelum PHP).

```powershell
# Deploy kode ke Heroku
git push heroku master

# Setelah build sukses, Procfile secara otomatis telah memanggil 'php artisan migrate --force'.
# Lakukan Seeding database (Division, RubricAspect, POH Users, dll.)
heroku run php artisan db:seed

# Cek daftar user POH
heroku run php artisan poh:users
```

## 4. Set Password untuk Akun POH

Password tidak ada *default*-nya dan tidak di-*seed* karena alasan keamanan. Anda harus menyetel password masing-masing secara manual. Perintah ini tidak akan me-log password:

```powershell
# Ganti 12345678 dengan NIM yang asli
heroku run php artisan poh:set-password 12345678
```

## 5. Live Steps (Pengaturan Aplikasi)

Lakukan langkah-langkah di bawah secara berurutan *di dalam* halaman Admin Heroku (`/admin/login`):

1. **Import CSV Kandidat:** Masuk ke halaman Kandidat, klik aksi "Import CSV", dan unggah *file* CSV final dari Form Pendaftaran.
2. **Tandai HMIF:** Tandai secara manual kandidat yang memiliki *flag* HMIF.
3. **Koreksi Waktu Ishoma (Breaks):** Buka tabel Jadwal/Hari Wawancara, sesuaikan jam dan durasi blok istirahat (Dzuhur & Ashar) mengikuti waktu masuk salat yang sesungguhnya di tanggal 3-4 Oktober.
4. **Generate Jadwal:** Masuk ke halaman Kandidat dan eksekusi fungsi "Generate Jadwal".
5. **Publikasi:** Informasikan *link* `https://infentra-recruitment-app.herokuapp.com/jadwal` kepada para kandidat.

## 6. Persiapan Hari H & Skala Dyno

Karena jadwal wawancara sangat padat (3-4 Oktober, mulai jam 08:00), pastikan *dyno* tidak tertidur (*sleep*) atau melambat saat wawancara berlangsung.

```powershell
# Upgrade dyno ke Basic atau Standard-1X agar tidak sleep (*BERBAYAR*)
heroku ps:type web=basic
```

## 7. Manajemen Backup Postgres (*BERBAYAR* jika melebihi batas)

Lakukan backup manual di saat-saat krusial untuk mencegah kehilangan data:

```powershell
# Backup 1: Sebelum event wawancara (Setelah Jadwal final terbentuk)
heroku pg:backups:capture

# Backup 2: Setelah Hari Pertama Wawancara Selesai (Malam 3 Okt)
heroku pg:backups:capture

# Backup 3: Setelah seluruh wawancara selesai
heroku pg:backups:capture
```

## 8. Rollback Release

Jika ada *deployment* kode yang gagal (terjadi *bug* fatal), segera mundur ke *release* sebelumnya:

```powershell
# Lihat daftar rilis
heroku releases

# Rollback ke versi sebelumnya (misal rilis v15)
heroku rollback v15
```
