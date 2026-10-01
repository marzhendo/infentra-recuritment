# PRD: INFENTRA Recruitment Hub

**Sistem Seleksi Calon Panitia & Penjadwalan Wawancara INFENTRA 2.0**

|  |  |
| --- | --- |
| Departemen | Talent Development and Inovation |
| Versi | 1.3 (siap dibangun; Open Questions tidak memblokir) |
| Tanggal | 1 Oktober 2026 |
| Target demo | 2 Oktober 2026 |
| Hari-H wawancara | 3-4 Oktober 2026 (offline, ruang DC-302) |

---

## 1. Latar Belakang & Masalah

INFENTRA 2.0 membuka pendaftaran panitia (saat ini 67 pendaftar, kemungkinan 70+). Data masuk lewat Google Form ke Google Sheets dengan banyak kolom berisi link Drive (sertifikat, CV, portofolio). Masalah yang ingin diselesaikan:

- Sulit memilah pendaftar per divisi (Pilihan 1 dan 2) dan membaca berkas satu per satu lewat link Drive.
- Penjadwalan sekitar 90 calon ke slot 10 menit selama 2 hari dengan spreadsheet rawan dobel-booking dan sulit dibagikan ke calon.
- Penilaian tersebar, tidak seragam, dan susah direkap.

## 2. Tujuan

1. Semua data pendaftar terpusat, bisa difilter per divisi/angkatan, dan berkasnya bisa dipreview langsung.
2. Jadwal wawancara 3-4 Oktober tersusun tanpa bentrok, dan calon bisa melihat jadwalnya sendiri.
3. Penilaian wawancara tercatat dengan rubrik seragam dan bisa direkap/di-export.

**Bukan tujuan (v1):** pendaftaran langsung di website, notifikasi WhatsApp/email, pengumuman kelulusan otomatis.

## 3. Target User

| Peran | Siapa | Kebutuhan utama |
| --- | --- | --- |
| Admin | Ketua Pelaksana (+ SC/PIC sesuai kebutuhan) | Semua akses: import, atur jadwal, lihat semua nilai, export |
| Pewawancara Umum | Ketua Pelaksana | Memimpin sesi, lihat antrean, isi nilai |
| Koor Divisi | Koordinator tiap divisi | Lihat antrean dan berkas calon; menilai hanya calon yang memilih divisinya (Pilihan 1/2); melihat nilai sesama koor |
| Calon Panitia | Pendaftar | Melihat jadwal wawancara miliknya (read-only) |

## 4. Alur Wawancara (terkonfirmasi)

- Format **panel**: Ketua Pelaksana dan seluruh koor divisi berada di satu ruangan (DC-302, satu-satunya ruangan yang dipinjam).
- Satu calon = satu sesi **10 menit total**. Ketua Pelaksana membuka dengan pertanyaan umum, lalu koor **Pilihan 1 dan Pilihan 2** bertanya bergantian sesuai divisi yang dipilih. Ketua Pelaksana dan semua koor duduk berjajar menghadap calon.
- Calon yang Pilihan 1 dan Pilihan 2-nya sama tidak punya aturan khusus: karena satu divisi hanya punya satu koor, hanya koor itu (bersama Ketua Pelaksana) yang menilai.
- Mulai sekitar pukul 08.00. Data CSV per 30 September: 91 baris, 89 orang unik (2 orang mengirim form dua kali), dibagi ke 2 hari, sekitar 45 calon per hari.

**Istirahat (ishoma):** 1 jam per blok, wajib ada setiap masuk waktu sholat; ada dua blok per hari, **Dzuhur dan Ashar**. Jam mulai tiap blok diatur admin sebagai data yang bisa diubah (bukan dikunci di kode).

**Calon HMIF:** anak HMIF tidak perlu wawancara, sehingga tidak diberi slot. Jumlah sesi per hari berkurang dan waktu yang bebas dipakai untuk memajukan istirahat atau mempercepat selesai; admin menyesuaikan jam blok ishoma setelah melihat perkiraan jam selesai dari generator.

**Estimasi beban:** 89 sesi x 10 menit = sekitar 14,8 jam, atau sekitar 7,4 jam bersih per hari. Mulai 08.00 dengan dua blok ishoma (masing-masing 1 jam), selesai sekitar 17.25 jika semua 89 calon diwawancarai; setiap calon HMIF yang dibebaskan memajukan jam selesai total 10 menit (sekitar 5 menit per hari).

