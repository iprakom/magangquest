# PRODUCT REQUIREMENT DOCUMENT (PRD)
**Nama Produk:** Magang Quest - BPS (Gamified Internship Logbook)
**Versi:** Final (1.1 - Comprehensive with WIP Logic)
**Target Eksekusi:** Tim Manajemen & IT Pusdiklat BPS

---

## 1. Ringkasan Eksekutif (Executive Summary)

Magang Quest adalah sistem manajemen logbook magang berkonsep gamifikasi yang dirancang khusus untuk memantau produktivitas peserta magang di Pusdiklat BPS. Sistem ini mengubah presensi pasif menjadi pencatatan kontribusi aktif. Dengan memadukan penugasan langsung (Assigned), sayembara (Bounty), kontrol kapasitas beban kerja (WIP Slots), serta skenario penyelesaian masa magang (Endgame) yang realistis, sistem ini bertujuan mencegah idle time, menghindari eksploitasi kerja (burnout), dan menyajikan data performa SDM yang tervalidasi secara objektif.

---

## 2. Kriteria SMART

- **Specific:** Menciptakan platform pengganti presensi manual dengan fitur Onboarding, manajemen beban tugas berbasis Slot, batasan validasi SLA 3x24 jam kerja, dan mekanisme Grace Period kelulusan.
- **Measurable:** Menargetkan utilisasi kapasitas peserta magang selalu berada di atas 50% (tidak Idle) dan menekan angka tugas menggantung (In-Progress) menjadi 0 pada hari terakhir magang.
- **Achievable:** Menggunakan arsitektur database transaksional standar, cron job harian, dan autentikasi Google Sign-In yang sudah umum digunakan.
- **Relevant:** Menyelesaikan pain point manajerial terkait anak magang yang menganggur, penugasan yang tumpang tindih, serta administrasi serah-terima tugas yang sering berantakan di akhir masa magang.
- **Time-bound:** PRD ini siap digunakan sebagai acuan sprint planning MVP oleh tim pengembang IT.

---

## 3. Profil Pengguna (User Personas)

| Role | Deskripsi |
|------|-----------|
| **Anak Magang (Player)** | Eksekutor yang mencari/menerima tugas, mencatat progres harian, dan mengejar poin. |
| **Mentor (Ketua Tim / Anggota Tim)** | Pegawai Pusdiklat BPS yang membuat tugas, menyetujui usulan, melakukan validasi bukti kerja, dan memantau kapasitas (Idle Dashboard). |
| **Super Admin (HR / Bagian Umum)** | Pengelola Master Hari Libur, pemvalidasi awal akun anak magang, pengatur Global Limit, dan pemantau statistik global. |

---

## 4. Logika Bisnis & Alur Sistem (The Core Engine)

### 4.1 Identitas & Onboarding Quest (Fase Awal)

- **Login Ekosistem:** Wajib menggunakan Google Sign-In.
- **Gatekeeper (Tutorial Level):** Saat pertama kali masuk, akun berstatus Restricted (semua fitur tugas terkunci).
- **Misi Onboarding:** Anak magang wajib melengkapi:
  - Tipe Magang (SMA/SMK, Mahasiswa, atau Profesional).
  - Periode Magang (Start Date & End Date).
  - Dokumen Legalitas (Surat Pengantar/Proposal).
- **Unlock:** Setelah di-approve oleh Super Admin, anak magang mendapat +50 Poin, status menjadi Active, dan semua fitur utama (papan Quest) terbuka.

### 4.2 Tipe Quest & Manajemen Beban Kerja (WIP Limit / Sistem Slot)

Untuk mencegah burnout atau idle, peserta magang dibatasi jumlah pekerjaan yang bisa dipegang secara bersamaan (Work-In-Progress/WIP).

#### A. Logika Bisnis (Untuk Manajemen & Pengguna)

Global Limit adalah representasi "Beban Kerja Maksimal Standar". Bobot setiap Quest dihitung berdasarkan tingkat kesulitannya:

| Difficulty | Bobot (porsi dari Global Limit) | Max bersamaan (jika GL=4) |
|------------|--------------------------------|--------------------------|
| High (Berat) | 1 porsi penuh | 4 quest |
| Mid (Sedang) | 1/2 porsi | 8 quest |
| Low (Ringan) | 1/4 porsi | 16 quest |

#### B. Logika Sistem (Catatan Spesifik untuk Tim IT / Developer)

> Agar database tidak mengalami kendala perhitungan angka desimal, backend mengkonversi Global Limit menjadi **Poin Slot (Integer)** dengan multiplier = 4.

**Rumus Utama Backend:**
```
Max_Capacity_Slots = Global_Limit_Value * 4
```
Contoh: Jika Global Limit = 4, kapasitas mutlak user adalah **16 Poin Slot**.

