<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Punch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PunchController extends Controller
{
    /**
     * List recent punches. System sees every organization's; everyone else
     * only sees their own organization's. Read-only — punches are recorded
     * by the kiosk, not edited here.
     */
    public function index(Request $request): Response
    {
        $query = Punch::with(['employee', 'organization']);

        if (! $request->user()->isSystem()) {
            $query->where('organization_id', $request->user()->organization_id);
        }

        return Inertia::render('admin/Punches', [
            'punches' => $query->latest('punched_at')
                ->limit(200)
                ->get()
                ->map(fn (Punch $punch) => [
                    'id' => $punch->id,
                    'card_uid' => $punch->card_uid,
                    'direction' => $punch->direction,
                    'punched_at' => $punch->punched_at->toIso8601String(),
                    'recorded_at' => $punch->recorded_at->toIso8601String(),
                    'employee_name' => $punch->employee?->name,
                    'organization_name' => $punch->organization?->name,
                ]),
        ]);
    }
}