**Implikasi ke sistem:** karena semua pewawancara hadir di ruangan yang sama, penjadwalan tidak perlu memeriksa ketersediaan tiap koor. Masalahnya menjadi **mengurutkan calon ke slot 10 menit** per hari tanpa dobel-booking. Sistem cukup menampilkan, untuk setiap calon, koor mana yang relevan (Pilihan 1 dan 2).

## 5. Fitur & Prioritas

### P0 (wajib untuk demo 2 Oktober)

1. **Login internal** untuk POH, akun di-seed dari daftar POH. Alur: halaman awal menampilkan **daftar divisi/jabatan**; memilih satu menampilkan nama anggota POH-nya; pengguna memilih namanya lalu login memakai **NIM**. Role: Admin dan Koor. Pembatasan percobaan login (rate limit) wajib ada. Lihat Open Question 1 tentang keamanan NIM-saja.
2. **Import data dari Google Form** via upload CSV hasil export Sheets. Import bisa diulang (pendaftar baru tidak menduplikasi yang lama).
3. **Daftar & detail pendaftar:** filter per divisi (Pilihan 1/2), angkatan, status; pencarian nama; halaman detail berisi semua jawaban form. Kasus Pilihan 1 = Pilihan 2 hanya ditandai (badge). **Penandaan anak HMIF:** setiap calon punya penanda `is_hmif` (bebas wawancara) yang bisa diubah satu per satu atau lewat aksi massal di daftar, dengan filter "HMIF", dan harus bisa diatur **sebelum** jadwal di-generate.
4. **Preview berkas** (sertifikat, CV, portofolio) di halaman detail lewat embed Google Drive, dengan tombol "buka di tab baru" sebagai cadangan.
5. **Manajemen slot & penjadwalan:** admin mengatur hari, jam mulai, durasi 10 menit, dan **blok ishoma** (mulai dan durasi, bisa lebih dari satu per hari); menetapkan calon ke slot (manual dan **generate otomatis** berurutan yang melewati blok ishoma); **melewati calon bertanda HMIF** (tidak diberi slot); mencegah satu slot terisi dua calon dan satu calon terjadwal dua kali; menampilkan perkiraan jam selesai setiap hari. Jadwal bisa diedit setelah di-generate.
6. **Halaman jadwal publik (read-only)** untuk calon: cari nama (pencarian sebagian nama), lihat hari, jam, dan lokasi (DC-302). Calon bertanda HMIF melihat keterangan "Dibebaskan dari wawancara". Hanya menampilkan nama dan jadwal, tanpa data pribadi lain.
7. **Form penilaian** per sesi dengan rubrik (bagian 6), skala 1-5, dan catatan. Penilai hanya Ketua Pelaksana dan koor Pilihan 1/2 milik calon tersebut; nilai terlihat oleh sesama koor.
8. **Keputusan akhir per calon:** tiap koor Pilihan 1/2 memberi status **Lolos / Tidak Lolos / Cadangan** untuk divisinya. Jika keputusan kedua koor berbeda, **Pilihan 1 selalu diprioritaskan**: bila koor Pilihan 1 menyatakan Lolos, penempatan akhir adalah divisi Pilihan 1. Jika Pilihan 1 bukan Lolos, sistem menampilkan keputusan Pilihan 2 dan admin menetapkan penempatan **secara manual**. Admin melihat dan menetapkan hasil akhir. Calon HMIF (tanpa wawancara) tetap mendapat keputusan akhir, tidak otomatis Lolos; koor memutuskan berdasarkan berkas pendaftaran.
9. **Export** jadwal, nilai, dan keputusan ke Excel/CSV.

### P1 (jika waktu cukup)

- Rekap nilai per divisi dan peringkat.
- Status pendaftar (terdaftar, dijadwalkan, selesai wawancara, dinilai).
- Tombol salin/bagikan link jadwal.
- Status "sedang berlangsung" untuk admin memantau antrean.

### P2 (setelah wawancara)

- Sinkron otomatis dengan Google Sheets API.
- Pengumuman kelulusan dan penempatan divisi.
- Notifikasi WhatsApp/email.

## 6. Rubrik Penilaian (v1)

