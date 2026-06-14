<?php

namespace App\Services;

use App\Models\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Ambil semua data statistik untuk dashboard user yang sedang login.
     */
    public function getSummary(): array
    {
        $userId = Auth::id();

        // Hitung total per status dalam satu query pakai groupBy
        $statusCounts = Application::query()
            ->where('user_id', $userId)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // Daftar semua status yang mungkin
        $allStatuses = [
            'Applied', 'Screening', 'Technical Test',
            'Interview', 'Offered', 'Rejected',
        ];

        // Pastikan semua status ada di array meski count-nya 0
        $stats = [];
        foreach ($allStatuses as $status) {
            $stats[$status] = $statusCounts[$status] ?? 0;
        }

        $total = array_sum($stats);

        // Data per bulan untuk bar chart (12 bulan terakhir)
        $monthExpr = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', applied_date)"
            : "DATE_FORMAT(applied_date, '%Y-%m')";

        $monthly = Application::query()
            ->where('user_id', $userId)
            ->where('applied_date', '>=', now()->subMonths(11)->startOfMonth())
            ->select(
                DB::raw("$monthExpr as month"),
                DB::raw('count(*) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month')
            ->map(fn($row) => $row->total)
            ->toArray();

        // Buat array 12 bulan terakhir, isi 0 jika tidak ada data
        $monthlyChart = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthKey = now()->subMonths($i)->format('Y-m');
            $monthLabel = now()->subMonths($i)->format('M Y'); // e.g. "Jun 2025"
            $monthlyChart[] = [
                'month' => $monthLabel,
                'total' => $monthly[$monthKey] ?? 0,
            ];
        }

        // 5 lamaran terbaru
        $recentApplications = Application::query()
            ->where('user_id', $userId)
            ->orderByDesc('applied_date')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return [
            'total'               => $total,
            'stats'               => $stats,
            'monthly_chart'       => $monthlyChart,
            'recent_applications' => $recentApplications,
        ];
    }
}