**Konversi Bobot Quest:**
- Quest High = 4 Slot
- Quest Mid = 2 Slot
- Quest Low = 1 Slot

**Validasi Campuran (Contoh):**
- User A (Kapasitas 16 Slot) sedang mengerjakan: 1 High (4) + 2 Mid (4) + 4 Low (4) = **12 Slot terpakai**
- Sisa: 4 Slot
- Sistem **mengizinkan** user mengambil 1 Quest High lagi
- Sistem **menolak** jika user mengambil 1 High + 1 Low (karena 12 + 5 = 17 > 16)

#### C. Indikator Idle Dashboard

| Status | Utilisasi Slot | Aksi |
|--------|----------------|------|
| **Overloaded** | >= 100% | Tidak bisa ambil tugas lagi |
| **Active/Optimal** | 51% - 99% | Normal |
| **Idle/Underloaded** | <= 50% | Visualisasi peringatan untuk Mentor |

#### D. Tiga Jalur Akses Quest

1. **Assigned:** Tugas dari Mentor langsung ke anak magang spesifik (otomatis memotong slot).
2. **Bounty (Sayembara):** Tugas terbuka. Anak magang yang Idle bisa proaktif Claim.
3. **Usulan (Bottom-Up):** Anak magang mengusulkan ide proyek. Jika di-approve Mentor, menjadi Quest resmi.

### 4.3 Siklus Hidup Tugas (Task Lifecycle)

```
Open/Bounty → Assigned/To-Do → Active/In-Progress → In Review
                ↓                   ↓                    ↓
            Cancelled          Paused              Approved (Done)
                                                       ↓
                                                  Revise (Returned)
                                                       ↓
                                              Failed/Expired (Hoarding)
```

| Status | Deskripsi |
|--------|-----------|
| **Open/Bounty** | Sayembara tersedia untuk publik |
| **Assigned/To-Do** | Terkunci untuk satu orang, siap dikerjakan |
| **Active/In-Progress** | Sedang dikerjakan. Wajib update progres harian |
| **Paused** | Ditangguhkan (mengembalikan kapasitas slot sementara) |
| **In Review** | Anak magang submit bukti kerja. Menunggu validasi Mentor |
| **Approved (Done)** | Mentor setuju, tugas selesai, poin cair |
| **Revise (Returned)** | Hasil kurang layak, dikembalikan dengan catatan (kena penalti poin) |
| **Cancelled** | Dibatalkan mandiri oleh anak magang (kena penalti poin) |
| **Failed/Expired** | Ditelantarkan (Hoarding) hingga kadaluarsa (kena penalti berat) |

### 4.4 Fase Penyelesaian & Masa Tenggang (Endgame Phase)

| Fase | Timeline | Deskripsi | Konsekuensi Poin |
|------|----------|-----------|------------------|
| **Critical Zone** | H-10 | Akses Bounty/Assigned dikunci. Fokus Clearance | - |
| **End Date (H-0)** | H-0 23:59 | Jika In-Progress = 0 → Lulus Sempurna | +200 Graduation Bonus |
| **Grace Period** | H+1 s/d H+7 | Tidak bisa ambil tugas baru, wajib selesaikan sisa | -10 poin/hari |
| **Force Close** | H+8 | Sisa tugas dibatalkan paksa, akun dibekukan | -50 poin |

---

## 5. Mesin Gamifikasi (Poin, Streak & Penalti)

### Tabel A: Matriks Poin Aktivitas Dasar

| Aksi / Status | Dampak Poin | Keterangan |
|---------------|-------------|------------|
| Progress To-Do | +10 | Micro-points entri progres harian |
| Quest Approved | +100 | Macro-points saat tugas disetujui tuntas |
| Usulan Quest Baru | +20 | Inisiatif bottom-up yang disetujui |
| Revisi (Revise) | -10 | Penalti ringan hasil kerja kurang sesuai |
| Batal (Cancel) | -10 | Penalti membatalkan komitmen |
| Hoarding/Expired | -50 | Penalti berat menelantarkan tugas >10 hari kerja |

### Tabel B: Matriks Kedisiplinan & Fase Akhir

| Aksi / Status | Dampak Poin | Keterangan |
|---------------|-------------|------------|
| Onboarding Lulus | +50 | Modal awal (Welcome Bonus) |
| Lulus Sempurna (H-0) | +200 | Graduation Bonus (0 tugas tertunggak di akhir) |
| Late Penalty | -10/hari | Potongan otomatis di Masa Tenggang |
| Force Close (H+8) | -50 | Penutupan paksa tunggakan |
| Streak 7 Hari | +50 | Input progres harian berturut-turut tanpa jeda |
| Streak 14 Hari | +100 | Bonus kelipatan |
| Streak 21 Hari | +200 | Bonus kelipatan |
| Streak 30 Hari | +500 | Bonus kelipatan maksimal |

