<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * List users. System sees every organization's; owner sees their own
     * organization's; admin only sees their own team's. Admin can view but
     * not manage — they're here to pick who to issue/revoke a card for.
     */
    public function index(Request $request): Response
    {
        $query = User::with(['organization', 'team'])->withCount('cards');

        if ($request->user()->isSystem()) {
            // no filter
        } elseif ($request->user()->isOwnerOrAbove()) {
            $query->where('organization_id', $request->user()->organization_id);
        } else {
            $query->where('team_id', $request->user()->team_id);
        }

        return Inertia::render('admin/Users', [
            'users' => $query->orderBy('full_name')
                ->get()
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'active' => $user->active,
                    'team_id' => $user->team_id,
                    'team_name' => $user->team?->name,
                    'organization_id' => $user->organization_id,
                    'organization_name' => $user->organization?->name,
                    'has_card' => $user->cards_count > 0,
                    'discord_user_id' => $user->discord_user_id,
                ]),
            'teams' => $request->user()->isSystem()
                ? Team::orderBy('name')->get(['id', 'name', 'organization_id'])
                : Team::where('organization_id', $request->user()->organization_id)->orderBy('name')->get(['id', 'name', 'organization_id']),
            'organizations' => $request->user()->isSystem()
                ? Organization::orderBy('name')->get(['id', 'name'])
                : Organization::where('id', $request->user()->organization_id)->get(['id', 'name']),
            'roles' => Role::when(
                ! $request->user()->isSystem(),
                fn ($query) => $query->where('name', '!=', 'system'),
            )->orderBy('rank')->get(['name', 'description']),
            'canManage' => $request->user()->isOwnerOrAbove(),
            'isSystem' => $request->user()->isSystem(),
        ]);
    }

    /**
     * Create a user. Owner/system only.
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isOwnerOrAbove(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'role' => ['required', Rule::exists(Role::class, 'name')],
            'team_id' => ['nullable', 'uuid', Rule::exists(Team::class, 'id')],
            'organization_id' => [Rule::requiredIf($request->input('role') !== 'system'), 'uuid', Rule::exists(Organization::class, 'id')],
        ]);

        // An unchecked checkbox submits nothing at all, so "active" is read
        // separately rather than through the validated array.
        $data['active'] = $request->boolean('active');

        if ($data['role'] === 'system' && ! $request->user()->isSystem()) {
            abort(403, 'Only a system user can appoint a system user.');
        }

        if (! $request->user()->isSystem()) {
            $data['organization_id'] = $request->user()->organization_id;
        }

        User::create($data);

        return back();
    }

    /**
     * Update a user. Owner may only update their own organization's users;
     * system may update any of them.
     */
    public function update(Request $request, User $targetUser): RedirectResponse
    {
        abort_unless($request->user()->isOwnerOrAbove(), 403);
        abort_unless(
            $request->user()->isSystem() || $request->user()->organization_id === $targetUser->organization_id,
            403
        );

        \Illuminate\Support\Facades\Log::info('TEMP-DEBUG users.update incoming', ['keys' => array_keys($request->all()), 'discord_user_id' => $request->input('discord_user_id'), 'has_discord_key' => $request->has('discord_user_id')]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($targetUser->id)],
            'role' => ['required', Rule::exists(Role::class, 'name')],
            'team_id' => ['nullable', 'uuid', Rule::exists(Team::class, 'id')],
            'discord_user_id' => ['nullable', 'string', 'max:32', Rule::unique(User::class, 'discord_user_id')->ignore($targetUser->id)],
        ]);

        // An unchecked checkbox submits nothing at all, so "active" is read
        // separately rather than through the validated array.
        $data['active'] = $request->boolean('active');

        if ($data['role'] === 'system' && ! $request->user()->isSystem()) {
            abort(403, 'Only a system user can appoint a system user.');
        }

        $targetUser->update($data);

        \Illuminate\Support\Facades\Log::info('TEMP-DEBUG users.update saved', ['validated_keys' => array_keys($data), 'stored_discord_user_id' => $targetUser->fresh()->discord_user_id]);

        return back();
    }

    /**
     * Set a user's password. A distinct action from update() — the
     * RobotClock backend's own admin_set_password function is likewise
     * separate from admin_save_user, which never touches passwords at all.
     * Owner may only set passwords for their own organization's users;
     * system may set any of them.
     */
    public function updatePassword(Request $request, User $targetUser): RedirectResponse
    {
        abort_unless($request->user()->isOwnerOrAbove(), 403);
        abort_unless(
            $request->user()->isSystem() || $request->user()->organization_id === $targetUser->organization_id,
            403
        );

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $targetUser->update($data);

        return back();
    }

    /**
     * Delete a user. Cards go with them (cascade); punches keep their
     * card_uid and lose only the link, so hours already worked stay in the
     * record. Owner may only delete their own organization's users; system
     * may delete any of them.
     */
    public function destroy(Request $request, User $targetUser): RedirectResponse
    {
        abort_unless($request->user()->isOwnerOrAbove(), 403);
        abort_unless(
            $request->user()->isSystem() || $request->user()->organization_id === $targetUser->organization_id,
            403
        );
        abort_if($targetUser->id === $request->user()->id, 403, 'You cannot delete your own account.');

        $targetUser->delete();

        return back();
    }
}
