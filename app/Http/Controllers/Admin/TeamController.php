<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    /**
     * List teams. System sees every organization's teams; owner sees their
     * own organization's; admin only sees their own team.
     */
    public function index(Request $request): Response
    {
        $query = Team::with('organization')->withCount('employees');

        if ($request->user()->isSystem()) {
            // no filter
        } elseif ($request->user()->isOwnerOrAbove()) {
            $query->where('organization_id', $request->user()->organization_id);
        } else {
            $query->where('id', $request->user()->team_id);
        }

        return Inertia::render('admin/Teams', [
            'teams' => $query->orderBy('name')
                ->get()
                ->map(fn (Team $team) => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'description' => $team->description,
                    'active' => $team->active,
                    'employees_count' => $team->employees_count,
                    'organization_id' => $team->organization_id,
                    'organization_name' => $team->organization->name,
                ]),
            'organizations' => $request->user()->isSystem()
                ? Organization::orderBy('name')->get(['id', 'name'])
                : Organization::where('id', $request->user()->organization_id)->get(['id', 'name']),
            'canManage' => $request->user()->isOwnerOrAbove(),
        ]);
    }

    /**
     * Create a team. Owner/system only.
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isOwnerOrAbove(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
        ]);

        if (! $request->user()->isSystem()) {
            $data['organization_id'] = $request->user()->organization_id;
        }

        Team::create($data);

        return back();
    }

    /**
     * Update a team. Owner may only update their own organization's teams;
     * system may update any of them.
     */
    public function update(Request $request, Team $team): RedirectResponse
    {
        abort_unless($request->user()->isOwnerOrAbove(), 403);
        abort_unless(
            $request->user()->isSystem() || $request->user()->organization_id === $team->organization_id,
            403
        );

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        // An unchecked checkbox submits nothing at all, so "active" is read
        // separately rather than through the validated array.
        $data['active'] = $request->boolean('active');

        $team->update($data);

        return back();
    }

    /**
     * Delete a team. Members are not deleted with it — their team_id is
     * set to null by the foreign key. Owner may only delete their own
     * organization's teams; system may delete any of them.
     */
    public function destroy(Request $request, Team $team): RedirectResponse
    {
        abort_unless($request->user()->isOwnerOrAbove(), 403);
        abort_unless(
            $request->user()->isSystem() || $request->user()->organization_id === $team->organization_id,
            403
        );

        $team->delete();

        return back();
    }
}
