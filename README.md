# CareerFlow Backend

REST API untuk aplikasi pelacak lamaran kerja CareerFlow, dibangun dengan Laravel 12 dan Laravel Sanctum untuk autentikasi berbasis sesi (SPA).

## Tech Stack

- **PHP** 8.2+
- **Laravel** 12
- **Laravel Sanctum** 4 — autentikasi SPA (session-based)
- **Database** — MySQL / SQLite (via konfigurasi `.env`)

## Prasyarat

- PHP >= 8.2
- Composer
- Node.js & NPM
- MySQL atau SQLite

## Instalasi

```bash
# Clone repo & masuk ke folder backend
cd backend

# Install dependensi PHP
composer install

# Salin file environment
cp .env.example .env

# Generate application key
php artisan key:generate

# Konfigurasi database di .env, lalu jalankan migrasi
php artisan migrate

# (Opsional) Seed data awal
php artisan db:seed
```

### Menjalankan Server

```bash
php artisan serve
```

Server berjalan di `http://localhost:8000`.

## Konfigurasi CORS

Frontend yang diizinkan (di `config/cors.php`):

| Origin | Keterangan |
|--------|-----------|
| `http://localhost:5173` | Vite dev server (default) |
| `http://localhost:3000` | Alternatif dev server |

Untuk produksi, ubah `allowed_origins` di `config/cors.php` sesuai domain frontend.

## Autentikasi

Menggunakan **Laravel Sanctum SPA Authentication** (session-based, bukan token). Frontend harus:

1. Ambil CSRF cookie dulu: `GET /sanctum/csrf-cookie`
2. Kirim request login/register dengan cookie tersebut
3. Semua request berikutnya otentik via session cookie

## API Endpoints

Base URL: `http://localhost:8000/api`

### Public

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `POST` | `/register` | Registrasi user baru |
| `POST` | `/login` | Login user |

### Protected (butuh autentikasi)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/user` | Mendapatkan data user yang login |
| `POST` | `/logout` | Logout user |

### Request & Response

#### POST `/register`

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Response `201`:**
```json
{
    "message": "Registrasi berhasil.",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "created_at": "2026-06-14T00:00:00.000000Z"
    }
}
```

#### POST `/login`

**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Response `200`:**
```json
{
    "message": "Login berhasil.",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "created_at": "2026-06-14T00:00:00.000000Z"
    }
}
```

#### GET `/user`

**Response `200`:**
```json
{
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "created_at": "2026-06-14T00:00:00.000000Z"
}
```

#### POST `/logout`

**Response `200`:**
```json
{
    "message": "Logout berhasil."
}
```

## Struktur Database

### `users`

| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| `id` | bigint | Primary key |
| `name` | varchar(255) | Nama user |
| `email` | varchar(255) | Email unik |
| `password` | varchar(255) | Bcrypt hash |
| `email_verified_at` | timestamp | Nullable |
| `remember_token` | varchar(100) | Nullable |
| `created_at` | timestamp | — |
| `updated_at` | timestamp | — |

### `applications`

| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| `id` | bigint | Primary key |
| `user_id` | bigint | FK ke `users` |
| `company_name` | varchar(255) | Nama perusahaan |
| `position` | varchar(255) | Posisi yang dilamar |
| `location` | varchar(255) | Nullable |
| `job_url` | varchar(255) | Nullable |
| `applied_date` | date | Tanggal melamar |
| `salary_range` | varchar(255) | Nullable |
| `status` | enum | `Applied`, `Screening`, `Technical Test`, `Interview`, `Offered`, `Rejected` |
| `notes` | text | Nullable |
| `created_at` | timestamp | — |
| `updated_at` | timestamp | — |

### `interviews`

| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| `id` | bigint | Primary key |
| `application_id` | bigint | FK ke `applications` |
| `interview_date` | date | Tanggal interview |
| `interview_time` | time | Jam interview |
| `interview_type` | enum | `Online`, `Offline` |
| `meeting_url` | varchar(255) | Nullable |
| `notes` | text | Nullable |
| `created_at` | timestamp | — |
| `updated_at` | timestamp | — |

### `documents`

| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| `id` | bigint | Primary key |
| `user_id` | bigint | FK ke `users` |
| `type` | enum | `CV`, `Portfolio` |
| `file_name` | varchar(255) | Nama file original |
| `file_path` | varchar(255) | Path penyimpanan |
| `created_at` | timestamp | — |
| `updated_at` | timestamp | — |

## Struktur Folder

```
app/
├── Http/
│   ├── Controllers/Api/
│   │   └── AuthController.php
│   ├── Requests/
│   │   ├── LoginRequest.php
│   │   └── RegisterRequest.php
│   └── Resources/
│       └── UserResource.php
├── Models/
│   └── User.php
├── Providers/
│   └── AppServiceProvider.php
└── Services/
    └── AuthService.php
database/
├── migrations/
└── seeders/
routes/
└── api.php
```

## Testing

```bash
composer test
```

## Development Scripts

```bash
# Jalankan semua service sekaligus (server, queue, log, vite)
composer dev

# Setup awal lengkap (install + migrate + build)
composer setup
```