> **Catatan:** Backfilling (isi mundur) tetap dapat poin dasar, tapi mereset Streak ke 0.

---

## 6. Otomasi Sistem & Aturan SLA

- **SLA Mentor (3x24 Jam Kerja):** Jika status In Review didiamkan oleh Mentor selama 3x24 jam kerja, Cron Job otomatis mengubahnya menjadi Approved.
- **Master Hari Libur:** Seluruh perhitungan SLA Mentor dan Penalti Hoarding (>10 hari) wajib mengecualikan akhir pekan dan tanggal merah yang diatur oleh Super Admin.

---

## 7. Daftar User Story (Panduan Acceptance Criteria)

### Epic 1: Anak Magang (Player)

| # | User Story |
|---|-----------|
| US-01 | Sebagai Player, saya ingin menyelesaikan "Onboarding Quest" agar sistem mengenali saya dan memberi saya Poin Perdana. |
| US-02 | Sebagai Player, saya ingin melihat sisa "Nyawa" (Countdown H-x End Date) di dashboard. |
| US-03 | Sebagai Player, saya ingin mengklaim Bounty yang tersedia selama Slot Kapasitas saya belum penuh. |
| US-04 | Sebagai Player, saya ingin mencatat progres harian (Micro-points) dan melampirkan bukti kerja agar tugas bisa berstatus In Review. |
| US-05 | Sebagai Player, saya ingin mendapat peringatan "Fase Krusial" pada H-10 agar saya fokus menyelesaikan pekerjaan (Clearance) untuk mengejar Graduation Bonus. |
| US-06 | Sebagai Player, saya ingin bisa menuntaskan tugas di Masa Tenggang (maksimal H+7) walau sadar poin saya akan bocor -10 poin/hari. |

### Epic 2: Mentor (Pegawai)

| # | User Story |
|---|-----------|
| US-07 | Sebagai Mentor, saya ingin melihat Idle Dashboard berbasis ruangan untuk memantau siapa anak magang yang sedang menganggur (Underloaded). |
| US-08 | Sebagai Mentor, saya ingin membuat tugas Assigned langsung ke anak magang, atau mempublikasikan tugas Bounty ke publik. |
| US-09 | Sebagai Mentor, saya ingin melakukan validasi (Approve / Revise) terhadap bukti kerja sebelum batas SLA 3x24 jam habis. |
| US-10 | Sebagai Mentor, saya tidak direpotkan dengan perpanjangan waktu manual, karena sistem otomatis mengatur Masa Tenggang anak magang. |

### Epic 3: Super Admin (HR / Bagian Umum)

| # | User Story |
|---|-----------|
| US-11 | Sebagai Admin, saya ingin memvalidasi dokumen legalitas magang di fase Onboarding. |
| US-12 | Sebagai Admin, saya ingin mengatur "Master Hari Libur Nasional" agar kalkulasi otomatisasi sistem berjalan adil di hari kerja produktif saja. |
| US-13 | Sebagai Admin, saya ingin mengatur Global Limit agar batas maksimal pekerjaan magang selalu proporsional. |
| US-14 | Sebagai Admin, saya ingin memantau Leaderboard Global dan mengekspor rekap nilai akhir anak magang yang sudah lulus (Graduated/Frozen). |

---

## 8. Panduan Teknis IT (Developer Notes)

### Middleware Gatekeeper
Amankan seluruh endpoint API terkait Quest. Jika `status_onboarding != approved`, kembalikan error 403 (Forbidden).

### Cron Jobs Engine
Diperlukan penjadwalan skrip harian tengah malam untuk:
1. **Cek End_Date - 10:** Aktifkan `is_critical_zone`.
2. **Cek End_Date (H-0):** Jika tugas = 0 berikan +200 poin dan bekukan akun. Jika tugas > 0 lemparkan ke Masa Tenggang.
3. **Cek Masa Tenggang:** Potong -10 poin/hari (Late Penalty).
4. **Cek End_Date + 8:** Eksekusi Force Close dan ubah hak akses jadi Read-Only.
5. **Cek tugas In Review > 3 hari kerja:** Eksekusi Auto-Approve.

### Audit Trail (Ledger Poin)
Jangan sekadar melakukan UPDATE saldo. Gunakan tabel `points_log` yang mencatat detail histori penambahan dan pengurangan setiap user secara mutasi (Debit/Kredit).

---

**Akhir Dokumen PRD**
