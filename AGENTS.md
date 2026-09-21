# Rekap Penjualan / Elephant POS - Agent Guidelines

Dokumen ini berisi panduan teknis, arsitektur, alur kerja (workflow) rutin, serta aturan penting dalam pengembangan dan pemeliharaan repositori **Rekap Penjualan (Elephant POS)**.

---

## 1. Ringkasan Arsitektur Proyek

Proyek ini terdiri dari dua bagian utama yang saling terintegrasi:

1. **Backend & Web Dashboard (Laravel)**
   - Direktori: Root workspace (`d:\laravel\rekap-penjualan\`)
   - Framework: Laravel (PHP 8.2+)
   - Database: MySQL / MariaDB (nama database: `rekap_penjualan`)
   - Domain Produksi: `https://pos.moonbyte.my.id` (Server IP: `103.150.197.121`)
   - Route API Mobile: `routes/api.php`
   - Controller Utama API Mobile: `app/Http/Controllers/Api/MobileApiController.php`

2. **Mobile App (Flutter / Android POS)**
   - Direktori: `flutter_app/`
   - Nama Aplikasi: Elephant POS (Elephant Cells)
   - Entry Point: `flutter_app/lib/main.dart`
   - Target Platform: Android APK (`elephant-pos.apk`)

---

## 2. Alur Kerja Wajib: Update Versi & Build APK

Setiap kali melakukan pembaruan pada aplikasi Flutter, ikuti langkah-langkah terstandar berikut agar notifikasi update di HP pengguna berfungsi dengan benar dan tidak terjadi loop update:

### A. Sinkronisasi Versi (3 Lokasi Wajib Sama)
Pastikan nomor versi dan build number dinaikkan secara sinkron di 3 file:
1. **`flutter_app/pubspec.yaml`**
   Contoh: `version: 1.1.2+13` (versi: 1.1.2, build: 13)
2. **`flutter_app/lib/services/update_service.dart`**
   ```dart
   static const String currentVersion = '1.1.2';
   static const int currentVersionCode = 13;
   ```
3. **`app/Http/Controllers/Api/MobileApiController.php`** (di dalam method `appVersion()`)
   ```php
   'version' => '1.1.2',
   'version_code' => 13,
   'title' => 'Pembaruan Tersedia (v1.1.2)',
   'release_notes' => "• Catatan rilis di sini...",
   'download_url' => 'https://pos.moonbyte.my.id/download/elephant-pos.apk?v=' . time(),
   ```

### B. Logo & Launcher Icon
- Jika ada pembaruan logo aplikasi:
  - File logo master diletakkan di `logo.png` dan `flutter_app/assets/images/logo.png`.
  - Icon launcher Android berada di `flutter_app/android/app/src/main/res/mipmap-*` (`ic_launcher.png`).
  - Pastikan icon homescreen di semua folder `mipmap` (`mdpi`, `hdpi`, `xhdpi`, `xxhdpi`, `xxxhdpi`) diperbarui sesuai logo.

### C. Build APK Release
Jalankan build Flutter dari direktori `flutter_app`:
```powershell
flutter build apk --release
```
*Hasil build berada di: `flutter_app/build/app/outputs/flutter-apk/app-release.apk`*

### D. Salin APK ke 2 Lokasi Wajib
Setelah build berhasil, salin APK ke dua lokasi berikut:
1. `d:\laravel\rekap-penjualan\elephant-pos.apk` (Root repo)
2. `d:\laravel\rekap-penjualan\public\download\elephant-pos.apk` (Endpoint unduhan publik server)

Contoh perintah PowerShell:
```powershell
Copy-Item "d:\laravel\rekap-penjualan\flutter_app\build\app\outputs\flutter-apk\app-release.apk" -Destination "d:\laravel\rekap-penjualan\elephant-pos.apk" -Force
Copy-Item "d:\laravel\rekap-penjualan\elephant-pos.apk" -Destination "d:\laravel\rekap-penjualan\public\download\elephant-pos.apk" -Force
```

### E. Git Commit & Push
Push semua perubahan kode beserta binary APK ke GitHub:
```powershell
git -C "d:\laravel\rekap-penjualan" add elephant-pos.apk public/download/elephant-pos.apk flutter_app/ app/
git -C "d:\laravel\rekap-penjualan" commit -m "v1.1.2: rincian pembaruan"
git -C "d:\laravel\rekap-penjualan" push
```

---

## 3. Database & Reset Master Data

### Perhatian Nama Tabel (Hindari Typo Umum):
- Tabel Retur Penjualan: **`sales_returns`** (BUKAN `sale_returns`).
- Tabel Baris Jurnal Akuntansi: **`journal_entry_lines`** (BUKAN `journal_entry_items`).

### Reset Transaksi & Master Data
Untuk mengosongkan semua data penjualan, retur, pembelian, kas, stok, produk, customer, dan supplier tanpa menghapus Chart of Accounts (COA) / Akun Keuangan:
- **Gunakan Command Artisan:**
  ```powershell
  php artisan app:reset-master-data --force
  ```
  *Command ini otomatis menonaktifkan foreign key checks, membersihkan tabel transaksi & master, dan memastikan akun owner tetap ada.*
- **Jika via phpMyAdmin / SQL query langsung:**
  Wajib bungkus query dengan `SET FOREIGN_KEY_CHECKS = 0;` di awal dan `SET FOREIGN_KEY_CHECKS = 1;` di akhir agar tidak terjadi error foreign key constraint (#1701).

---

## 4. Environment & Command Execution (Windows)

- OS: Windows, Shell: PowerShell.
- **Sandbox Permission**: Saat menjalankan git command, gunakan flag `-C "d:\laravel\rekap-penjualan"` dan parameter `BypassSandbox: true` untuk menghindari masalah *Permission Denied* pada shell sandbox lokal Windows.

---

## 5. Deployment ke Server Produksi

Setelah agen melakukan `git push` ke branch `main`, langkah yang perlu dijalankan di server produksi (`pos.moonbyte.my.id`):
```bash
git pull origin main
php artisan optimize:clear
```
Jika ada perubahan struktur database / seeder:
```bash
php artisan migrate --force
```
Jika server perlu reset data:
```bash
php artisan app:reset-master-data --force
```
