<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->dashboardService->getSummary();

        return response()->json([
            'total'               => $data['total'],
            'stats'               => $data['stats'],
            'monthly_chart'       => $data['monthly_chart'],
            'recent_applications' => ApplicationResource::collection($data['recent_applications']),
        ]);
    }
}