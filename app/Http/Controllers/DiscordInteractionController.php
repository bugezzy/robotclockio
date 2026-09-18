<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Punch;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SodiumException;

class DiscordInteractionController extends Controller
{
    private const TYPE_PING = 1;

    private const TYPE_APPLICATION_COMMAND = 2;

    private const TYPE_PONG = 1;

    private const TYPE_CHANNEL_MESSAGE_WITH_SOURCE = 4;

    private const FLAG_EPHEMERAL = 64;

    /**
     * The only thing allowed to record a Discord-originated punch. Discord
     * calls this synchronously for every /clock interaction — there's no
     * separate long-running bot process to authenticate instead, so the
     * signed request itself is the only proof of authenticity (see
     * StripeWebhookController for the equivalent pattern).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $body = $request->getContent();

        if (! $this->hasValidSignature($request, $body)) {
            return response()->json(['error' => 'invalid request signature'], 401);
        }

        $payload = json_decode($body, true) ?? [];

        if (($payload['type'] ?? null) === self::TYPE_PING) {
            return response()->json(['type' => self::TYPE_PONG]);
        }

        if (($payload['type'] ?? null) === self::TYPE_APPLICATION_COMMAND && ($payload['data']['name'] ?? null) === 'clock') {
            return $this->clock($payload);
        }

        return $this->ephemeral('Unrecognized command.');
    }

    private function hasValidSignature(Request $request, string $body): bool
    {
        $signature = $request->header('X-Signature-Ed25519', '');
        $timestamp = $request->header('X-Signature-Timestamp', '');
        $publicKey = (string) config('services.discord.public_key', '');

        if ($signature === '' || $timestamp === '' || $publicKey === '') {
            return false;
        }

        try {
            return sodium_crypto_sign_verify_detached(hex2bin($signature), $timestamp.$body, hex2bin($publicKey));
        } catch (SodiumException) {
            return false;
        }
    }

    /**
     * Handles /clock in and /clock out. Each is a subcommand of the single
     * registered `clock` command (see DiscordRegisterCommands), so the
     * requested direction is the name of the chosen subcommand.
     *
     * @param  array<string, mixed>  $payload
     */
    private function clock(array $payload): JsonResponse
    {
        $direction = $payload['data']['options'][0]['name'] ?? null;

        if (! in_array($direction, ['in', 'out'], true)) {
            return $this->ephemeral('Use /clock in or /clock out.');
        }

        $guildId = $payload['guild_id'] ?? null;
        $discordUserId = $payload['member']['user']['id'] ?? null;

        if ($guildId === null || $discordUserId === null) {
            return $this->ephemeral('This command only works in a server, not a DM.');
        }

        $organization = Organization::where('discord_guild_id', $guildId)->first();

        if ($organization === null) {
            return $this->ephemeral("This Discord server isn't linked to a RobotClock organization yet.");
        }

        $employee = User::where('discord_user_id', $discordUserId)
            ->where('organization_id', $organization->id)
            ->first();

        if ($employee === null) {
            return $this->ephemeral("Your Discord account isn't linked — ask an admin to add your Discord user ID in RobotClock.");
        }

        $lastPunch = Punch::where('user_id', $employee->id)->orderByDesc('punched_at')->first();

        if ($lastPunch?->direction === $direction) {
            return $this->ephemeral($direction === 'in' ? "You're already clocked in." : "You're already clocked out.");
        }

        $punch = Punch::create([
            'card_uid' => 'discord:'.$discordUserId,
            'user_id' => $employee->id,
            'direction' => $direction,
            'punched_at' => now(),
            'organization_id' => $organization->id,
        ]);

        return $this->ephemeral(
            $direction === 'in'
                ? 'Clocked in at '.$punch->punched_at->format('g:i A').'.'
                : 'Clocked out at '.$punch->punched_at->format('g:i A').'.'
        );
    }

    private function ephemeral(string $content): JsonResponse
    {
        return response()->json([
            'type' => self::TYPE_CHANNEL_MESSAGE_WITH_SOURCE,
            'data' => [
                'content' => $content,
                'flags' => self::FLAG_EPHEMERAL,
            ],
        ]);
    }
}
