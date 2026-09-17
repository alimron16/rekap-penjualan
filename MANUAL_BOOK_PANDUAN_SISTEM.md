# BUKU PANDUAN PENGGUNAAN SISTEM (USER MANUAL BOOK)
## ELEPHANT POS & SISTEM INFORMASI AKUNTANSI KASIR TERPADU

---

## DAFTAR ISI
1. [Pendahuluan & Konsep Arsitektur Sistem](#1-pendahuluan--konsep-arsitektur-sistem)
2. [Hierarki Peran & Hak Akses Pengguna (Access Control)](#2-hierarki-peran--hak-akses-pengguna-access-control)
3. [Halaman Login & Autentikasi](#3-halaman-login--autentikasi)
4. [Dashboard Utama (Executive & Front-Liner View)](#4-dashboard-utama-executive--front-liner-view)
5. [Modul Kasir POS: Penjualan Retail (Eceran)](#5-modul-kasir-pos-penjualan-retail-eceran)
6. [Modul Kasir POS: Penjualan Grosir](#6-modul-kasir-pos-penjualan-grosir)
7. [Modul Kasir: Tarik Tunai POS (Cash Withdrawal)](#7-modul-kasir-tarik-tunai-pos-cash-withdrawal)
8. [Modul Produk Elektrik & Pulsa (Multi Server PPOB)](#8-modul-produk-elektrik--pulsa-multi-server-ppob)
9. [Modul Transfer Antar Akun & Agen Transfer Bank](#9-modul-transfer-antar-akun--agen-transfer-bank)
10. [Modul Retur Penjualan (Sales Return)](#10-modul-retur-penjualan-sales-return)
11. [Modul Pembelian Barang & Hutang Supplier](#11-modul-pembelian-barang--hutang-supplier)
12. [Modul Piutang Pelanggan & Pembayaran Tempo](#12-modul-piutang-pelanggan--pembayaran-tempo)
13. [Modul Penyesuaian Stok Fisik (Stock Opname)](#13-modul-penyesuaian-stok-fisik-stock-opname)
14. [Modul Kas Masuk & Kas Keluar (Beban Operasional)](#14-modul-kas-masuk--kas-keluar-beban-operasional)
15. [Modul Bagan Akun / Chart of Accounts (COA) & Modal Awal](#15-modul-bagan-akun--chart-of-accounts-coa--modal-awal)
16. [Modul Master Data (Barang, Pelanggan, Supplier, Cabang)](#16-modul-master-data-barang-pelanggan-supplier-cabang)
17. [Modul Laporan Keuangan & Rekapitulasi](#17-modul-laporan-keuangan--rekapitulasi)
18. [Modul Manajemen Pengguna & Pengaturan Hak Akses](#18-modul-manajemen-pengguna--pengaturan-hak-akses)
19. [Modul Pengaturan Toko & Printer Thermal Bluetooth/USB](#19-modul-pengaturan-toko--printer-thermal-bluetoothusb)

---

## 1. PENDAHULUAN & KONSEP ARSITEKTUR SISTEM

Aplikasi Elephant POS dirancang khusus untuk toko ritel, konter seluler, grosir, dan agen keuangan dengan integrasi penuh antara **Point of Sale (POS)** dan **Sistem Akuntansi Buku Berpasangan (Double-Entry Bookkeeping)**. 

Setiap transaksi yang dilakukan di kasir tidak hanya mencatat struk belanja, tetapi secara otomatis:
1. **Memotong/Menambah Stok Fisik Barang** secara *real-time* berbasis ACID Transaction (mencegah stok minus/balapan transaksi).
2. **Membentuk Jurnal Akuntansi Otomatis** (Debet & Kredit seimbang).
3. **Mengupdate Nilai Buku Kas/Bank/Piutang/Hutang/HPP**.
4. **Memperbarui Laporan Laba Rugi dan Neraca** seketika itu juga tanpa proses tutup buku manual.

---

## 2. HIERARKI PERAN & HAK AKSES PENGGUNA (ACCESS CONTROL)

Sistem menerapkan prinsip *Least Privilege Security* dengan fleksibilitas penuh di bawah kendali **Super Admin**:

| Modul / Izin Fitur | Super Admin | Administrator | Toko / Kasir (Front-Liner) | Pengaruh Terhadap Akses & Keamanan |
| :--- | :---: | :---: | :---: | :--- |
| **Kasir POS (Retail & Grosir)** | Ya | Ya | Ya | Melayani penjualan harian & cetak nota thermal |
| **Produk Multi (Pulsa/PPOB)** | Ya | Ya | Ya | Transaksi pulsa/token listrik menggunakan saldo deposit |
| **Tarik Tunai POS** | Ya | Ya | Ya | Penarikan uang tunai nasabah dengan potong kas laci |
| **Transfer Agen & Bank** | Ya | Ya | Ya | Jasa kirim uang transfer antar bank |
| **Lihat Saldo Akhir & Laba** | Ya | Ya | **DILINDUNGI** | Kasir tidak dapat mengintip total kas toko & laba bersih |
| **Edit Stok Fisik Barang** | Ya | Ya | **DILINDUNGI** | Kasir dilarang mengubah angka stok langsung di katalog |
| **Top Up Saldo Multi** | Ya | Ya | **Opsional (Default Tidak)** | Menambah deposit server pulsa via kas/bank toko |
| **Pembelian & Hutang** | Ya | Ya | **DISEMBUNYIKAN** | Melindungi rahasia supplier & harga kulakan |
| **Piutang Pelanggan** | Ya | Ya | **Opsional** | Kasir fokus pada penjualan tunai |
| **Bagan Akun (COA) & Kas** | Ya | Ya | **DISEMBUNYIKAN** | Pengaturan rekening & struktur modal hanya untuk finance |
| **Kelola Pengguna** | Ya | **Tidak** | **Tidak** | Hanya Super Admin yang berhak mengatur akun & perizinan |

---

## 3. HALAMAN LOGIN & AUTENTIKASI

### Tampilan & Komponen:
- **Input Email Login**: Memasukkan alamat email terdaftar akun.
- **Input Password**: Memasukkan kata sandi rahasia.
- **Tombol "Masuk ke Sistem"**:
  - *Aksi*: Memvalidasi kredensial pengguna, status aktif akun, dan mengambil hak akses (permissions).
  - *Pengaruh ke Sistem*: Menghasilkan sesi login aman (Web Cookie / Mobile Sanctum Token). Menentukan cabang toko yang aktif untuk sesi tersebut.

---

## 4. DASHBOARD UTAMA (EXECUTIVE & FRONT-LINER VIEW)

Dashboard adalah pusat kendali analitik bisnis toko Anda.

### A. Filter Periode & Cabang Toko (Bagian Atas)
- **Tombol Pilihan Periode (Bulan Ini / Hari Ini / Kustom Tanggal)**:
  - *Fungsi*: Menentukan cakupan tanggal penghitungan seluruh data indikator.
  - *Pengaruh*: Memfilter ulang seluruh KPI, omzet, HPP, biaya, dan laba kotor.
- **Dropdown Pilih Cabang / Outlet** *(Khusus Akun Pusat / Super Admin)*:
  - *Fungsi*: Melihat performa per toko cabang atau agregat seluruh cabang.
  - *Pengaruh*: Memisahkan kas laci dan perputaran barang masing-masing cabang.

### B. 8 Kotak Kartu KPI Utama
1. **PERSEDIAAN BARANG (Rp)**:
   - *Arti*: Total valuasi rupiah aset barang fisik yang saat ini ada di gudang/toko (dihitung dari `Stok Fisik x Nilai HPP Modal`).
   - *Pengaruh*: Mengambil saldo Akun `1-2010 PERSEDIAAN BARANG`.
2. **HUTANG SUPPLIER (Rp)**:
   - *Arti*: Sisa kewajiban uang kulakan barang ke supplier yang belum dilunasi.
   - *Pengaruh*: Mengambil saldo Akun `2-1101 HUTANG PEMBELIAN`.
3. **PIUTANG PELANGGAN (Rp)**:
   - *Arti*: Tagihan uang toko yang masih ada di tangan pelanggan (penjualan tempo/bon).
   - *Pengaruh*: Mengambil saldo Akun `1-1210 PIUTANG PENJUALAN`.
4. **TOTAL KAS & BANK (Rp)** *(Hanya tampil jika berizin `view_final_balance`)*:
   - *Arti*: Akumulasi seluruh uang riil di laci kasir kas fisik, rekening BCA, BRI, dan deposit saldo multi.
   - *Pengaruh*: Mengakumulasi seluruh akun golongan `AKTIVA` kelompok kas/bank. Kasir FL tidak dapat melihat angka ini.
5. **TOTAL PENDAPATAN (Rp)**:
   - *Arti*: Omzet kotor dari Penjualan Retail + Grosir + Pulsa Elektrik + Jasa Transfer.
   - *Pengaruh*: Mengakumulasi seluruh akun golongan `PENDAPATAN (4-xxxx)`.
6. **BIAYA OPERASIONAL (Rp)**:
   - *Arti*: Pengeluaran uang kas untuk operasional (gaji karyawan, listrik, sewa, konsumsi, dll).
   - *Pengaruh*: Mengambil akumulasi transaksi Kas Keluar pada akun `BIAYA (6-xxxx)`.
7. **TOTAL TRANSAKSI**:
   - *Arti*: Jumlah lembar nota yang berhasil diterbitkan (pecahan Retail vs Grosir).
8. **LABA BERSIH REAL-TIME (Rp)** *(Hanya tampil jika berizin `view_final_balance`)*:
   - *Arti*: Keuntungan bersih toko sesungguhnya (`Total Pendapatan - Beban HPP - Biaya Operasional`).
   - *Pengaruh*: Dihitung otomatis per detik dari buku besar jurnal akuntansi.

### C. Rincian Saldo Kas & Bank (Cash Breakdown)
- Menampilkan rincian rupiah per kasir laci, rekening bank, dan saldo deposit server.
- Terlindungi oleh izin `view_final_balance`.

---

## 5. MODUL KASIR POS: PENJUALAN RETAIL (ECERAN)

Halaman utama yang digunakan kasir untuk transaksi eceran harga toko.

### A. Komponen Katalog & Pencarian Barang
- **Kolom Cari Barang / Scan Barcode**:
  - *Fungsi*: Mengetik nama barang, kode SKU, atau melakukan scan barcode fisik via scanner gun/kamera ponsel.
  - *Pengaruh*: Memfilter daftar barang seketika. Jika barcode cocok tepat, barang langsung otomatis masuk ke keranjang belanja.
- **Pill Kategori (Filter Jenis Barang)**:
  - *Fungsi*: Tombol cepat memfilter barang berdasarkan kategori (contoh: VOCER, KARTU PERDANA, AKSESORIS, ROKOK).
- **Kartu Produk / Item List**:
  - Menampilkan nama barang, stok tersisa, dan harga jual eceran.
  - *Aksi Klik*: Menambahkan 1 unit ke keranjang kasir. Jika stok 0, sistem otomatis memberikan peringatan dan melarang penambahan.

### B. Keranjang Belanja & Tombol Aksi Item
- **Tombol Tambah (+) / Kurang (-) Qty**:
  - *Fungsi*: Mengatur kuantitas item yang dibeli.
  - *Validasi*: Kuantitas tidak boleh melebihi stok fisik yang tersedia di database.
- **Tombol Hapus (Ikon Tempat Sampah)**:
  - *Fungsi*: Mengeluarkan barang dari keranjang belanja.
- **Pilihan Pelanggan (Customer Dropdown)**:
  - *Default*: **UMUM** (pelanggan tunai lepas).
  - *Pilihan Member*: Bisa memilih pelanggan tetap yang terdaftar di master data (penting jika pembayaran secara Tempo/Piutang).

### C. Pembayaran & Eksekusi Transaksi (Checkout)
- **Input Diskon Nota (Rp)**:
  - *Fungsi*: Memberikan potongan harga langsung pada total nota.
  - *Pengaruh*: Mengurangi total tagihan belanja dan otomatis dijurnal ke akun diskon penjualan.
- **Metode Pembayaran**:
  - **Tunai (Cash)**: Pembayaran menggunakan uang fisik laci kasir.
  - **Transfer**: Pembayaran masuk ke rekening Bank toko (BCA/BRI).
  - **Piutang / Tempo**: Pembayaran ditunda. Tagihan akan masuk ke buku Piutang Pelanggan (wajib memilih nama pelanggan selain UMUM).
- **Input Uang Diterima (Bayar)**:
  - *Fungsi*: Memasukkan jumlah uang kertas yang diserahkan pembeli.
  - *Tombol Cepat Uang Pas*: Mengisi otomatis nominal pas sesuai total nota.
- **Kembalian**:
  - Menghitung uang kembali secara otomatis (`Uang Bayar - Total Tagihan`).
- **Tombol "PROSES TRANSAKSI & CETAK STRUK"**:
  - *Pengaruh Sistematis*:
    1. Memotong stok fisik barang di tabel `products`.
    2. Menerbitkan nomor nota unik otomatis (format: `PR-YYYYMMDD-xxxx`).
    3. Menambah saldo kas kasir di Akun `1-1110 (CASH RETAIL)` atau Bank yang dipilih.
    4. Mengakui Pendapatan Penjualan Retail di Akun `4-1000`.
    5. Menghitung HPP modal dan memotong nilai Persediaan Barang di Akun `1-2010`.
    6. Membuka dialog pratinjau struk nota thermal dan langsung menghubungkan ke printer Bluetooth/USB.

---

## 6. MODUL KASIR POS: PENJUALAN GROSIR

Modul kasir khusus transaksi partai besar/reseller dengan sistem harga grosir.

### Perbedaan Utama dengan Kasir Retail:
- Menggunakan nomor nota berawalan **PG-** (Penjualan Grosir).
- Harga barang otomatis menerapkan tarif **Harga Grosir (Wholesale Price)** yang disetel pada master barang.
- Jurnal pendapatan otomatis dibukukan ke Akun `4-1100 (PENDAPATAN GROSIR)`.
- Pembeli umumnya adalah toko rekanan yang terdaftar di database pelanggan untuk memudahkan pelacakan histori transaksi grosir dan tempo.

---

## 7. MODUL KASIR: TARIK TUNAI POS (CASH WITHDRAWAL)

Fitur kasir untuk melayani nasabah yang ingin mengambil uang tunai di toko dengan cara mentransfer uang ke rekening toko via mobile banking/ATM.

### Cara Penggunaan & Fungsi Tombol:
1. Klik tombol **"Tarik Tunai"** (berwarna emas/amber) di bilah atas kasir POS.
2. Muncul jendela pop-up **Tarik Tunai Kasir POS**:
   - **Sumber Kas Pengambilan Uang Fisik**: Pilih akun kas laci kasir (misal: *CASH RETAIL* atau kas fisik toko).
   - **Nominal Uang Tunai Ditarik (Rp)**: Masukkan jumlah uang tunai yang diberikan ke nasabah (misal: `100.000`).
   - **Biaya Admin / Jasa Transfer (Rp)**: Masukkan tarif jasa toko (misal: `5.000`).
   - **Nama & No. HP Pelanggan**: Mengisi identitas penerima uang tunai untuk bukti kwitansi.
   - **Catatan**: Keterangan tambahan (misal: Transfer dari bank Mandiri a.n Budi).
3. Klik tombol **"Proses Penarikan Tunai"**.

### Pengaruh Sistematis & Akuntansi:
- Menerbitkan nomor bukti transaksi berawalan `TT-YYYYMMDD-xxxx`.
- **Uang Fisik Kas Laci Berkurang**: Sebesar nominal penarikan (`Rp 100.000`).
- **Saldo Bank Toko Bertambah**: Sebesar transfer yang masuk dari nasabah (`Rp 105.000` = Nominal + Admin).
- **Pendapatan Toko Bertambah**: Biaya admin (`Rp 5.000`) langsung diakui sebagai laba di Akun `4-1200 PENDAPATAN JASA`.

---

## 8. MODUL PRODUK ELEKTRIK & PULSA (MULTI SERVER PPOB)

Halaman penjualan pulsa handphone, paket data internet, token listrik PLN, voucher game, dan tagihan PPOB.

### A. Kartu Saldo Multi & Tombol "Top Up Saldo"
- Menampilkan sisa modal deposit server pulsa toko secara *real-time* (Akun `1-1131 SALDO MULTI`).
- **Tombol "Top Up Saldo"**:
  - *Fungsi*: Menambah modal deposit server saat saldo tipis.
  - *Alur Pengisian*:
    1. Klik **Top Up Saldo**.
    2. Pilih sumber rekening pembayaran (misal: Rekening BCA Toko).
    3. Masukkan nominal top up (misal: `1.000.000`).
    4. Klik **Konfirmasi Tambah Saldo**.
  - *Pengaruh Akuntansi*: Rekening BCA toko berkurang Rp 1.000.000, Saldo Multi server bertambah Rp 1.000.000. Tidak ada selisih uang gaib.

### B. Formulir Transaksi Pulsa / Elektrik
- **Input Nomor Tujuan / No. Meter PLN**: Memasukkan nomor handphone pelanggan atau ID pelanggan PLN.
- **Pilih Produk Elektrik**: Dropdown produk pulsa (misal: Telkomsel 10rb, Token PLN 50rb). Menampilkan harga jual dan harga modal HPP.
- **Pilih Akun Kasir Penerima (Kas Tunai / QRIS / Bank)**: Rekening kas toko yang menerima uang dari pembeli.
- **Tombol "Proses Transaksi Elektrik"**:
  - *Pengaruh Sistematis*:
    1. Menerbitkan nota transaksi berawalan `PE-YYYYMMDD-xxxx`.
    2. Saldo Kas Kasir bertambah sebesar Harga Jual ke pelanggan.
    3. Saldo Deposit Server (Akun `1-1131`) otomatis terpotong sebesar HPP Modal.
    4. Selisih keuntungan (Margin) otomatis tercatat di Laporan Laba Rugi pada Akun `4-1300 PENDAPATAN MULTI`.
    5. Menampilkan pratinjau struk pulsa yang siap dicetak ke printer Bluetooth thermal.

### C. Tombol "Batalkan / Reversal Transaksi"
- *Fungsi*: Digunakan jika server pulsa pusat menyatakan transaksi GAGAL / pulsa tidak masuk ke pelanggan.
- *Pengaruh*: Mengembalikan Saldo Multi server, mengembalikan uang pelanggan dari kasir, dan membatalkan jurnal laba rugi transaksi tersebut secara otomatis.

---

## 9. MODUL TRANSFER ANTAR AKUN & AGEN TRANSFER BANK

Modul untuk mencatat pemindahan uang antar kas toko atau layanan jasa agen transfer uang untuk pelanggan.

### A. Transfer Antar Akun (Internal)
- *Kegunaan*: Setor uang tunai dari kasir laci ke bank, atau tarik uang tunai dari bank ke kasir laci.
- *Pengaruh*: Mengurangi saldo akun asal dan menambah saldo akun tujuan dengan nominal yang persis sama tanpa pengaruh laba rugi.

### B. Jasa Agen Transfer Bank (Eksternal)
- *Kegunaan*: Pelanggan membawa uang tunai ke toko untuk ditransfer ke rekening bank orang lain.
- *Input*: Bank tujuan, nomor rekening, nama pemilik rekening, nominal transfer, dan biaya admin.
- *Pengaruh*:
  - Kas laci toko bertambah (menerima uang tunai + admin).
  - Saldo rekening bank toko berkurang (terpotong untuk kirim transfer ke penerima).
  - Biaya admin diakui sebagai Pendapatan Jasa Toko.

---

## 10. MODUL RETUR PENJUALAN (SALES RETURN)

Modul penanganan klaim barang rusak, salah beli, atau pengembalian produk oleh pembeli.

### Langkah Penggunaan:
1. Masukkan nomor nota invoice asli (contoh: `PR-20260917-1234`).
2. Pilih barang yang diretur dan masukkan jumlah barang (Qty Retur).
3. Tentukan opsi kompensasi retur:
   - **Kembalikan Uang Tunai (Refund Cash)**: Kas laci toko berkurang dikembalikan ke pembeli.
   - **Potong Piutang**: Mengurangi tagihan piutang pembeli jika transaksi aslinya tempo.
4. Klik **"Simpan Retur Penjualan"**.

### Dampak Sistematis:
- Stok fisik barang otomatis bertambah kembali ke master inventori.
- Nilai Pendapatan Penjualan dan HPP pada laporan laba rugi otomatis terkoreksi proporsional.

---

## 11. MODUL PEMBELIAN BARANG & HUTANG SUPPLIER

*(Hanya dapat diakses oleh user yang memiliki hak akses `purchase`)*

### A. Pencatatan Faktur Pembelian (Kulakan)
- **Input Tanggal Faktur & Nomor Faktur Supplier**.
- **Pilih Supplier**: Memilih distributor/agen tempat kulakan barang.
- **Pilih Metode Pembayaran**:
  - **Tunai / Transfer**: Pembayaran lunas seketika dari kas/bank toko.
  - **Kredit / Hutang Tempo**: Pembayaran bertahap sesuai jatuh tempo.
- **Tabel Barang Kulakan**: Menentukan barang yang dibeli, kuantitas masuk, dan harga beli satuan terbaru.
- **Tombol "Simpan Faktur Pembelian"**:
  - *Pengaruh*:
    1. Stok fisik barang di toko langsung bertambah sesuai qty yang dibeli.
    2. Nilai HPP barang terupdate secara moving average.
    3. Nilai Persediaan Barang di Neraca bertambah.
    4. Jika metode Kredit, otomatis muncul tagihan di **Buku Hutang Supplier**.

### B. Pembayaran Hutang Supplier
- Menampilkan seluruh faktur pembelian yang belum lunas.
- Kasir/Admin mengklik tombol **"Bayar Hutang"**, memilih rekening kas/bank sumber pembayaran dan memasukkan jumlah pembayaran.
- *Pengaruh*: Mengurangi saldo kas/bank toko dan mengurangi sisa hutang pada supplier tersebut.

---

## 12. MODUL PIUTANG PELANGGAN & PEMBAYARAN TEMPO

*(Dikelola oleh Admin / Kasir dengan izin terkait)*

### A. Buku Pembantu Piutang
- Menampilkan daftar seluruh pelanggan yang memiliki sisa tagihan belanja tempo yang belum lunas.
- Menampilkan rincian: No. Nota, Tanggal Transaksi, Total Tagihan, Sudah Dibayar, dan Sisa Piutang.

### B. Tombol "Pelunasan / Bayar Piutang"
1. Klik tombol **"Bayar"** pada baris nota pelanggan yang bersangkutan.
2. Pilih rekening penerimaan uang (misal: Kasir Laci atau Transfer BCA).
3. Masukkan jumlah uang cicilan/pelunasan.
4. Masukkan diskon/potongan pelunasan (jika ada kebijakan potongan).
5. Klik **"Simpan Pembayaran"**.
- *Pengaruh Sistematis*:
  - Saldo kas/bank toko bertambah sesuai uang yang diterima.
  - Sisa piutang pelanggan berkurang.
  - Jika sisa piutang menjadi Rp 0, status nota otomatis berubah menjadi **LUNAS**.

---

## 13. MODUL PENYESUAIAN STOK FISIK (STOCK OPNAME)

Modul resmi untuk menyeimbangkan stok fisik nyata di rak toko dengan stok yang tercatat di aplikasi komputer.

### Langkah Penggunaan:
1. Pilih barang yang dilakukan penghitungan fisik.
2. Sistem menampilkan **Stok Sistem** (contoh: 20 pcs).
3. Masukkan **Stok Fisik Nyata** hasil hitungan di rak (contoh: 18 pcs).
4. Masukkan alasan penyesuaian (misal: *Barang rusak di rak, tercecer, atau hilang*).
5. Klik **"Simpan Penyesuaian Stok"**.

### Dampak Akuntansi & Stok:
- Angka stok barang di sistem langsung diperbarui menjadi 18 pcs.
- Selisih 2 pcs (senilai harga modal HPP) otomatis dibukukan sebagai biaya kerugian stok pada Akun `Biaya Selisih Inventori / Kerusakan Barang`.
- Tidak ada manipulasi stok tersembunyi karena setiap penyesuaian tersimpan jejak auditnya (*Audit Trail*).

---

## 14. MODUL KAS MASUK & KAS KELUAR (BEBAN OPERASIONAL)

Modul pencatatan transaksi kas non-penjualan.

### A. Kas Keluar (Beban / Biaya Toko)
- *Kegunaan*: Mencatat pengeluaran uang laci kasir untuk keperluan operasional.
- *Pilihan Akun Beban*: Biaya Listrik & Air, Gaji Karyawan, Pembelian ATK/Plastik Struk, Biaya Kebersihan & Keamanan, Sewa Tempat, dll.
- *Pengaruh*: Kas laci toko berkurang dan biaya operasional bertambah di Laporan Laba Rugi.

### B. Kas Masuk (Penerimaan Kas Non-Nota)
- *Kegunaan*: Mencatat uang masuk selain dari penjualan barang (contoh: Setoran modal awal tambahan dari pemilik, pendapatan sewa etalase, pengembalian kelebihan bayar).
- *Pengaruh*: Kas toko bertambah dan akun modal/pendapatan lain bertambah.

---

## 15. MODUL BAGAN AKUN / CHART OF ACCOUNTS (COA) & MODAL AWAL

*(Modul Khusus Super Admin & Administrator)*

### A. Struktur Akun Akuntansi
Daftar seluruh rekening pembukuan toko yang terbagi dalam 5 kategori standar:
1. **AKTIVA (1-xxxx)**: Kas Laci, Rekening Bank, Saldo Multi, Piutang, Persediaan Barang.
2. **KEWAJIBAN (2-xxxx)**: Hutang Supplier, Titipan Dana Nasabah.
3. **MODAL (3-xxxx)**: Modal Disetor Pemilik, Laba Ditahan.
4. **PENDAPATAN (4-xxxx)**: Penjualan Retail, Penjualan Grosir, Pendapatan Multi, Pendapatan Jasa Transfer.
5. **HPP & BIAYA (5-xxxx & 6-xxxx)**: Beban Pokok Modal Barang, Biaya Operasional Toko.

### B. Pengaturan & Penyesuaian Modal Awal Kas
- **Tombol Edit (Ikon Pensil)** pada baris akun kas/bank:
  - Super Admin dapat mengubah nominal **Saldo Awal (Initial Balance)** kas toko.
  - *Pengaruh*: Selisih saldo awal langsung menyesuaikan *Current Balance* akun tersebut tanpa merusak histori transaksi jurnal yang sudah berjalan.

---

## 16. MODUL MASTER DATA (BARANG, PELANGGAN, SUPPLIER, CABANG)

### A. Master Barang (Produk Fisik)
- **Tombol "Tambah Barang Baru"**: Membuka form pendaftaran barang (Kode/Barcode, Nama Barang, Tipe/Kategori, Merek, Harga Modal HPP, Harga Retail, Harga Grosir, Stok Awal, Batas Minimum Stok).
- **Proteksi Edit Stok Kasir**: Kasir biasa yang tidak memiliki izin `edit_stock` tidak akan bisa mengedit angka stok di form ini.
- **Peringatan Minimum Stok**: Jika stok fisik barang berada di bawah batas minimum stok, sistem otomatis memberi warna peringatan agar toko segera melakukan kulakan.

### B. Master Pelanggan & Supplier
- Menampung identitas nama toko, nomor WhatsApp, alamat, dan batas limit plafon piutang/hutang.

### C. Master Cabang / Outlet
- Mengatur multi-toko (Pusat, Cabang 1, Cabang 2) untuk toko yang memiliki lebih dari satu lokasi fisik.

---

## 17. MODUL LAPORAN KEUANGAN & REKAPITULASI

### 1. Laporan Penjualan (Harian / Rekapitulasi Nota)
- Rekap seluruh struk belanja kasir lengkap dengan filter kasir pelaksana, tanggal, metode pembayaran, dan status lunas.
- Terdapat tombol ekspor ke file **Excel** dan cetak rekapitulasi.

### 2. Laporan Laba Rugi (Profit and Loss Statement)
- Menampilkan pendapatan kotor, total HPP modal barang terjual, laba kotor, seluruh biaya operasional, dan **Laba Bersih Akhir**.

### 3. Neraca Keuangan (Balance Sheet)
- Membuktikan kesehatan finansial toko: `TOTAL AKTIVA = TOTAL KEWAJIBAN + TOTAL MODAL`.

### 4. Buku Besar & Jurnal Umum
- Laporan jejak audit seluruh mutasi akuntansi debit dan kredit per akun perkiraan.

---

## 18. MODUL MANAJEMEN PENGGUNA & PENGATURAN HAK AKSES

*(Eksklusif Hak Akses Super Admin)*

### A. Pendaftaran User Baru
- Klik tombol **"Tambah User"**.
- Masukkan Nama, Email, Password, Peran (Super Admin / Administrator / Toko Kasir), dan Penugasan Cabang Toko.
- **Checklist Hak Akses (Granular Permissions)**:
  Super Admin dapat menyalakan/mematikan switch perizinan secara spesifik:
  - *Kasir POS (Retail & Grosir)*
  - *Produk Multi / Elektrik*
  - *Tarik Tunai Kasir*
  - *Transfer Agen & Bank*
  - *Master Data*
  - *Edit Angka Stok Fisik Barang*
  - *Top Up Saldo Multi Server*
  - *Menu Pembelian*
  - *Akuntansi & Bagan Akun*
  - *Kelola Modal Awal Kas*
  - *Lihat Saldo Akhir & Laba Bersih di Dashboard*
  - *Laporan Keuangan*
  - *Pengaturan Toko & Printer*

### B. Mengedit Izin User yang Sudah Ada
- Klik ikon **Edit** pada daftar pengguna.
- Ubah hak akses fitur sesuai kebutuhan (misal: memberikan kasir kepercayaan untuk melayani Tarik Tunai dan Top Up Saldo Multi, tetapi tetap mematikan akses lihat Saldo Kas Akhir & Laba Bersih).

---

## 19. MODUL PENGATURAN TOKO & PRINTER THERMAL BLUETOOTH/USB

### A. Pengaturan Profil Toko & Struk Nota
- Mengatur Nama Toko, Alamat, Nomor Telepon, Logo Toko, dan Pesan Kaki Nota (*Footer Receipt*).
- Pengaturan ini otomatis tercetak pada bagian atas dan bawah struk kasir retail, grosir, maupun pulsa elektrik.

### B. Pengaturan Koneksi Printer Thermal (Aplikasi Android / Desktop)
- **Pilih Ukuran Kertas**: 58mm (kertas kecil standar kasir) atau 80mm.
- **Pindai Perangkat Bluetooth (Scan Devices)**:
  - Mencari printer thermal Bluetooth terdekat (contoh: *RPP02N, Panda, Iware, VSC, MiniPOS*).
  - Klik **Sambungkan (Connect)**.
- **Tombol "Test Print Nota"**:
  - Mencetak contoh struk pengujian untuk memastikan ukuran teks, ketajaman tinta printer, dan perataan kertas sudah presisi sebelum toko mulai beroperasi.

---

### KESIMPULAN OPERASIONAL:
Sistem Elephant POS menjamin seluruh perputaran uang dan barang terkontrol secara matematis dan akuntansi. Front-liner fokus melayani transaksi penjualan, tarik tunai, dan pulsa dengan cepat, sementara Super Admin memiliki kendali penuh atas keamanan stok, hak akses, dan privasi finansial toko.
