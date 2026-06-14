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
| `GET` | `/dashboard` | Statistik lamaran, chart bulanan, 5 lamaran terbaru |

### Applications (butuh autentikasi)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/applications/schema` | Metadata field form lamaran |
| `GET` | `/applications` | List lamaran (filter + search + pagination) |
| `POST` | `/applications` | Tambah lamaran baru |
| `GET` | `/applications/{id}` | Detail satu lamaran |
| `PUT` | `/applications/{id}` | Update lamaran |
| `DELETE` | `/applications/{id}` | Hapus lamaran |

#### Query Parameters `GET /applications`

| Parameter | Tipe | Deskripsi |
|-----------|------|-----------|
| `search` | string | Cari berdasarkan nama perusahaan atau posisi |
| `status` | string | Filter by status (`Applied`, `Screening`, dll.) |
| `location` | string | Filter by lokasi (partial match) |
| `sort` | string | `newest` (default) atau `oldest` |

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

#### POST `/applications`

**Request Body:**
```json
{
    "company_name": "PT Contoh Jaya",
    "position": "Backend Developer",
    "applied_date": "2026-06-14",
    "status": "Applied",
    "location": "Jakarta",
    "job_url": "https://example.com/job/123",
    "salary_range": "8-12 juta",
    "notes": "Referral dari teman"
}
```

**Response `201`:**
```json
{
    "message": "Lamaran berhasil ditambahkan.",
    "application": {
        "id": 1,
        "company_name": "PT Contoh Jaya",
        "position": "Backend Developer",
        "location": "Jakarta",
        "job_url": "https://example.com/job/123",
        "applied_date": "2026-06-14",
        "salary_range": "8-12 juta",
        "status": "Applied",
        "notes": "Referral dari teman",
        "created_at": "2026-06-14T00:00:00.000000Z",
        "updated_at": "2026-06-14T00:00:00.000000Z"
    }
}
```

#### GET `/applications/{id}`

**Response `200`:**
```json
{
    "data": {
        "id": 1,
        "company_name": "PT Contoh Jaya",
        "position": "Backend Developer",
        "location": "Jakarta",
        "job_url": "https://example.com/job/123",
        "applied_date": "2026-06-14",
        "salary_range": "8-12 juta",
        "status": "Applied",
        "notes": "Referral dari teman",
        "interviews": [],
        "created_at": "2026-06-14T00:00:00.000000Z",
        "updated_at": "2026-06-14T00:00:00.000000Z"
    }
}
```

> Field `interviews` hanya muncul di endpoint detail ini, bukan di `GET /applications` (list).

#### GET `/applications`

**Response `200`:**
```json
{
    "data": [...],
    "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
    "meta": { "current_page": 1, "per_page": 10, "total": 25 }
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
| `job_url` | varchar(2048) | Nullable |
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
│   │   ├── AuthController.php
│   │   ├── ApplicationController.php
│   │   └── DashboardController.php
│   ├── Requests/
│   │   ├── LoginRequest.php
│   │   ├── RegisterRequest.php
│   │   ├── StoreApplicationRequest.php
│   │   └── UpdateApplicationRequest.php
│   └── Resources/
│       ├── UserResource.php
│       └── ApplicationResource.php
├── Models/
│   ├── User.php
│   ├── Application.php
│   └── Interview.php
├── Providers/
│   └── AppServiceProvider.php
└── Services/
    ├── AuthService.php
    ├── ApplicationService.php
    └── DashboardService.php
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
