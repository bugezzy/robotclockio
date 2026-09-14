<?php

namespace Tests\Unit;

use App\License;
use App\Models\Organization;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use Tests\TestCase;

class LicenseTest extends TestCase
{
    /**
     * The example from the RCLK1 licence format spec: a real licence,
     * parseable by the desktop app's own reader.
     *
     * The spec fixture predates the 'exp' field added below (pending
     * desktop-side support), so this asserts against the decoded payload
     * rather than a byte-identical string.
     */
    public function test_encode_matches_the_documented_wire_format(): void
    {
        Config::set('services.supabase.url', 'https://abcdefghijklm.supabase.co');
        Config::set('services.supabase.publishable_key', 'sb_publishable_EXAMPLE-KEY');

        $organization = $this->organization([
            'id' => '01a09719-66e4-71b4-9bc3-ad2fc8854377',
            'name' => 'Acme',
            'kiosk_key' => '3f2b9c14-7d51-4a88-9e0c-1b2a3c4d5e6f',
        ]);

        $payload = $this->decode(License::encode($organization));

        $this->assertSame([
            'org' => '01a09719-66e4-71b4-9bc3-ad2fc8854377',
            'org_name' => 'Acme',
            'url' => 'https://abcdefghijklm.supabase.co',
            'key' => 'sb_publishable_EXAMPLE-KEY',
            'kiosk' => '3f2b9c14-7d51-4a88-9e0c-1b2a3c4d5e6f',
        ], Arr::except($payload, 'exp'));
    }

    /**
     * The reader rejects standard base64: the payload must use the
     * URL-safe alphabet ('-'/'_', not '+'/'/') with no '=' padding.
     */
    public function test_encode_produces_unpadded_base64url(): void
    {
        Config::set('services.supabase.url', 'https://example.supabase.co');
        Config::set('services.supabase.publishable_key', 'sb_publishable_key');

        $organization = $this->organization();

        $encoded = License::encode($organization);

        $this->assertStringStartsWith('RCLK1.', $encoded);

        $payload = substr($encoded, strlen('RCLK1.'));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $payload);
        $this->assertStringNotContainsString('=', $payload);
    }

    /**
     * The spec requires "scheme and host only, no trailing slash and no
     * path" — a configured URL with a trailing slash must not leak one in.
     */
    public function test_encode_strips_a_trailing_slash_from_the_configured_url(): void
    {
        Config::set('services.supabase.url', 'https://example.supabase.co/');
        Config::set('services.supabase.publishable_key', 'sb_publishable_key');

        $payload = $this->decode(License::encode($this->organization()));

        $this->assertSame('https://example.supabase.co', $payload['url']);
    }

    /**
     * org_name absent or null is a tolerated reader case, not a writer one
     * — org.name is a non-nullable, unique column, so this only exercises
     * that the payload still carries whatever the column holds.
     */
    public function test_encode_carries_every_required_field(): void
    {
        Config::set('services.supabase.url', 'https://example.supabase.co');
        Config::set('services.supabase.publishable_key', 'sb_publishable_key');

        $organization = $this->organization([
            'id' => '01a09719-66e4-71b4-9bc3-ad2fc8854377',
            'name' => 'Acme',
            'kiosk_key' => '3f2b9c14-7d51-4a88-9e0c-1b2a3c4d5e6f',
        ]);

        $this->travelTo(now());

        $payload = $this->decode(License::encode($organization));

        $this->assertSame([
            'org' => '01a09719-66e4-71b4-9bc3-ad2fc8854377',
            'org_name' => 'Acme',
            'url' => 'https://example.supabase.co',
            'key' => 'sb_publishable_key',
            'kiosk' => '3f2b9c14-7d51-4a88-9e0c-1b2a3c4d5e6f',
            'exp' => now()->addYear()->getTimestamp(),
        ], $payload);
    }

    /**
     * A licence is valid for one year from the moment it's issued, after
     * which the desktop reader is expected to refuse it.
     */
    public function test_encode_expires_one_year_from_now(): void
    {
        Config::set('services.supabase.url', 'https://example.supabase.co');
        Config::set('services.supabase.publishable_key', 'sb_publishable_key');

        $this->travelTo(now());

        $payload = $this->decode(License::encode($this->organization()));

        $this->assertSame(now()->addYear()->getTimestamp(), $payload['exp']);
    }

    /**
     * A misconfigured deploy must fail loudly rather than issue a licence
     * with a blank url or key — both required, non-blank per the spec.
     */
    public function test_encode_refuses_to_issue_without_a_configured_url(): void
    {
        Config::set('services.supabase.url', null);
        Config::set('services.supabase.publishable_key', 'sb_publishable_key');

        $this->expectException(RuntimeException::class);

        License::encode($this->organization());
    }

    public function test_encode_refuses_to_issue_without_a_configured_key(): void
    {
        Config::set('services.supabase.url', 'https://example.supabase.co');
        Config::set('services.supabase.publishable_key', '');

        $this->expectException(RuntimeException::class);

        License::encode($this->organization());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function organization(array $attributes = []): Organization
    {
        $organization = new Organization;

        $organization->forceFill(array_merge([
            'id' => '01a09719-66e4-71b4-9bc3-ad2fc8854377',
            'name' => 'Acme',
            'kiosk_key' => '3f2b9c14-7d51-4a88-9e0c-1b2a3c4d5e6f',
        ], $attributes));

        return $organization;
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $licence): array
    {
        $payload = substr($licence, strlen('RCLK1.'));

        return json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
    }
}
