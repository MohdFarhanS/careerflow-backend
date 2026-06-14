# CareerFlow Backend

REST API untuk aplikasi pelacak lamaran kerja CareerFlow. Backend ini dibangun dengan Laravel 12, Laravel Sanctum untuk autentikasi SPA berbasis session, dan struktur Controller -> Service -> Model.

## Tech Stack

- PHP 8.2+
- Laravel 12
- Laravel Sanctum 4 untuk SPA session authentication
- MySQL atau SQLite
- PHPUnit untuk test
- Laravel Pint untuk code style

## Prasyarat

- PHP >= 8.2
- Composer
- Node.js dan NPM
- MySQL atau SQLite

## Instalasi

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

Seeder bersifat opsional. Jalankan hanya jika butuh data awal.

## Environment Penting

```env
APP_URL=http://localhost:8000

SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost:3000
FRONTEND_URL=http://localhost:5173
FRONTEND_URL_LOCAL=http://localhost:3000

SESSION_DRIVER=database
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=false

MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@careerflow.test"
```

Catatan produksi:

- Isi `FRONTEND_URL` dengan domain frontend produksi.
- Kosongkan atau hapus `FRONTEND_URL_LOCAL` jika tidak ingin origin lokal diizinkan.
- Gunakan `SESSION_SECURE_COOKIE=true` ketika API berjalan di HTTPS.
- Pastikan `SANCTUM_STATEFUL_DOMAINS` berisi host frontend tanpa scheme, misalnya `app.example.com`.

## Menjalankan Server

```bash
php artisan serve
```

Default server: `http://localhost:8000`.

## CORS dan Sanctum

CORS membaca origin dari:

- `FRONTEND_URL`
- `FRONTEND_URL_LOCAL`

Sanctum menggunakan session cookie, bukan bearer token. Frontend SPA harus:

1. Request `GET /sanctum/csrf-cookie`.
2. Kirim request auth mutasi dengan cookie dan header CSRF, termasuk login, register, forgot password, dan reset password.
3. Kirim request API berikutnya dengan session cookie.

Pastikan HTTP client frontend memakai credentials, misalnya `withCredentials: true`.

Kontrak frontend auth:

- `/login` dan `/register` mengirim `POST /api/login` atau `POST /api/register` setelah CSRF cookie tersedia.
- `/forgot-password` mengirim `POST /api/forgot-password` dengan body `{ "email": "..." }`.
- Email reset mengarah ke `/reset-password?token={token}&email={email}` pada `FRONTEND_URL`.
- `/reset-password` mengirim `POST /api/reset-password` dengan `token`, `email`, `password`, dan `password_confirmation`.
- Response validasi `422` dipertahankan sebagai error field; status lain dapat dipetakan frontend menjadi pesan umum seperti sesi berakhir, forbidden, not found, rate limited, atau server error.

## Keamanan API

- Public auth routes memakai rate limit `throttle:5,1`.
- Protected routes memakai `auth:sanctum` dan `throttle:60,1`.
- Response JSON untuk rate limit mengembalikan pesan `Terlalu banyak percobaan...` dengan status `429`.
- Middleware `SecurityHeaders` menambahkan:
  - `X-Frame-Options: DENY`
  - `X-Content-Type-Options: nosniff`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Permissions-Policy: camera=(), microphone=(), geolocation=()`
- Password register dan reset minimal 8 karakter, memiliki huruf besar/kecil, dan angka.
- Forgot password selalu mengembalikan `200` agar status email terdaftar tidak terekspos.

## API Endpoints

Base URL: `http://localhost:8000/api`

### Public

| Method | Endpoint | Deskripsi |
| --- | --- | --- |
| `POST` | `/register` | Registrasi user baru dan login session |
| `POST` | `/login` | Login user |
| `POST` | `/forgot-password` | Kirim email reset password jika email terdaftar |
| `POST` | `/reset-password` | Reset password memakai token dari email |

### Protected

Semua endpoint berikut butuh autentikasi Sanctum.

| Method | Endpoint | Deskripsi |
| --- | --- | --- |
| `GET` | `/user` | Data user yang sedang login |
| `POST` | `/logout` | Logout dan invalidate session |
| `GET` | `/dashboard` | Statistik lamaran, chart bulanan, dan 5 lamaran terbaru |

### Applications

| Method | Endpoint | Deskripsi |
| --- | --- | --- |
| `GET` | `/applications/schema` | Metadata field form lamaran |
| `GET` | `/applications` | List lamaran dengan filter, search, dan pagination |
| `POST` | `/applications` | Tambah lamaran |
| `GET` | `/applications/{application}` | Detail lamaran termasuk interview |
| `PUT/PATCH` | `/applications/{application}` | Update lamaran |
| `DELETE` | `/applications/{application}` | Hapus lamaran |

