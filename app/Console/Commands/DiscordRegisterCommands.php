<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('discord:register-commands')]
#[Description('Register the /clock slash command with Discord. Run once after setup, and again whenever its shape changes.')]
class DiscordRegisterCommands extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $applicationId = config('services.discord.application_id');
        $botToken = config('services.discord.bot_token');

        if (! $applicationId || ! $botToken) {
            $this->error('services.discord.application_id and services.discord.bot_token must be configured first.');

            return self::FAILURE;
        }

        // A global bulk overwrite: this PUT replaces every command
        // currently registered for the application with this list. Global
        // commands can take up to an hour to propagate to every server.
        $response = Http::withToken($botToken, 'Bot')
            ->put("https://discord.com/api/v10/applications/{$applicationId}/commands", [
                [
                    'name' => 'clock',
                    'description' => 'Clock in or out',
                    'type' => 1,
                    'options' => [
                        ['type' => 1, 'name' => 'in', 'description' => 'Clock in'],
                        ['type' => 1, 'name' => 'out', 'description' => 'Clock out'],
                    ],
                ],
            ]);

        if ($response->failed()) {
            $this->error("Discord rejected the command registration: {$response->status()} {$response->body()}");

            return self::FAILURE;
        }

        $this->info('Registered the /clock command with Discord.');

        return self::SUCCESS;
    }
}
