# CareerFlow Backend

REST API untuk aplikasi pelacak lamaran kerja CareerFlow. Backend dibangun dengan Laravel 12 dan autentikasi Bearer token via Laravel Sanctum.

## Tech Stack

- PHP 8.2+
- Laravel 12
- Laravel Sanctum 4 — Bearer token authentication
- MySQL (production) / SQLite (development)
- PHPUnit untuk test
- Laravel Pint untuk code style

## Prasyarat

- PHP >= 8.2
- Composer
- MySQL atau SQLite

## Instalasi

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

## Environment Penting

```env
APP_URL=http://localhost:8000

FRONTEND_URL=http://localhost:5173
FRONTEND_URL_LOCAL=http://localhost:3000

SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost:3000

MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@careerflow.test"
```

Lihat `ENVIRONMENT_VARIABLES.md` di root project untuk daftar lengkap variabel production.

## Menjalankan Server

```bash
php artisan serve
```

Default server: `http://localhost:8000`.

## Autentikasi

CareerFlow menggunakan **Bearer token** (bukan cookie/session). Frontend dan backend berjalan di domain berbeda (Vercel/Railway), sehingga cookie lintas-domain tidak bisa digunakan.

Flow autentikasi:
1. `POST /api/register` atau `POST /api/login` → response berisi `{ user, token }`
2. Simpan token di sisi frontend (localStorage)
3. Setiap request berikutnya tambahkan header: `Authorization: Bearer {token}`
4. `POST /api/logout` → backend hapus token, frontend hapus dari storage

Tidak ada langkah CSRF cookie. Tidak diperlukan `withCredentials`.

## CORS

CORS membaca origin dari environment variable:

- `FRONTEND_URL` — domain frontend production
- `FRONTEND_URL_LOCAL` — domain frontend lokal (opsional)

## Keamanan API

- Public auth routes rate limit: `throttle:5,1`
- Protected routes: `auth:sanctum` + `throttle:60,1`
- Middleware `SecurityHeaders` menambahkan:
  - `X-Frame-Options: DENY`
  - `X-Content-Type-Options: nosniff`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Permissions-Policy: camera=(), microphone=(), geolocation=()`
- Password minimal 8 karakter, huruf besar/kecil, dan angka
- Forgot password selalu `200` (tidak mengekspos status email terdaftar)

## API Endpoints

Base URL: `http://localhost:8000/api`

### Public

| Method | Endpoint | Deskripsi |
|---|---|---|
| `POST` | `/register` | Registrasi user baru, mengembalikan Bearer token |
| `POST` | `/login` | Login user, mengembalikan Bearer token |
| `POST` | `/forgot-password` | Kirim email reset password |
| `POST` | `/reset-password` | Reset password dengan token dari email |

### Protected (butuh `Authorization: Bearer {token}`)

| Method | Endpoint | Deskripsi |
|---|---|---|
| `GET` | `/user` | Data user yang sedang login |
| `POST` | `/logout` | Logout dan hapus token aktif |
| `GET` | `/dashboard` | Statistik lamaran, chart bulanan, 5 lamaran terbaru |

### Applications

| Method | Endpoint | Deskripsi |
|---|---|---|
| `GET` | `/applications/schema` | Metadata field form lamaran |
| `GET` | `/applications` | List lamaran dengan filter, search, dan pagination |
| `POST` | `/applications` | Tambah lamaran |
| `GET` | `/applications/{id}` | Detail lamaran beserta daftar interview |
| `PUT` | `/applications/{id}` | Update lamaran |
| `PATCH` | `/applications/{id}/notes` | Update notes saja |
| `DELETE` | `/applications/{id}` | Hapus lamaran |

Query `GET /applications`:

| Parameter | Deskripsi |
|---|---|
| `search` | Cari berdasarkan perusahaan atau posisi |
| `status` | Filter status (`Applied`, `Screening`, dll.) |
| `location` | Filter lokasi (partial match) |
| `sort` | `newest` (default) atau `oldest` |
| `page` | Halaman (default 1, 10 item per halaman) |

### Interviews

| Method | Endpoint | Deskripsi |
|---|---|---|
| `GET` | `/interviews` | List interview milik user |
| `POST` | `/interviews` | Tambah interview |
| `PUT` | `/interviews/{id}` | Update interview |
| `DELETE` | `/interviews/{id}` | Hapus interview |

Query `GET /interviews`:

| Parameter | Deskripsi |
|---|---|
| `upcoming` | `true` untuk interview yang belum lewat |
| `application_id` | Filter interview per lamaran |
| `interview_type` | `Online` atau `Offline` |
| `sort` | `soonest` (default), `oldest`, atau `latest` |

### Documents

| Method | Endpoint | Deskripsi |
|---|---|---|
| `GET` | `/documents` | List dokumen milik user |
| `POST` | `/documents` | Upload CV (PDF) atau simpan link portfolio |
| `DELETE` | `/documents/{id}` | Hapus dokumen dan file fisiknya |

Field `POST /documents` (`multipart/form-data`):

| Field | Deskripsi |
|---|---|
| `document_type` | `cv` atau `portfolio` — wajib |
| `file` | File PDF, maks 5MB (wajib untuk CV) |
| `portfolio_url` | URL portfolio (wajib jika portfolio tanpa file) |

Setiap user hanya boleh memiliki satu dokumen per tipe. Upload baru otomatis replace yang lama.

## Contoh Response

### POST `/register` atau `/login` — `200`/`201`

```json
{
  "message": "Login berhasil.",
  "user": { "id": 1, "name": "John Doe", "email": "john@example.com" },
  "token": "1|abc123..."
}
```

### POST `/forgot-password` — `200`

```json
{ "message": "Jika email tersebut terdaftar, link reset password telah dikirim." }
```

### GET `/dashboard` — `200`

```json
{
  "total": 12,
  "stats": { "Applied": 3, "Screening": 2, "Interview": 2, "Offered": 1, "Rejected": 3 },
  "monthly_chart": [{ "month": "Jan", "count": 2 }],
  "recent_applications": [{ "id": 12, "company_name": "PT Contoh", "position": "Dev", "status": "Interview" }]
}
```

## Struktur Database

| Tabel | Keterangan |
|---|---|
| `users` | User dengan email unik |
| `personal_access_tokens` | Sanctum Bearer tokens |
| `applications` | Lamaran kerja, FK ke `users` |
| `interviews` | Interview, FK ke `applications` |
| `documents` | CV dan portfolio, FK ke `users` |
| `password_reset_tokens` | Token reset password |

## Testing dan Formatting

```bash
php artisan test
vendor/bin/pint --test
```

## Deployment (Railway)

Konfigurasi ada di `railway.json`. Setiap deploy otomatis menjalankan:
```bash
php artisan migrate --force
```

Set environment variables berikut di Railway dashboard (lihat `ENVIRONMENT_VARIABLES.md`):
`APP_KEY`, `APP_ENV`, `APP_DEBUG`, `APP_URL`, `DB_URL`, `FRONTEND_URL`, `MAIL_MAILER`, `RESEND_API_KEY`, `MAIL_FROM_ADDRESS`

## Development Scripts

```bash
composer dev    # Jalankan server + queue + log watcher sekaligus
composer test   # Test + code style check
```
