<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Punch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CardController extends Controller
{
    /**
     * List cards. System sees every organization's cards; owner sees their
     * own organization's; admin only sees their own team's.
     */
    public function index(Request $request): Response
    {
        $query = Card::with('employee.organization');

        if ($request->user()->isSystem()) {
            // no filter
        } elseif ($request->user()->isOwnerOrAbove()) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('organization_id', $request->user()->organization_id);
            });
        } else {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('team_id', $request->user()->team_id);
            });
        }

        return Inertia::render('admin/Cards', [
            'cards' => $query->orderByRaw('revoked_at is not null')
                ->orderBy('issued_at', 'desc')
                ->get()
                ->map(fn (Card $card) => [
                    'card_uid' => $card->card_uid,
                    'label' => $card->label,
                    'issued_at' => $card->issued_at->toIso8601String(),
                    'revoked_at' => $card->revoked_at?->toIso8601String(),
                    'employee_name' => $card->employee?->name,
                    'organization_name' => $card->employee?->organization?->name,
                ]),
            'users' => $this->scopedEmployees($request)->orderBy('full_name')->get(['id', 'full_name']),
            'unmatchedScans' => $this->recentUnmatchedScans($request),
        ]);
    }

    /**
     * Issue a card to an employee. Available to any admin-tier user — this
     * is admin's core job. Retires whatever card that person currently
     * holds first (one active card per person).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'card_uid' => ['required', 'string', 'regex:/^[0-9A-Fa-f]{4,32}$/'],
            'user_id' => ['required', 'uuid', Rule::exists(User::class, 'id')],
            'label' => ['nullable', 'string'],
        ]);

        $employee = User::findOrFail($data['user_id']);

        abort_unless($this->scopedEmployees($request)->whereKey($employee->id)->exists(), 403);

        $uid = Str::upper($data['card_uid']);

        Card::where('user_id', $employee->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);

        Card::updateOrCreate(
            ['card_uid' => $uid],
            ['user_id' => $employee->id, 'label' => $data['label'] ?? null, 'issued_at' => now(), 'revoked_at' => null]
        );

        return back();
    }

    /**
     * Revoke a card. Available to any admin-tier user.
     */
    public function destroy(Request $request, Card $card): RedirectResponse
    {
        abort_unless(
            $card->user_id !== null && $this->scopedEmployees($request)->whereKey($card->user_id)->exists(),
            403
        );

        $card->update(['revoked_at' => now()]);

        return back();
    }

    /**
     * Card UIDs scanned at a kiosk recently that don't belong to anyone yet
     * — a punch is recorded with a null user_id when the scanning card
     * isn't currently issued to anyone. Surfacing these lets an admin pick
     * the UID from a real scan instead of transcribing it off the card by
     * hand. Scoped by organization like everything else here; a card is
     * only truly "unmatched" if it isn't currently an active card for
     * someone else's now-revoked assignment.
     *
     * @return Collection<int, array{card_uid: string, last_seen_at: string}>
     */
    private function recentUnmatchedScans(Request $request): Collection
    {
        $issuedCardUids = Card::whereNull('revoked_at')->pluck('card_uid');

        $query = Punch::whereNull('user_id')->whereNotIn('card_uid', $issuedCardUids);

        if (! $request->user()->isSystem()) {
            $query->where('organization_id', $request->user()->organization_id);
        }

        return $query->select('card_uid')
            ->selectRaw('max(punched_at) as last_seen_at')
            ->groupBy('card_uid')
            ->orderByDesc('last_seen_at')
            ->limit(10)
            ->get()
            ->map(fn (Punch $scan) => [
                'card_uid' => $scan->card_uid,
                'last_seen_at' => $scan->last_seen_at,
            ]);
    }

    /**
     * Employees this user is allowed to issue/revoke cards for: system —
     * anyone; owner — their own organization; admin — their own team.
     */
    private function scopedEmployees(Request $request): Builder
    {
        if ($request->user()->isSystem()) {
            return User::query();
        }

        if ($request->user()->isOwnerOrAbove()) {
            return User::where('organization_id', $request->user()->organization_id);
        }

        return User::where('team_id', $request->user()->team_id);
    }
}