Tujuh aspek, sesuai daftar terbaru yang kamu kirim: Komitmen & Tanggung Jawab, Komunikasi, Problem Solving, Kerja Sama Tim, Inisiatif & Proaktif, Ketersediaan Waktu, Motivasi & Pemahaman Peran. Daftar ini sudah dikonfirmasi menggantikan daftar delapan aspek sebelumnya.

Skala **1-5** per aspek, disimpan **per pewawancara per sesi**. Bobot antar-aspek belum ditentukan; untuk v1 rekap memakai rata-rata sederhana (tanpa bobot). Aspek disimpan sebagai data yang bisa diubah admin, sehingga daftar ini mudah diganti tanpa ubah kode.

## 7. Model Data (ringkas)

- `users` (nama, email, password, role, division_id)
- `divisions` (nama, alias; "Usdakom" dan "Danus & Konsum" adalah satu divisi dengan dua nama, sehingga nilai di form dipetakan ke divisi yang sama)
- `candidates` (nama, nim nullable, angkatan, pilihan_1, pilihan_2, link sertifikat/CV/portofolio, raw_form_timestamp, status, is_hmif)
- `interview_slots` (tanggal, jam_mulai, jam_selesai, ruangan, candidate_id nullable)
- `scores` (slot_id, interviewer_id, nilai 1-5 per aspek, catatan)
- `decisions` (candidate_id, division_id, status \[lolos/tidak_lolos/cadangan\], catatan, decided_by) — satu keputusan per koor per divisi pilihan calon
- `placements` (candidate_id, division_id nullable, status_akhir, ditetapkan_oleh) — hasil akhir setelah aturan prioritas Pilihan 1
- `rubric_aspects` (nama, urutan, aktif)
- `import_logs` (waktu, jumlah baru/diperbarui)

Kunci import (`import_key`): nama ternormalisasi + nomor WhatsApp ternormalisasi. Baris dengan kunci sama = orang yang sama mengirim form ulang, data terbaru dipakai. Nama sama dengan WhatsApp berbeda dipisah dan diberi badge. NIM tidak bisa jadi kunci karena 72 dari 91 baris kosong.

## 8. Rekomendasi Teknis

| Aspek | Rekomendasi | Alasan |
| --- | --- | --- |
| Framework | **Laravel + Filament** (Livewire), satu aplikasi (Opsi A, sudah diputuskan) | Panel admin CRUD, filter, tabel, dan form langsung jadi; paling cepat untuk target 2 hari; hanya satu deployment |
| Database | MySQL (atau SQLite jika hosting minim) | Cukup untuk skala puluhan sampai ratusan data |
| Import | Upload CSV, upsert | Paling cepat; Sheets API masuk P2 |
| Preview berkas | Ubah link `open?id=XXXX` menjadi `https://drive.google.com/file/d/XXXX/preview` dalam iframe | Tanpa download; file sudah dibagikan ke siapa saja dengan link |
| Auth | Email + password untuk POH | Sederhana; halaman jadwal calon tidak perlu login |
| Hosting | Heroku (aplikasi Laravel + Postgres); Vercel tidak dipakai | UI dirender server sehingga tidak ada frontend terpisah; bisa diakses dari HP pewawancara |
| Export | Laravel Excel | Standar untuk export xlsx/csv |

**Catatan hosting Heroku (hasil pencarian 30 September 2026):** tidak ada free tier. Eco dyno sekitar US$5/bulan tetapi tidur setelah 30 menit tanpa request (cold start); Basic dyno sekitar US$7/bulan dan selalu aktif; Postgres Essential-0 sekitar US$5/bulan. Biaya dihitung per detik, jadi untuk 3-4 Oktober pakai dyno always-on agar halaman tidak lambat saat dibuka calon dan koor. Cek ulang harga di dashboard Heroku sebelum deploy.

## 9. Kebutuhan Non-Fungsional

- Responsif di HP (pewawancara memakai HP/tablet di lokasi).
- Data pribadi (NIM, berkas, nilai) hanya bisa dilihat user login (Admin dan Koor); nilai terlihat oleh sesama koor sesuai keputusan POH.
- Halaman publik tidak boleh membocorkan data selain nama dan jadwal.
- Backup data sebelum hari-H (export rutin).

## 10. Risiko

