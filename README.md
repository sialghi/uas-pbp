# 📚 EPUB-FST — FST ePub Reader (PHP + MySQL)

Aplikasi web untuk **upload**, **mengelola**, dan **membaca buku digital berformat `.epub`** langsung di browser. Dilengkapi fitur **bookmark**, **catatan (notes)**, serta **pencatatan waktu baca** (reading progress) untuk mendukung statistik di dashboard.

---

## ✨ Fitur

- **User Authentication**
  - Login & Register (session-based)
  - Password hashing dengan `password_hash()` + `password_verify()`
  - Multi-bahasa (ID/EN) via `?lang=id` / `?lang=en`

- **Manajemen Buku**
  - Upload file `.epub` + cover opsional
  - Kategori (Informatika, Biologi, Fisika, Matematika)
  - Admin bisa **edit** dan **hapus** buku

- **EPUB Reader di Browser**
  - Membaca EPUB via **epub.js** (render langsung di halaman)
  - Navigasi: tombol Next/Prev + keyboard ArrowLeft/ArrowRight
  - Dark/Light mode (mengikuti `localStorage.theme`)

- **Smart Tracking**
  - Waktu baca per buku (tabel `reading_progress`)
  - Total waktu baca user (tabel `user_stats`)
  - Status “Sedang Dibaca/Reading” di dashboard berdasarkan `books.last_read` (24 jam terakhir)

- **Bookmarks & Notes**
  - Simpan bookmark per user per buku
  - Simpan catatan/highlight per user per buku (berbasis CFI)

---

## 🧱 Tech Stack

- **Backend:** PHP 8.x (Native)
- **Database:** MySQL / MariaDB
- **Frontend:** HTML + Tailwind (CDN), Vanilla JavaScript
- **Reader:** epub.js + jszip (CDN)
- **Server Lokal:** XAMPP (Apache + MySQL)

---

## 📁 Struktur Folder

Folder penting yang dipakai aplikasi:

- `uploads/files/` → menyimpan file EPUB hasil upload
- `uploads/covers/` → menyimpan cover buku & foto profil (dipakai juga untuk profile photo)
- `config/koneksi.php` → konfigurasi koneksi database
- `config/language.php` → sistem multi-bahasa ID/EN

---

## 🚀 Instalasi (Windows + XAMPP) — Step-by-step

### 1) Taruh Project ke `htdocs`

Letakkan folder project ke:

- `C:\xampp\htdocs\epub-fst`

Jalankan XAMPP:
- Start **Apache**
- Start **MySQL**

Buka aplikasi:
- `http://localhost/epub-fst/`

---

### 2) Pastikan Folder Upload Ada

Pastikan folder berikut ada (buat manual jika belum ada):

- `uploads/files/`
- `uploads/covers/`

---

### 3) Buat Database

Aplikasi ini (default) terkoneksi ke database bernama **`epub_fst`** (lihat `config/koneksi.php`).

Di phpMyAdmin:
1. Buka `http://localhost/phpmyadmin`
2. Buat database baru: `epub_fst`

---

### 4) Import Schema SQL

Import file SQL berikut **ke database `epub_fst`**:

1. `db_epub_fst.sql`
2. `add_reading_progress_table.sql`

> Catatan: `db_epub_fst.sql` berisi pembuatan tabel utama (`users`, `books`, `notes`, `bookmarks`, `user_stats`).

---

### 5) Tambahkan Kolom `books.last_read` (WAJIB)

Kode di `dashboard.php`, `read.php`, dan `api.php` menggunakan `books.last_read` untuk:
- sorting buku yang terakhir dibaca
- badge “Sedang Dibaca/Reading”

Jika kolom ini belum ada, jalankan SQL berikut di phpMyAdmin (database: `epub_fst`):

```sql
ALTER TABLE books
  ADD COLUMN last_read TIMESTAMP NULL DEFAULT NULL;
```

(Opsional) Agar sorting lebih cepat:

```sql
CREATE INDEX idx_books_last_read ON books(last_read);
```

---

### 6) Konfigurasi Koneksi DB (Jika Berbeda)

Cek file `config/koneksi.php`:

- host: `127.0.0.1`
- user: `root`
- password: (biasanya kosong di XAMPP)
- database: `epub_fst`
- port: `3306`

Jika MySQL kamu memakai port lain (misalnya `3307`), ubah `$port`.

---

## 👤 Akun Admin

Fitur edit/hapus buku hanya muncul jika user role = **admin**.

Untuk menjadikan user sebagai admin:

```sql
UPDATE users SET role = 'admin' WHERE nim = 'NIM_KAMU';
```

---

## 🧭 Cara Pakai (Flow Cepat)

1. Buka `index.php` → Register user baru → Login
2. Masuk dashboard (`dashboard.php`) → Upload buku (`upload.php`)
3. Klik **Baca Sekarang** → masuk ke `read.php?id=...`
4. Reader memuat EPUB dari `uploads/files/<file_path>`
5. Timer berjalan otomatis dan tersimpan berkala ke database via `api.php`

---

## 🔌 API (Dipanggil oleh JavaScript Reader)

Endpoint: `api.php` (method `POST`, butuh session login)

- `action=get_time&book_id=ID`
  - Mengambil total waktu baca buku (kolom `reading_seconds` di `reading_progress`)

- `action=update_time&seconds=N&book_id=ID`
  - Menambah waktu baca per buku (`reading_progress`)
  - Menambah total waktu baca user (`user_stats.total_seconds`)
  - Update `books.last_read = NOW()` agar dashboard menandai buku recent

- `action=save_bookmark&book_id=ID&cfi=...&label=...`
  - Menyimpan bookmark ke tabel `bookmarks`

- `action=save_note&book_id=ID&cfi=...&text=...`
  - Menyimpan catatan/highlight ke tabel `notes`

---

## 📦 Streaming EPUB via PHP (Opsional)

File `stream.php` bisa digunakan sebagai endpoint streaming:
- cek login
- ambil file path dari tabel `books`
- kirim header `application/epub+zip`
- `readfile()` untuk mengirim isi file EPUB

Jika ingin memakai streaming, arahkan URL buku ke:
- `stream.php?id=ID_BUKU`

---

## 🧯 Troubleshooting

### 1) Upload berhasil tapi file tidak muncul
- Pastikan folder `uploads/files/` dan `uploads/covers/` ada.
- Pastikan Apache bisa menulis ke folder tersebut.

### 2) Dashboard error terkait `last_read`
- Pastikan kolom `books.last_read` sudah dibuat (lihat langkah #5).

### 3) Timer tidak tersimpan
- Pastikan tabel `reading_progress` ada (`add_reading_progress_table.sql`).
- Pastikan request ke `api.php` tidak diblokir (cek Network tab DevTools).

### 4) EPUB loading terus (tidak terbuka)
- Coba file EPUB lain (kemungkinan file korup).
- Pastikan file ada di `uploads/files/` dan `file_path` di DB benar.

---

## 📝 Catatan Pengembangan (Next Improvement)

- Disarankan migrasi query SQL ke **prepared statements** untuk keamanan.
- Validasi upload sebaiknya menambah pemeriksaan MIME type dan batas ukuran file.
- Tambahkan foreign key constraint untuk menjaga integritas relasi antar tabel.

---

## 🙌 Credits

- epub.js
- Tailwind CSS
- Lucide Icons
