<?php

namespace App;

use App\Models\Organization;
use RuntimeException;

/**
 * The RCLK1 kiosk licence: one pasteable string telling a kiosk which
 * organization it serves and how to reach it. Mirrors
 * com.moyers.robotclock.license.License on the desktop side — see that
 * class for the wire format and the reader's tolerances. This side only
 * ever encodes; a licence is never parsed here.
 *
 * Pending desktop-side support for the 'exp' field added below —
 * coordinate before relying on kiosks actually enforcing it.
 */
class License
{
    /**
     * The 'RCLK1.' prefix. Literal and case-sensitive.
     */
    private const PREFIX = 'RCLK1.';

    /**
     * How long an issued licence remains valid.
     */
    private const VALIDITY = '1 year';

    /**
     * Encode a licence for the given organization's current kiosk key.
     *
     * Rotating `organizations.kiosk_key` invalidates every licence already
     * issued for it, so call this again — and reissue to each machine —
     * whenever that key changes. A licence also expires one year after
     * being issued, per `exp` — a Unix timestamp, in seconds.
     */
    public static function encode(Organization $organization): string
    {
        $url = config('services.supabase.url');
        $key = config('services.supabase.publishable_key');

        if (blank($url) || blank($key)) {
            throw new RuntimeException(
                'services.supabase.url and services.supabase.publishable_key must both be configured to issue a licence.'
            );
        }

        $payload = [
            'org' => $organization->id,
            'org_name' => $organization->name,
            'url' => rtrim($url, '/'),
            'key' => $key,
            'kiosk' => $organization->kiosk_key,
            'exp' => now()->add(self::VALIDITY)->getTimestamp(),
        ];

        $encoded = rtrim(strtr(
            base64_encode(json_encode($payload, JSON_UNESCAPED_SLASHES)),
            '+/',
            '-_'
        ), '=');

        return self::PREFIX.$encoded;
    }
}
