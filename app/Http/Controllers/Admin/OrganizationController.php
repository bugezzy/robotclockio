<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    /**
     * List RobotClock customer organizations. System sees every one;
     * everyone else only sees their own.
     */
    public function index(Request $request): Response
    {
        $query = Organization::withCount(['teams', 'employees']);

        if (! $request->user()->isSystem()) {
            $query->where('id', $request->user()->organization_id);
        }

        return Inertia::render('admin/Organizations', [
            'organizations' => $query->orderBy('name')
                ->get()
                ->map(fn (Organization $organization) => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'description' => $organization->description,
                    'active' => $organization->active,
                    'teams_count' => $organization->teams_count,
                    'employees_count' => $organization->employees_count,
                    'created_at' => $organization->created_at->toIso8601String(),
                ]),
            'canCreate' => $request->user()->isSystem(),
        ]);
    }

    /**
     * Create a new customer organization. System only — this is the only
     * way a new tenant comes into being.
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSystem(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        Organization::create($data);

        return back();
    }

    /**
     * Update an organization's details. Owner may only update their own
     * organization; system may update any of them.
     */
    public function update(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless(
            $request->user()->isSystem() || $request->user()->organization_id === $organization->id,
            403
        );
        abort_unless($request->user()->isOwnerOrAbove(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        // An unchecked checkbox submits nothing at all, so "active" is read
        // separately rather than through the validated array.
        if ($request->user()->isSystem()) {
            $data['active'] = $request->boolean('active');
        }

        $organization->update($data);

        return back();
    }
}
