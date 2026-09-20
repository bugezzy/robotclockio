<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Punch;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DiscordInteractionTest extends TestCase
{
    use DatabaseTransactions;

    private const DISCORD_USER_ID = '111111111111111111';

    private const DISCORD_GUILD_ID = '222222222222222222';

    private string $publicKey;

    private string $secretKey;

    protected function setUp(): void
    {
        parent::setUp();

        $keyPair = sodium_crypto_sign_keypair();
        $this->publicKey = sodium_crypto_sign_publickey($keyPair);
        $this->secretKey = sodium_crypto_sign_secretkey($keyPair);

        config(['services.discord.public_key' => bin2hex($this->publicKey)]);
    }

    public function test_responds_to_ping(): void
    {
        $response = $this->postInteraction(['type' => 1]);

        $response->assertOk();
        $this->assertSame(1, $response->json('type'));
    }

    public function test_rejects_a_bad_signature(): void
    {
        $payload = json_encode(['type' => 1]);

        $response = $this->call('POST', route('discord.interactions'), [], [], [], [
            'HTTP_X_SIGNATURE_ED25519' => bin2hex(random_bytes(64)),
            'HTTP_X_SIGNATURE_TIMESTAMP' => (string) time(),
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(401);
    }

    public function test_clocks_in_a_linked_member_in_a_linked_guild(): void
    {
        $organization = Organization::factory()->create(['discord_guild_id' => self::DISCORD_GUILD_ID]);
        $employee = User::factory()->create([
            'organization_id' => $organization->id,
            'discord_user_id' => self::DISCORD_USER_ID,
        ]);

        $response = $this->postInteraction($this->clockPayload('in'));

        $response->assertOk();

        $punch = Punch::sole();
        $this->assertSame('in', $punch->direction);
        $this->assertSame($employee->id, $punch->user_id);
        $this->assertSame($organization->id, $punch->organization_id);
        $this->assertSame('discord:'.self::DISCORD_USER_ID, $punch->card_uid);
    }

    public function test_rejects_clocking_in_twice_in_a_row(): void
    {
        $organization = Organization::factory()->create(['discord_guild_id' => self::DISCORD_GUILD_ID]);
        $employee = User::factory()->create([
            'organization_id' => $organization->id,
            'discord_user_id' => self::DISCORD_USER_ID,
        ]);

        Punch::factory()->create([
            'user_id' => $employee->id,
            'organization_id' => $organization->id,
            'direction' => 'in',
            'punched_at' => now()->subHour(),
        ]);

        $this->postInteraction($this->clockPayload('in'))->assertOk();

        $this->assertSame(1, Punch::count());
    }

    public function test_clocking_out_after_clocking_in_succeeds(): void
    {
        $organization = Organization::factory()->create(['discord_guild_id' => self::DISCORD_GUILD_ID]);
        $employee = User::factory()->create([
            'organization_id' => $organization->id,
            'discord_user_id' => self::DISCORD_USER_ID,
        ]);

        Punch::factory()->create([
            'user_id' => $employee->id,
            'organization_id' => $organization->id,
            'direction' => 'in',
            'punched_at' => now()->subHour(),
        ]);

        $this->postInteraction($this->clockPayload('out'))->assertOk();

        $this->assertSame(2, Punch::count());
        $this->assertSame('out', Punch::orderByDesc('punched_at')->first()->direction);
    }

    public function test_rejects_when_the_guild_is_not_linked_to_an_organization(): void
    {
        $response = $this->postInteraction($this->clockPayload('in'));

        $response->assertOk();
        $this->assertSame(0, Punch::count());
    }

    public function test_rejects_when_the_discord_user_is_not_linked(): void
    {
        Organization::factory()->create(['discord_guild_id' => self::DISCORD_GUILD_ID]);

        $response = $this->postInteraction($this->clockPayload('in'));

        $response->assertOk();
        $this->assertSame(0, Punch::count());
    }

    /**
     * @return array<string, mixed>
     */
    private function clockPayload(string $direction): array
    {
        return [
            'type' => 2,
            'guild_id' => self::DISCORD_GUILD_ID,
            'member' => ['user' => ['id' => self::DISCORD_USER_ID]],
            'data' => [
                'name' => 'clock',
                'options' => [
                    ['name' => $direction, 'type' => 1],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postInteraction(array $payload): TestResponse
    {
        $body = json_encode($payload);
        $timestamp = (string) time();
        $signature = bin2hex(sodium_crypto_sign_detached($timestamp.$body, $this->secretKey));

        return $this->call('POST', route('discord.interactions'), [], [], [], [
            'HTTP_X_SIGNATURE_ED25519' => $signature,
            'HTTP_X_SIGNATURE_TIMESTAMP' => $timestamp,
            'CONTENT_TYPE' => 'application/json',
        ], $body);
    }
}
