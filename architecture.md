# Architecture - CareerFlow Backend

Dokumentasi ini menjelaskan arsitektur teknis backend CareerFlow. Baca bersama `README.md` untuk setup, konfigurasi environment, dan referensi endpoint.

## Ringkasan

CareerFlow memakai Laravel 12 dengan API JSON, session authentication via Sanctum, dan pemisahan tanggung jawab yang sederhana:

```text
HTTP Request
  -> Middleware
  -> FormRequest
  -> Controller
  -> Service
  -> Eloquent Model
  -> JsonResource
  -> HTTP Response
```

Prinsip utama:

- Controller menangani request orchestration dan response.
- FormRequest menangani validasi dan otorisasi request.
- Service menangani logika bisnis.
- Model menangani relasi, casts, fillable, dan query scopes.
- Resource menangani bentuk JSON response.

## Routing

Route utama ada di `routes/api.php`.

Public auth routes:

```php
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});
```

Protected routes:

```php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('applications/schema', [ApplicationController::class, 'schema']);
    Route::apiResource('applications', ApplicationController::class);
    Route::apiResource('interviews', InterviewController::class)
        ->only(['index', 'store', 'update', 'destroy']);
});
```

## Middleware

Middleware dikonfigurasi di `bootstrap/app.php`.

- `statefulApi()` mengaktifkan flow Sanctum SPA.
- `SecurityHeaders` dipasang pada API middleware group.
- Exception renderer khusus `ThrottleRequestsException` mengubah response rate limit menjadi JSON `429`.

`SecurityHeaders` menambahkan header berikut pada response API:

- `X-Frame-Options: DENY`
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: camera=(), microphone=(), geolocation=()`

## Authentication

Auth menggunakan Sanctum SPA berbasis session, bukan token API.

Flow login:

```text
Frontend -> GET /sanctum/csrf-cookie
Frontend -> POST /api/login
Laravel  -> regenerate session
Frontend -> request API berikutnya dengan session cookie
```

Flow logout:

```text
POST /api/logout
  -> AuthService::logout()
  -> guard web logout
  -> invalidate session
  -> regenerate CSRF token
```

`AuthService` adalah tempat login, register, dan logout. Controller tetap tipis dan mengembalikan `UserResource`.

Public auth mutation dari frontend tetap mengambil CSRF cookie terlebih dahulu. Ini berlaku untuk register, login, forgot password, dan reset password karena request dikirim dari SPA dengan cookie Sanctum.

## Password Reset

Password reset memakai broker default Laravel:

- Config broker: `config/auth.php`
- Tabel token: `password_reset_tokens`
- Expire token: 60 menit
- Throttle token: 60 detik

Flow forgot password:

```text
Frontend -> GET /sanctum/csrf-cookie
POST /api/forgot-password
  -> ForgotPasswordRequest
  -> Password::sendResetLink()
  -> return 200 tanpa expose status email
```

Flow reset password:

```text
Frontend -> GET /sanctum/csrf-cookie
POST /api/reset-password
  -> ResetPasswordRequest
  -> Password::reset()
  -> update password
  -> rotate remember_token
  -> dispatch PasswordReset event