Query `GET /applications`:

| Parameter | Tipe | Deskripsi |
| --- | --- | --- |
| `search` | string | Cari berdasarkan perusahaan atau posisi |
| `status` | string | Filter status, misalnya `Applied` |
| `location` | string | Filter lokasi secara partial match |
| `sort` | string | `newest` default atau `oldest` |

### Interviews

| Method | Endpoint | Deskripsi |
| --- | --- | --- |
| `GET` | `/interviews` | List interview milik user |
| `POST` | `/interviews` | Tambah interview |
| `PUT/PATCH` | `/interviews/{interview}` | Update interview |
| `DELETE` | `/interviews/{interview}` | Hapus interview |

Query `GET /interviews`:

| Parameter | Tipe | Deskripsi |
| --- | --- | --- |
| `upcoming` | string | `true` untuk interview yang belum lewat |
| `application_id` | integer | Filter interview per lamaran |
| `interview_type` | string | `Online` atau `Offline` |
| `sort` | string | `soonest` default, `oldest`, atau `latest` |

## Contoh Request

### POST `/register`

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "Password123",
  "password_confirmation": "Password123"
}
```

Response `201`:

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

### POST `/login`

```json
{
  "email": "john@example.com",
  "password": "Password123"
}
```

Response `200`:

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

### POST `/forgot-password`

```json
{
  "email": "john@example.com"
}
```

Response `200`:

```json
{
  "message": "Jika email tersebut terdaftar, link reset password telah dikirim."
}
```

Link reset diarahkan ke:

```text
{FRONTEND_URL}/reset-password?token={token}&email={email}
```

### POST `/reset-password`

```json
{
  "token": "reset-token-from-email",
  "email": "john@example.com",
  "password": "NewPassword123",
  "password_confirmation": "NewPassword123"
}
```

Response `200`:

```json
{
  "message": "Password berhasil direset."
}
```

Response `422` untuk token invalid atau kadaluarsa:

```json
{
  "message": "Token tidak valid atau sudah kadaluarsa."
}
```

### POST `/applications`

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

### POST `/interviews`

```json
{
  "application_id": 1,
  "interview_date": "2026-06-20",
  "interview_time": "10:00",
  "interview_type": "Online",
  "meeting_url": "https://meet.google.com/abc-xyz",
  "notes": "Bawa portfolio"
}
```

## Struktur Database

### `users`

| Kolom | Keterangan |
| --- | --- |
| `id` | Primary key |
| `name` | Nama user |
| `email` | Email unik |
| `email_verified_at` | Nullable |
| `password` | Hash password |
| `remember_token` | Token remember session |
| `created_at`, `updated_at` | Timestamp |

### `password_reset_tokens`

| Kolom | Keterangan |
| --- | --- |
| `email` | Primary key |
| `token` | Token reset password |
| `created_at` | Waktu token dibuat |

### `sessions`

Dipakai karena `SESSION_DRIVER=database`.

### `applications`

| Kolom | Keterangan |
| --- | --- |
| `id` | Primary key |
| `user_id` | FK ke `users`, cascade delete |
| `company_name` | Nama perusahaan |
| `position` | Posisi yang dilamar |
| `location` | Nullable |
| `job_url` | Nullable, panjang sampai 2048 |
| `applied_date` | Tanggal melamar |
| `salary_range` | Nullable |
| `status` | `Applied`, `Screening`, `Technical Test`, `Interview`, `Offered`, `Rejected` |
| `notes` | Nullable |
| `created_at`, `updated_at` | Timestamp |

### `interviews`

| Kolom | Keterangan |
| --- | --- |
| `id` | Primary key |
| `application_id` | FK ke `applications`, cascade delete |
| `interview_date` | Tanggal interview |
| `interview_time` | Jam interview |
| `interview_type` | `Online` atau `Offline` |
| `meeting_url` | Nullable |
| `notes` | Nullable |
| `created_at`, `updated_at` | Timestamp |

### `documents`

Migration sudah ada, tetapi fitur dokumen belum diimplementasikan di API.

## Struktur Folder

```text
app/
  Http/
    Controllers/Api/
    Middleware/
    Requests/
    Resources/
  Models/
  Providers/
  Services/
database/
  migrations/
  seeders/
routes/
  api.php
```

## Testing dan Formatting

```bash
php artisan test
vendor/bin/pint --test
```

Jika menjalankan test dari sandbox Windows dan folder Temp user tidak bisa ditulis, arahkan `TEMP` dan `TMP` ke folder dalam project terlebih dahulu.

## Development Scripts

```bash
composer dev
composer setup
composer test
```
