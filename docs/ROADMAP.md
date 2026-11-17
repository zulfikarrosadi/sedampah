# 🤖 Agent Implementation & Execution Plan

## 📌 Agent Architecture Overview

* **Agent Role:** Autonomous System Builder / Full-Stack Engineer Agent.
* **Target Stack:** Laravel v13, MySQL, TailwindCSS/Blade, JavaScript (QR Code Scanner & Interactivity), Midtrans Payment Gateway.
* **Execution Strategy:** Sequential Phase-based Pipeline with Automated Validation Gates.

---

## 🛠️ Phase 1: Database Architecture & Domain Model Setup

**Objective:** Mengenerasi skema basis data, migration files, seeder, dan Eloquent Model yang merepresentasikan domain bisnis reservasi.

### Task 1.1: Migration & Schema Design

* **Goal:** Buat file migrasi Laravel sesuai struktur entitas.
* **Entities & Fields Required:**
* `users`: `id`, `name`, `email`, `password`, `phone`, `role` (`admin`, `petugas`, `pengunjung`), `timestamps`.
* `camping_areas` (tapak): `id`, `name`, `capacity`, `price_per_night`, `status`, `description`, `layout_coordinates`, `timestamps`.
* `facilities` (add-on): `id`, `name`, `category` (tenda/alat/lampu/dll), `stock`, `rental_price`, `unit`, `timestamps`.
* `reservations`: `id`, `user_id`, `booking_code`, `camping_area_id`, `check_in_date`, `check_out_date`, `total_price`, `payment_status` (`pending`, `settled`, `expired`, `cancelled`), `snap_token`, `timestamps`.
* `reservation_items` (pivot add-on): `id`, `reservation_id`, `facility_id`, `qty`, `subtotal`, `timestamps`.
* `tickets`: `id`, `reservation_id`, `qr_code_hash`, `is_checked_in`, `checked_in_at`, `checked_in_by`, `timestamps`.



### Task 1.2: Eloquent Model & Relationships Definition

* **Rules for AI Agent:**
* `User` `hasMany` `Reservation`.
* `Reservation` `belongsTo` `CampingArea`, `belongsTo` `User`, `hasMany` `ReservationItem`, `hasOne` `Ticket`.
* `ReservationItem` `belongsTo` `Facility`.



### 🛡️ Validation Gate (Phase 1):

* Agen menjalankan `php artisan migrate:fresh --seed` tanpa error.
* Melakukan assertion relasi melalui `php artisan tinker`.

---

## ⚙️ Phase 2: Core Logic & Booking Engine Development

**Objective:** Membangun Service Layer untuk kalkulasi kuota real-time, penguncian transaksi, dan kalkulasi harga total.

### Task 2.1: Quota & Availability Service (`BookingService`)

* **Logic Requirements:**
* Fungsi `checkAvailability($checkInDate)`: Menghitung total reservasi aktif (`settled` & `pending` belum expired) pada tanggal tersebut.
* Jika (Jumlah Reservasi Aktif >= Kapasitas Maksimal Harian), return `false`.
* Mencegah *race condition* atau *overbooking* saat dua user checkout bersamaan (Gunakan Database Lock / Transaction Isolation Level).



### Task 2.2: Add-On & Cart Calculation Engine

* **Logic Requirements:**
* Kalkulasi Total = `Harga Area Tapak` + `Sum(Harga Alat x Qty)`.
* Verifikasi ketersediaan stok fisik alat pada `facilities.stock` sebelum reservasi dibuat.



### 🛡️ Validation Gate (Phase 2):

* Agen membuat Unit Test (`BookingServiceTest.php`) yang menguji:
1. Penolakan reservasi jika kuota tanggal penuh.
2. Akurasi kalkulasi harga total beserta add-on.



---

## 💳 Phase 3: Payment Gateway & Ticket Generator Automation

**Objective:** Mengintegrasikan Midtrans Snap API dan pembentukan e-tiket ber-QR Code otomatis.

### Task 3.1: Midtrans Service Integration (`PaymentService`)

* **Logic Requirements:**
* Endpoint Checkout: Membuat `snap_token` dari Midtrans Snap API menggunakan payload pesanan (`order_id`, `gross_amount`, `customer_details`).
* Endpoint Webhook Handler (`POST /api/midtrans/notification`):
* Menerima payload callback dari Midtrans.
* Verifikasi `signature_key` dari Midtrans.
* Jika `transaction_status` == `settlement`/`capture`, ubah `reservations.payment_status` $\rightarrow$ `settled`.
* Panggil `TicketService` untuk mendokumentasikan e-tiket secara otomatis.





