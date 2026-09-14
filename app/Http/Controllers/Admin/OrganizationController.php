<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\License;
use App\Models\LicenseIssuance;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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
            'canManageLicenses' => $request->user()->isOwnerOrAbove(),
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

    /**
     * View this organization's licence issuance history. Read-only —
     * opening this does not issue a licence. Same visibility as everything
     * else here: owner for their own organization, system for any.
     */
    public function licenseHistory(Request $request, Organization $organization): JsonResponse
    {
        $this->authorizeLicenseAccess($request, $organization);

        return response()->json([
            'history' => $this->recentLicenseHistory($organization),
        ]);
    }

    /**
     * Issue an RCLK1 kiosk licence for this organization's current kiosk
     * key, recording the issuance in its history. Same visibility as
     * everything else here: owner for their own organization, system for
     * any. The licence string itself is never persisted, only the fact
     * that it was issued.
     */
    public function issueLicense(Request $request, Organization $organization): JsonResponse
    {
        $this->authorizeLicenseAccess($request, $organization);

        LicenseIssuance::create([
            'organization_id' => $organization->id,
            'issued_by' => $request->user()->id,
            'kiosk_key' => $organization->kiosk_key,
            'issued_at' => now(),
        ]);

        return response()->json([
            'license' => License::encode($organization),
            'history' => $this->recentLicenseHistory($organization),
        ]);
    }

    /**
     * Revoke every RCLK1 licence currently held by this organization's
     * kiosks, by rotating its kiosk_key — a licence carries the key it was
     * issued against, so this invalidates every one of them at once
     * without needing to track which machines hold which licence. A new
     * licence must be issued and pasted into each kiosk afterward. Same
     * visibility as everything else here: owner for their own
     * organization, system for any.
     */
    public function revokeLicense(Request $request, Organization $organization): JsonResponse
    {
        $this->authorizeLicenseAccess($request, $organization);

        $organization->forceFill(['kiosk_key' => (string) Str::uuid()])->save();

        return response()->json([
            'history' => $this->recentLicenseHistory($organization),
        ]);
    }

    private function authorizeLicenseAccess(Request $request, Organization $organization): void
    {
        abort_unless(
            $request->user()->isSystem() || $request->user()->organization_id === $organization->id,
            403
        );
        abort_unless($request->user()->isOwnerOrAbove(), 403);
    }

    /**
     * @return Collection<int, array{issued_at: string, issued_by: string|null}>
     */
    private function recentLicenseHistory(Organization $organization): Collection
    {
        return $organization->licenseIssuances()
            ->with('issuedBy:id,full_name')
            ->latest('issued_at')
            ->limit(10)
            ->get()
            ->map(fn (LicenseIssuance $issuance) => [
                'issued_at' => $issuance->issued_at->toIso8601String(),
                'issued_by' => $issuance->issuedBy?->name,
            ]);
    }
}
