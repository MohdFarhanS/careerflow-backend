# Architecture — CareerFlow Backend

Dokumentasi arsitektur teknis untuk backend CareerFlow. Dibaca bersama `CLAUDE.md` (konvensi kode) dan `README.md` (setup & API reference).

---

## Pola Utama: Controller → Service → Model

Setiap fitur mengikuti alur tiga lapis yang konsisten:

```
HTTP Request
    │
    ▼
FormRequest          ← validasi input + otorisasi
    │
    ▼
Controller           ← terima request, panggil service, kembalikan response
    │
    ▼
Service              ← semua logika bisnis di sini
    │
    ▼
Eloquent Model       ← akses database via ORM
    │
    ▼
JsonResource         ← transformasi output JSON
    │
    ▼
HTTP Response
```

**Aturan keras:**
- Controller tidak boleh berisi logika bisnis — hanya dispatch ke Service.
- Service tidak boleh berisi raw SQL — hanya via Eloquent Model dan scope-nya.
- Response tidak pernah return raw Model — selalu melalui `JsonResource`.
- Validasi tidak pernah di controller — selalu di kelas `FormRequest` tersendiri.

---

## Layer Detail

### Controller (`app/Http/Controllers/Api/`)

Tanggung jawab minimal: inject FormRequest, panggil Service, return Resource atau JsonResponse.

```php
// Pola standar untuk store
public function store(StoreXxxRequest $request): JsonResponse
{
    $result = $this->xxxService->create($request->validated());
    return response()->json(['message' => '...', 'data' => new XxxResource($result)], 201);
}
```

Otorisasi di controller hanya untuk kasus yang tidak bisa dilakukan di FormRequest (lihat bagian Otorisasi di bawah).

### Service (`app/Services/`)

Berisi semua logika bisnis. Menerima array data atau Model, mengembalikan Model atau koleksi.

```php
// Pola standar: getAll dengan filter, create, update, delete
public function getAll(array $filters): LengthAwarePaginator { ... }
public function create(array $data): Model { ... }
public function update(Model $model, array $data): Model { ... }
public function delete(Model $model): void { ... }
```

Service tidak tahu tentang HTTP request atau response. Dependency injection via constructor.

### FormRequest (`app/Http/Requests/`)

Dua tanggung jawab: validasi (`rules()`) dan otorisasi (`authorize()`).

- `authorize()` mengembalikan `bool` — `false` → Laravel throw 403
- `rules()` mendefinisikan aturan validasi
- `messages()` mendefinisikan pesan error dalam Bahasa Indonesia

### Resource (`app/Http/Resources/`)

Transformasi Model → array JSON. Selalu extend `JsonResource`.

- Gunakan `$this->whenLoaded('relation', ...)` untuk relasi opsional — mencegah N+1 dan key hanya muncul jika relasi sudah di-load.
- Selalu format tanggal secara eksplisit: `->toDateString()` untuk `date`, `->toISOString()` untuk `datetime`.
- Untuk collection relasi, gunakan `RelatedResource::collection($this->whenLoaded('items'))`.

### Model (`app/Models/`)

Model berisi: `$fillable`, `$casts`, relasi, dan query scopes.

- Scopes (`scopeXxx`) untuk filter yang reusable — dipanggil sebagai method chaining di Service.
- Cast `'date'` pada kolom tanggal mengembalikan Carbon object — gunakan `->toDateString()` saat membandingkan dengan string atau mengembalikan ke JSON.

---

## Otorisasi

Dua pola otorisasi digunakan secara konsisten:

### Pola 1: FormRequest `authorize()` (untuk create & update)

Digunakan ketika data yang dibutuhkan untuk otorisasi tersedia di route binding atau request body.

```php
// Contoh: update application — cek via route param
public function authorize(): bool
{
    return $this->route('application')->user_id === auth()->id();
}

// Contoh: store interview — cek application_id dari body
public function authorize(): bool
{
    $application = Application::find($this->application_id);
    return $application && $application->user_id === $this->user()->id;
}
```

