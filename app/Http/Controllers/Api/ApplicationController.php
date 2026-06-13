<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApplicationRequest;
use App\Http\Requests\UpdateApplicationRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Services\ApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApplicationController extends Controller
{
    public function __construct(private readonly ApplicationService $applicationService)
    {
    }

    /**
     * GET /api/applications/schema
     * Metadata field untuk form lamaran — mana yang wajib dan opsional
     */
    public function schema(): JsonResponse
    {
        return response()->json([
            'fields' => [
                [
                    'name'     => 'company_name',
                    'label'    => 'Nama Perusahaan',
                    'type'     => 'string',
                    'required' => true,
                    'max'      => 255,
                ],
                [
                    'name'     => 'position',
                    'label'    => 'Posisi',
                    'type'     => 'string',
                    'required' => true,
                    'max'      => 255,
                ],
                [
                    'name'     => 'applied_date',
                    'label'    => 'Tanggal Melamar',
                    'type'     => 'date',
                    'required' => true,
                ],
                [
                    'name'     => 'status',
                    'label'    => 'Status',
                    'type'     => 'enum',
                    'required' => true,
                    'options'  => ['Applied', 'Screening', 'Technical Test', 'Interview', 'Offered', 'Rejected'],
                ],
                [
                    'name'     => 'location',
                    'label'    => 'Lokasi',
                    'type'     => 'string',
                    'required' => false,
                    'max'      => 255,
                ],
                [
                    'name'     => 'job_url',
                    'label'    => 'URL Lowongan',
                    'type'     => 'url',
                    'required' => false,
                    'max'      => 2048,
                ],
                [
                    'name'     => 'salary_range',
                    'label'    => 'Kisaran Gaji',
                    'type'     => 'string',
                    'required' => false,
                    'max'      => 100,
                ],
                [
                    'name'     => 'notes',
                    'label'    => 'Catatan',
                    'type'     => 'text',
                    'required' => false,
                ],
            ],
        ]);
    }

    /**
     * GET /api/applications
     * List semua lamaran milik user + filter + search + pagination
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['search', 'status', 'location', 'sort']);

        $applications = $this->applicationService->getAll($filters);

        return ApplicationResource::collection($applications);
    }

    /**
     * POST /api/applications
     */
    public function store(StoreApplicationRequest $request): JsonResponse
    {
        $application = $this->applicationService->create($request->validated());

        return response()->json([
            'message'     => 'Lamaran berhasil ditambahkan.',
            'application' => new ApplicationResource($application),
        ], 201);
    }

    /**
     * GET /api/applications/{id}
     */
    public function show(Application $application): ApplicationResource|JsonResponse
    {
        // Pastikan hanya pemilik yang bisa lihat
        if ($application->user_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $application->load('interviews');

        return new ApplicationResource($application);
    }

    /**
     * PUT /api/applications/{id}
     */
    public function update(UpdateApplicationRequest $request, Application $application): JsonResponse
    {
        $updated = $this->applicationService->update($application, $request->validated());

        return response()->json([
            'message'     => 'Lamaran berhasil diperbarui.',
            'application' => new ApplicationResource($updated),
        ]);
    }

    /**
     * DELETE /api/applications/{id}
     */
    public function destroy(Application $application): JsonResponse
    {
        if ($application->user_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $this->applicationService->delete($application);

        return response()->json(['message' => 'Lamaran berhasil dihapus.']);
    }
}