### Task 3.2: QR Code & Ticket Generation (`TicketService`)

* **Logic Requirements:**
* Generate `qr_code_hash` unik (misal: `SHA256(booking_code + timestamp + secret)`).
* Buat record pada tabel `tickets`.
* Sediakan helper function untuk merender tiket ke format PDF / Blade View dengan komponen QR Code terintegrasi.



### 🛡️ Validation Gate (Phase 3):

* Agen mensimulasikan callback notification Midtrans via Feature Test (`MidtransWebhookTest.php`).
* Assertion: Reservasi berubah status menjadi `settled` dan record `tickets` otomatis tercipta.

---

## 📱 Phase 4: UI Build & Role-Based Workflows

**Objective:** Merender antarmuka pengguna sesuai peran (Admin, Petugas, Pengunjung).

### Task 4.1: Pengunjung Portal (Front-End)

* **Views:**
* **Landing Page & Availability Calendar:** Menampilkan kalender ketersediaan kuota per tanggal.
* **Area Selection & Add-on Selector:** UI interaktif pemilihan kavling dan jumlah item sewa.
* **Checkout & Midtrans Popup Integration:** Memunculkan Snap JS Popup untuk pembayaran.
* **My Tickets / Transaction History:** Menampilkan riwayat transaksi & e-tiket ber-QR Code.



### Task 4.2: Petugas Scanner Interface

* **Views & Functionality:**
* Halaman `/petugas/scan` terproteksi middleware `role:petugas`.
* Integrasi JavaScript Camera Scanner Library (seperti `html5-qrcode`).
* AJAX Request ke `POST /api/tickets/verify`:
* Jika QR Valid & `is_checked_in == false`: Ubah status `is_checked_in = true`, catat `checked_in_at`, tampilkan modal SUCCESS (Rincian nama & daftar alat sewa).
* Jika QR Tidak Valid atau Sudah Digunakan: Tampilkan modal ERROR.





### Task 4.3: Admin Control Panel

* **Views:**
* CRUD Manajemen Tapak & Stok Peralatan.
* Monitoring Transaksi Real-time & Export Laporan.



### 🛡️ Validation Gate (Phase 4):

* End-to-end browser testing (Dusk/Cypress) simulasi flow Pengunjung checkout $\rightarrow$ Petugas memindai QR Code.

---

## 🧪 Phase 5: Automated QA & Security Audit Execution

**Objective:** Pengujian otomatis kualitas kode, performa, dan kerentanan keamanan oleh agent.

### Task 5.1: Black-Box & Integration Test Execution

* Agen mengeksekusi suite pengujian Black-Box:
* **Test Case A:** Pengisian Form Login (Valid, Invalid, Empty Field).
* **Test Case B:** Pemilihan Tanggal & Pengujian Boundary Value Kuota (Tanggal Penuh).
* **Test Case C:** Verifikasi Scan QR Code (Token Palsu, Token Valid, Re-scan Token yang Sama).



### Task 5.2: Security & Performance Sanity Check

* Check SQL Injection vulnerabilities pada pencarian data.
* Memastikan Middleware Authorization pada route Petugas & Admin terkonfigurasi dengan tepat.
* Optimasi query N+1 pada pembacaan daftar transaksi dan item reservasi.

---

## 📊 Agent Task Matrix & Dependency Graph

```
[Phase 1: DB & Models]
          │
          ▼
[Phase 2: Booking Engine]
          │
          ├──────────────────────────┐
          ▼                          ▼
[Phase 3: Midtrans & QR]   [Phase 4.1: Visitor UI]
          │                          │
          ├──────────────────────────┘
          ▼
[Phase 4.2: Petugas Scanner UI]
          │
          ▼
[Phase 5: Full QA & Audit Gate] ➔ [Deployment Ready]

```

---

## 📝 Directives & System Rules for AI Execution

> **STRICT EXECUTION DIRECTIVES FOR THE AI AGENT:**
> 1. **No Hardcoded Credentials:** Seluruh key Midtrans (`MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`) wajib dibaca melalui environment file (`.env`).
> 2. **Transactional Integrity:** Semua proses pembuatan reservasi dan itemnya WAJIB menggunakan `DB::beginTransaction()` dan `DB::commit()`.
> 3. **QR Code Security:** Token QR Code tidak boleh berisi ID auto-increment telanjang (e.g., `id=12`), melainkan wajib menggunakan *encrypted string* atau *secure hash*.
> 4. **API Response Format:** Semua endpoint API untuk pemindaian QR Code wajib merespons dengan format JSON standar: `{ "success": boolean, "message": string, "data": object|null }`.
>
>
>
>