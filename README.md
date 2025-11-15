# Native PHP To-Do List

A simple yet polished to-do list application built with **plain PHP**, vanilla JS, and file-based JSON storage. All CRUD operations hit a single API endpoint, making the project easy to understand, extend, and deploy—perfect for demos or lightweight personal task tracking.

## ✨ Fitur Utama

- Tambah, edit, hapus, dan tandai tugas selesai.
- Filter berdasarkan status (aktif/selesai) dan prioritas.
- Pencarian instan dan tombol untuk membersihkan semua tugas selesai.
- Formulir pintar: mode tambah & edit dalam satu tempat.
- Penyimpanan aman di PostgreSQL lokal tanpa berkas JSON.
- UI responsif dengan statistik tugas real-time.

## 🧱 Arsitektur & Alur Data

| Lapisan | Detail |
| --- | --- |
| UI (`index.php`, `assets/style.css`, `assets/app.js`) | Menyediakan form input, daftar tugas interaktif, dan kontrol filter. JavaScript memanggil API menggunakan `fetch` dan merender data secara dinamis. |
| API (`api.php`) | Menerima request `GET` untuk mengambil daftar tugas dan `POST` dengan aksi `add`, `update`, `toggle`, `delete`, `clearCompleted`. Semua response dalam format JSON. |
| Storage (PostgreSQL) | Tugas disimpan di tabel `todos` dengan kolom `id, title, description, due_date, priority, is_done, created_at`. Koneksi dikelola melalui PDO (`pdo_pgsql`). |

## 🚀 Menjalankan Aplikasi

Pastikan sudah terpasang PHP 8.x (atau 7.4+). Jalankan server bawaan PHP dari root proyek:

```bash
php -S localhost:8000
```

Lalu buka `http://localhost:8000` di browser. Tugas baru otomatis tersimpan di database PostgreSQL lokal.

## 🧪 Validasi


## 🐘 Penyimpanan PostgreSQL

API sekarang terhubung langsung ke PostgreSQL lokal. Gunakan kredensial berikut dan pastikan extension `pdo_pgsql` sudah aktif di PHP:

| Kunci | Nilai |
| --- | --- |
| Host | `localhost` |
| Port | `5432` |
| Nama database | `todolist` |
| Username | `postgre` |
| Password | `root` |

Jika belum ada database atau user, jalankan perintah berikut di `psql` dengan user admin PostgreSQL:

```sql
CREATE DATABASE todolist;
CREATE USER postgre WITH PASSWORD 'root';
GRANT ALL PRIVILEGES ON DATABASE todolist TO postgre;
```

`api.php` akan membuat tabel `todos` secara otomatis saat pertama kali menerima request, tapi berikut ini skema yang dibuat:

```sql
CREATE TABLE todos (
	id TEXT PRIMARY KEY,
	title TEXT NOT NULL,
	description TEXT,
	due_date DATE,
	priority TEXT NOT NULL CHECK (priority IN ('low','medium','high')),
	is_done BOOLEAN NOT NULL DEFAULT FALSE,
	created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
```

Setelah database siap, jalankan server PHP seperti biasa dan buka `http://localhost:8000`.

Kedua berkas sudah diuji dengan linter bawaan PHP (`php -l`).

## 🧩 Struktur Direktori

```
.
├── api.php              # Endpoint CRUD berbasis file JSON
├── index.php            # Halaman utama + UI
├── assets/
│   ├── app.js           # Logika front-end (fetch API + rendering)
│   └── style.css        # Styling responsif
├── data/
│   └── todos.json       # Penyimpanan tugas
└── README.md            # Dokumentasi dan panduan
```

## 🛠️ Pengembangan Lanjut

- Migrasi penyimpanan ke database (MySQL/PostgreSQL) bila data makin besar.
- Tambahkan autentikasi sederhana untuk multi pengguna.
- Gunakan WebSocket atau SSE untuk kolaborasi real-time.
- Tambahkan pengujian otomatis (PHPUnit) untuk endpoint API.

Selamat membangun produktivitas Anda dengan PHP native! ☀️