### Pola 2: Cek manual di Controller (untuk show & destroy)

Digunakan untuk operasi yang tidak memiliki FormRequest (tidak ada body untuk divalidasi).

```php
// Cek langsung (applications)
if ($application->user_id !== auth()->id()) {
    return response()->json(['message' => 'Forbidden.'], 403);
}

// Traverse relasi (interviews — tidak ada user_id langsung)
if ($interview->application->user_id !== auth()->id()) {
    return response()->json(['message' => 'Tidak diizinkan.'], 403);
}
```

---

## Database & Relasi

### Hierarki kepemilikan

```
User
 └── Application (user_id FK, cascade delete)
      └── Interview (application_id FK, cascade delete)
```

Interview tidak punya `user_id` — kepemilikannya selalu ditelusuri via `interview → application → user_id`.

### Cascade Delete

Semua FK menggunakan `->onDelete('cascade')`:
- Hapus User → semua Application dan Interview-nya ikut terhapus
- Hapus Application → semua Interview-nya ikut terhapus

### Query Scopes (Application Model)

```php
scopeSearch($query, ?string $keyword)    // LIKE pada company_name + position
scopeByStatus($query, ?string $status)  // exact match, skip jika 'all'
scopeByLocation($query, ?string $location) // LIKE pada location
```

### Eager Loading

InterviewService selalu `with('application')` untuk menghindari N+1 — InterviewResource butuh `company_name` dan `position` dari Application.

DashboardService menggunakan raw query dengan `groupBy` untuk aggregasi status dalam satu query, bukan N query per status.

---

## Autentikasi (Sanctum SPA)

Session-based, bukan token. Penting untuk dipahami:

1. Frontend hit `GET /sanctum/csrf-cookie` → browser menyimpan XSRF-TOKEN cookie
2. Frontend kirim POST `/api/login` dengan X-XSRF-TOKEN header
3. Laravel mengembalikan session cookie
4. Semua request berikutnya authenticated via session cookie

Middleware `statefulApi()` di `bootstrap/app.php` mengaktifkan Sanctum untuk request SPA.

**Jangan** ganti ke token-based (`createToken()`) — ini akan mempengaruhi cara frontend mengirim semua request.

---

## Fitur yang Sudah Diimplementasi

| Fitur | Model | Migration | Service | Controller | Request | Resource |
|-------|-------|-----------|---------|------------|---------|----------|
| Auth | User | ✓ | ✓ | ✓ | ✓ | ✓ |
| Applications | Application | ✓ | ✓ | ✓ | ✓ | ✓ |
| Dashboard | — | — | ✓ | ✓ | — | — |
| Interviews | Interview | ✓ | ✓ | ✓ | ✓ | ✓ |
| Documents | — | ✓ | — | — | — | — |

---

## Konvensi Penamaan File

```
StoreXxxRequest.php        ← validasi + otorisasi untuk POST
UpdateXxxRequest.php       ← validasi + otorisasi untuk PUT/PATCH
XxxController.php          ← di app/Http/Controllers/Api/
XxxService.php             ← di app/Services/
XxxResource.php            ← di app/Http/Resources/
```

---

## Catatan Pengembangan Fitur Baru

Untuk menambah fitur baru (misal: `documents`), ikuti urutan ini:

1. **Migration** — buat tabel (jika belum ada)
2. **Model** — `$fillable`, `$casts`, relasi
3. **Service** — `getAll`, `create`, `update`, `delete`
4. **FormRequest** — `StoreXxxRequest` + `UpdateXxxRequest`
5. **Resource** — `XxxResource`
6. **Controller** — inject Service, gunakan FormRequest, return Resource
7. **Route** — daftarkan di `routes/api.php` dalam group `auth:sanctum`
