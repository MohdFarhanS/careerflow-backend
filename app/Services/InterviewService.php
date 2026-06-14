<?php

namespace App\Services;

use App\Models\Interview;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class InterviewService
{
    /**
     * Ambil semua interview milik user yang login.
     * Eager load 'application' sekaligus supaya resource bisa tampilkan
     * company_name & position tanpa N+1 query.
     *
     * Filter opsional:
     * - upcoming: hanya interview yang belum lewat
     * - application_id: filter per lamaran tertentu
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = Interview::with('application')
            ->whereHas('application', function ($q) {
                // Pastikan interview yang dikembalikan hanya milik user ini
                $q->where('user_id', Auth::id());
            });

        // Filter: hanya yang akan datang
        if (!empty($filters['upcoming']) && $filters['upcoming'] === 'true') {
            $query->where('interview_date', '>=', now()->toDateString());
        }

        // Filter: per aplikasi tertentu
        if (!empty($filters['application_id'])) {
            $query->where('application_id', $filters['application_id']);
        }

        // Filter: tipe interview
        if (!empty($filters['interview_type'])) {
            $query->where('interview_type', $filters['interview_type']);
        }

        // Default sort: paling dekat dulu (ascending interview_date)
        $sort = $filters['sort'] ?? 'soonest';
        if ($sort === 'oldest') {
            $query->orderBy('interview_date', 'asc')->orderBy('interview_time', 'asc');
        } elseif ($sort === 'latest') {
            $query->orderBy('interview_date', 'desc')->orderBy('interview_time', 'desc');
        } else {
            // 'soonest' — yang paling dekat dari sekarang
            $query->orderBy('interview_date', 'asc')->orderBy('interview_time', 'asc');
        }

        return $query->paginate(10);
    }

    /**
     * Buat interview baru.
     * application_id sudah divalidasi dan authorized di FormRequest.
     */
    public function create(array $data): Interview
    {
        $interview = Interview::create($data);

        // Load relasi supaya resource bisa mengakses company_name & position
        return $interview->load('application');
    }

    /**
     * Update interview yang sudah ada.
     * Otorisasi sudah dilakukan di UpdateInterviewRequest::authorize().
     */
    public function update(Interview $interview, array $data): Interview
    {
        $interview->update($data);

        return $interview->load('application');
    }

    /**
     * Hapus interview.
     */
    public function delete(Interview $interview): void
    {
        $interview->delete();
    }
}