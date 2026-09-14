<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Organization;
use App\Models\Punch;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the admin dashboard. Same visibility as everywhere else in the
     * admin panel: system sees the whole platform, owner sees their own
     * organization, admin sees their own team.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('admin/Dashboard', [
            'stats' => $this->stats($user),
            'recentPunches' => $this->recentPunches($user)
                ->map(fn (Punch $punch) => [
                    'id' => $punch->id,
                    'direction' => $punch->direction,
                    'punched_at' => $punch->punched_at->toIso8601String(),
                    'employee_name' => $punch->employee?->full_name,
                    'organization_name' => $punch->organization?->name,
                ]),
        ]);
    }

    /**
     * @return array{primary: array{label: string, count: int, subLabel: string, subCount: int}|null, users: int, activeCards: int, punchesToday: int}
     */
    private function stats(User $user): array
    {
        if ($user->isSystem()) {
            return [
                'primary' => [
                    'label' => 'Organizations',
                    'count' => Organization::count(),
                    'subLabel' => 'active',
                    'subCount' => Organization::where('active', true)->count(),
                ],
                'users' => User::count(),
                'activeCards' => Card::whereNull('revoked_at')->count(),
                'punchesToday' => Punch::whereDate('punched_at', Carbon::today())->count(),
            ];
        }

        if ($user->isOwnerOrAbove()) {
            return [
                'primary' => [
                    'label' => 'Teams',
                    'count' => Team::where('organization_id', $user->organization_id)->count(),
                    'subLabel' => 'active',
                    'subCount' => Team::where('organization_id', $user->organization_id)->where('active', true)->count(),
                ],
                'users' => User::where('organization_id', $user->organization_id)->count(),
                'activeCards' => Card::whereNull('revoked_at')
                    ->whereHas('employee', fn ($query) => $query->where('organization_id', $user->organization_id))
                    ->count(),
                'punchesToday' => Punch::where('organization_id', $user->organization_id)
                    ->whereDate('punched_at', Carbon::today())
                    ->count(),
            ];
        }

        return [
            'primary' => null,
            'users' => User::where('team_id', $user->team_id)->count(),
            'activeCards' => Card::whereNull('revoked_at')
                ->whereHas('employee', fn ($query) => $query->where('team_id', $user->team_id))
                ->count(),
            'punchesToday' => Punch::whereHas('employee', fn ($query) => $query->where('team_id', $user->team_id))
                ->whereDate('punched_at', Carbon::today())
                ->count(),
        ];
    }

    /**
     * The 10 most recent punches this user is allowed to see.
     *
     * @return Collection<int, Punch>
     */
    private function recentPunches(User $user): Collection
    {
        $query = Punch::with(['employee', 'organization'])->latest('recorded_at')->limit(10);

        if ($user->isSystem()) {
            return $query->get();
        }

        if ($user->isOwnerOrAbove()) {
            return $query->where('organization_id', $user->organization_id)->get();
        }

        return $query->whereHas('employee', fn ($q) => $q->where('team_id', $user->team_id))->get();
    }
}
