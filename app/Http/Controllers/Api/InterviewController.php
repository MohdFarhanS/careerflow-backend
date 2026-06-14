<?php
// app/Http/Controllers/Api/InterviewController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInterviewRequest;
use App\Http\Requests\UpdateInterviewRequest;
use App\Http\Resources\InterviewResource;
use App\Models\Interview;
use App\Services\InterviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InterviewController extends Controller
{
    public function __construct(private InterviewService $interviewService)
    {
    }

    /**
     * GET /api/interviews
     * List semua interview milik user dengan filter & pagination.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['upcoming', 'application_id', 'interview_type', 'sort']);
        $interviews = $this->interviewService->getAll($filters);

        return InterviewResource::collection($interviews);
    }

    /**
     * POST /api/interviews
     */
    public function store(StoreInterviewRequest $request): JsonResponse
    {
        $interview = $this->interviewService->create($request->validated());

        return response()->json([
            'message'   => 'Interview berhasil ditambahkan.',
            'interview' => new InterviewResource($interview),
        ], 201);
    }

    /**
     * PUT /api/interviews/{interview}
     * Route model binding otomatis inject Interview berdasarkan {interview} di URL.
     */
    public function update(UpdateInterviewRequest $request, Interview $interview): JsonResponse
    {
        $updated = $this->interviewService->update($interview, $request->validated());

        return response()->json([
            'message'   => 'Interview berhasil diperbarui.',
            'interview' => new InterviewResource($updated),
        ]);
    }

    /**
     * DELETE /api/interviews/{interview}
     * Otorisasi: cek manual karena tidak ada FormRequest untuk delete.
     * Pola ini konsisten dengan ApplicationController::destroy().
     */
    public function destroy(Interview $interview): JsonResponse
    {
        // Traverse relasi: interview → application → user_id
        if ($interview->application->user_id !== auth()->id()) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        $this->interviewService->delete($interview);

        return response()->json(['message' => 'Interview berhasil dihapus.']);
    }
}