<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Organization;
use App\Models\Punch;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the admin dashboard.
     */
    public function __invoke(): Response
    {
        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'organizations' => Organization::count(),
                'activeOrganizations' => Organization::where('active', true)->count(),
                'users' => User::count(),
                'activeCards' => Card::whereNull('revoked_at')->count(),
                'punchesToday' => Punch::whereDate('punched_at', Carbon::today())->count(),
            ],
            'recentPunches' => Punch::with(['employee', 'organization'])
                ->latest('recorded_at')
                ->limit(10)
                ->get()
                ->map(fn (Punch $punch) => [
                    'id' => $punch->id,
                    'direction' => $punch->direction,
                    'punched_at' => $punch->punched_at->toIso8601String(),
                    'employee_name' => $punch->employee?->full_name,
                    'organization_name' => $punch->organization?->name,
                ]),
        ]);
    }
}
