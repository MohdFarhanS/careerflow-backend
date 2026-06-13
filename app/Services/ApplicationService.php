<?php

namespace App\Services;

use App\Models\Application;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ApplicationService
{
    /**
     * Ambil semua lamaran milik user yang login dengan filter, search, dan sort.
     */
    public function getAll(array $filters): LengthAwarePaginator
    {
        return Application::query()
            ->where('user_id', Auth::id())
            ->search($filters['search'] ?? null)
            ->byStatus($filters['status'] ?? null)
            ->byLocation($filters['location'] ?? null)
            ->orderBy(
                'applied_date',
                ($filters['sort'] ?? 'newest') === 'oldest' ? 'asc' : 'desc'
            )
            ->paginate(10);
    }

    /**
     * Buat lamaran baru untuk user yang login.
     */
    public function create(array $data): Application
    {
        return Auth::user()->applications()->create($data);
    }

    /**
     * Update lamaran yang sudah ada.
     */
    public function update(Application $application, array $data): Application
    {
        $application->update($data);

        return $application->fresh();
    }

    /**
     * Hapus lamaran.
     */
    public function delete(Application $application): void
    {
        $application->delete();
    }
}