| Risiko | Dampak | Mitigasi |
| --- | --- | --- |
| Preview Drive gagal untuk sebagian file (format tidak didukung/link rusak) | Berkas tidak tampil | Sediakan tombol "buka di tab baru" sebagai cadangan |
| NIM tidak ada di form | Sulit mengidentifikasi calon unik dan duplikat | Tambah kolom NIM yang bisa diisi admin/calon belakangan; cocokkan manual |
| Waktu sangat mepet (demo 2 Okt) | Fitur P1 tidak sempat | Kunci scope P0 sekarang; Sheets tetap jadi cadangan |
| Sesi 10 menit sangat ketat (ruang tunggal, 35 calon per hari) | Antrean molor, jadwal bergeser | Sediakan slot cadangan, tampilkan status "sedang berlangsung" untuk admin, dan beri buffer di jadwal publik |
| Cold start dyno Heroku Eco di hari wawancara | Halaman lambat/gagal dibuka | Pakai dyno always-on selama 3-4 Oktober |
| Pendaftar bertambah setelah import | Data tidak mutakhir | Import ulang bersifat upsert |

## 11. Rencana Waktu (usulan)

- **1 Okt (malam):** export CSV dari Sheets; setup proyek, migration, auth, import CSV.
- **1 - 2 Okt:** daftar + detail calon + preview berkas + penanda HMIF; slot dan generator jadwal (dua blok ishoma), halaman jadwal publik, form nilai, keputusan akhir, export; deploy ke Heroku.
- **2 Okt:** demo ke POH, import ulang data final setelah pendaftaran ditutup, tandai anak HMIF, generate jadwal, buat akun POH.
- **3 - 4 Okt:** pemakaian di wawancara (dyno always-on); perbaikan cepat jika ada masalah.

## 12. Open Questions (tidak memblokir pembangunan)

1. **Keamanan login NIM-saja:** NIM bukan rahasia (tercantum di KTM dan daftar panitia), sehingga siapa pun yang tahu NIM seorang koor bisa masuk dan melihat CV, nilai, dan keputusan calon. Usulan: tambahkan satu kode akses bersama yang dibagikan di grup POH, atau PIN per orang, di samping NIM. Perlu keputusanmu; sampai itu, alur NIM-saja dibangun dengan rate limit.
2. **Jam mulai blok ishoma** Dzuhur dan Ashar tiap hari (default sementara bisa diatur di admin).
3. **Urutan calon** saat generate jadwal dan pembagian ke hari 1 dan hari 2: berdasarkan waktu daftar, angkatan, atau acak?
4. **Bobot rubrik:** rata-rata sederhana cukup, atau ada aspek yang lebih penting?
5. **Pilihan 1 sama dengan Pilihan 2** (36 dari 91 baris): terjawab. Koor tiap pilihan mewawancarai bergantian; untuk pilihan yang sama itu otomatis satu koor, dan keputusan hanya satu per divisi. Sistem hanya menampilkan badge di daftar.
6. **Jam selesai** dan batas pemakaian ruang DC-302 tiap hari.
7. **Role non-koor:** apakah SC, PIC, sekretaris, dan bendahara boleh melihat semua data (Admin) atau hanya-lihat?

## Lampiran A. Akun POH (untuk seed pengguna)

Login memakai NIM (disimpan di database saat seed, tidak ditulis di dokumen ini).

| Jabatan | Nama | Role di sistem |
| --- | --- | --- |
| Ketua Pelaksana | Marzhendo Galang | Admin |
| Steering Committee | Fatir Gibran, Aedil Riski Ansyah | Admin (melihat semua) |
| PIC | Ridha Akifah | Admin (melihat semua) |
| Sekretaris Umum | Salsadilla Hanny | Admin (melihat semua) |
| Sekretaris Kegiatan | Rista Sania | Admin (melihat semua) |
| Bendahara Umum | Sarah Maulidya | Admin (melihat semua) |
| Bendahara Kegiatan | Ahmad Luthfi | Admin (melihat semua) |
| Koor Acara | Rizky Al Kahfi | Koor |
| Koor Humas | Raysa Rahma | Koor |
| Koor IT Team | Aqilla Rachel | Koor |
| Koor PDD | Muhammad Zacky | Koor |
| Koor Usdakom (Danus & Konsum) | Dealova Agta | Koor |
| Koor Perkap | Nawwar Ulayya | Koor |
| Koor Sponsorship | Aqilah Izani | Koor |
| Koor Keamanan | Brilyant Keyza | Koor |

Catatan: pembagian role selain Ketua Pelaksana adalah usulan (Open Question 7).