```

URL reset dibuat di `AppServiceProvider`:

```text
{FRONTEND_URL}/reset-password?token={token}&email={email}
```

`User` memakai cast `password => hashed`, jadi password baru cukup diisi plain value pada model. Eloquent akan melakukan hashing.

## Password Policy

Register dan reset password memakai aturan:

- Minimal 8 karakter.
- Mengandung huruf besar dan kecil.
- Mengandung angka.
- Harus cocok dengan `password_confirmation`.

Aturan register ada di `RegisterRequest`. Aturan reset ada di `ResetPasswordRequest`.

## CORS dan Frontend URL

`config/cors.php` membaca origin dari environment:

```php
env('FRONTEND_URL', 'http://localhost:5173')
env('FRONTEND_URL_LOCAL', 'http://localhost:3000')
```

`config/app.php` menyimpan `frontend_url` untuk pembuatan link reset password:

```php
'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173')
```

Gunakan `FRONTEND_URL` sebagai sumber utama di production. `FRONTEND_URL_LOCAL` hanya untuk development.

## Controller Layer

Controller berada di `app/Http/Controllers/Api`.

Tanggung jawab:

- Menerima FormRequest atau Request.
- Memanggil service.
- Mengembalikan `JsonResponse` atau `JsonResource`.
- Melakukan otorisasi manual hanya untuk operasi tanpa FormRequest, seperti show/destroy tertentu.

Contoh pola:

```php
public function store(StoreApplicationRequest $request): JsonResponse
{
    $application = $this->applicationService->create($request->validated());

    return response()->json([
        'message' => 'Lamaran berhasil ditambahkan.',
        'application' => new ApplicationResource($application),
    ], 201);
}
```

## Service Layer

Service berada di `app/Services`.

Tanggung jawab:

- Logika bisnis.
- Query Eloquent yang dibutuhkan fitur.
- Create, update, delete, dan aggregasi data.

Service tidak membuat response HTTP dan tidak membaca request langsung.

Service saat ini:

- `AuthService`
- `ApplicationService`
- `InterviewService`
- `DashboardService`

## FormRequest Layer

Request berada di `app/Http/Requests`.

Tanggung jawab:

- `authorize()` untuk izin request.
- `rules()` untuk validasi.
- `messages()` untuk pesan validasi Bahasa Indonesia.

Request auth:

- `RegisterRequest`
- `LoginRequest`
- `ForgotPasswordRequest`
- `ResetPasswordRequest`

Request domain:

- `StoreApplicationRequest`
- `UpdateApplicationRequest`
- `StoreInterviewRequest`
- `UpdateInterviewRequest`

## Resource Layer

Resource berada di `app/Http/Resources`.

Gunakan Resource untuk semua output model:

- `UserResource`
- `ApplicationResource`
- `InterviewResource`

Aturan:

- Jangan return raw model dari controller.
- Gunakan `whenLoaded()` untuk relasi opsional.
- Format date/datetime secara eksplisit sebelum dikirim ke JSON.

## Model dan Relasi

Relasi utama:

```text
User
  -> hasMany Application
Application
  -> belongsTo User
  -> hasMany Interview
Interview
  -> belongsTo Application
```

Interview tidak menyimpan `user_id`. Kepemilikan interview selalu ditelusuri melalui `interview -> application -> user_id`.

## Otorisasi

Dua pola otorisasi:

1. FormRequest `authorize()` untuk create/update yang punya request body atau route model.
2. Cek manual di controller untuk show/destroy yang tidak memakai FormRequest.

Contoh cek manual application:

```php
if ($application->user_id !== auth()->id()) {
    return response()->json(['message' => 'Forbidden.'], 403);
}
```

Contoh cek manual interview:

```php
if ($interview->application->user_id !== auth()->id()) {
    return response()->json(['message' => 'Tidak diizinkan.'], 403);
}
```

## Database

Tabel utama:

- `users`
- `password_reset_tokens`
- `sessions`
- `applications`
- `interviews`
- `documents`

Cascade delete:

- Hapus `users` akan menghapus `applications` miliknya.
- Hapus `applications` akan menghapus `interviews` miliknya.

`documents` sudah memiliki migration, tetapi API dokumen belum diimplementasikan.

## Query dan Performance

Application filtering memakai query scopes pada `Application`:

- `scopeSearch`
- `scopeByStatus`
- `scopeByLocation`

Interview list memakai eager loading `application` karena `InterviewResource` membutuhkan data perusahaan dan posisi dari lamaran.

Dashboard memakai aggregasi query untuk status dan monthly chart agar tidak perlu query berulang per status.

## Fitur

| Fitur | Model | Migration | Service | Controller | Request | Resource |
| --- | --- | --- | --- | --- | --- | --- |
| Auth session | User | Ya | Ya | Ya | Ya | Ya |
| Password reset | User | Ya | Broker Laravel | Ya | Ya | Tidak |
| Applications | Application | Ya | Ya | Ya | Ya | Ya |
| Interviews | Interview | Ya | Ya | Ya | Ya | Ya |
| Dashboard | Application | Tidak khusus | Ya | Ya | Tidak | Menggunakan ApplicationResource |
| Documents | Belum | Ya | Belum | Belum | Belum | Belum |

## Konvensi Penambahan Fitur

Urutan yang disarankan:

1. Tambah atau sesuaikan migration.
2. Tambah Model dengan fillable, casts, relasi, dan scopes.
3. Tambah Service untuk logika bisnis.
4. Tambah FormRequest untuk validasi dan otorisasi.
5. Tambah Resource untuk response JSON.
6. Tambah Controller di `app/Http/Controllers/Api`.
7. Daftarkan route di `routes/api.php`.
8. Tambahkan test sesuai risiko perubahan.
9. Update `README.md` dan `architecture.md` jika perilaku API berubah.

## Verifikasi

Perintah lokal:

```bash
php artisan route:list --path=api
php artisan test
vendor/bin/pint --test
```

Catatan: jika terminal Windows tidak boleh menulis ke folder Temp user, set `TEMP` dan `TMP` ke folder writable di project sebelum menjalankan test.
