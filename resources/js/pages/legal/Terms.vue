<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { legalContactEmail, legalEffectiveDate } from '@/lib/legal';
import { privacy } from '@/routes';
</script>

<template>
    <Head title="Terms of Service" />

    <h1>Terms of Service</h1>
    <p class="text-muted-foreground">Effective {{ legalEffectiveDate }}</p>

    <h2>1. Agreement</h2>
    <p>
        These Terms of Service ("Terms") govern your access to and use of
        RobotClock, including the website at robotclock.io, the admin
        dashboard, the kiosk software, and the RobotClock Discord bot
        (together, the "Service"). By creating an account, using the Service,
        or clocking in or out through it, you agree to these Terms. If you use
        the Service on behalf of an organization, you confirm you are
        authorized to accept these Terms for that organization.
    </p>

    <h2>2. The Service</h2>
    <p>
        RobotClock is an RFID-based time clock for clubs and teams. People
        tap an RFID card at a kiosk (or use the /clock command in their
        organization's Discord server) to record when they clock in and out.
        Administrators manage users, teams, cards, and attendance from the
        dashboard.
    </p>

    <h2>3. Accounts and organizations</h2>
    <ul>
        <li>
            Organizations are created and licensed by RobotClock. Each
            organization's administrators decide who has an account and what
            role each person holds.
        </li>
        <li>
            You are responsible for keeping your login credentials, passkeys,
            and two-factor authentication devices secure, and for activity
            under your account.
        </li>
        <li>
            Provide accurate information, and tell us promptly if you believe
            your account has been compromised.
        </li>
    </ul>

    <h2>4. Licenses, purchases, and payment</h2>
    <p>
        Access for an organization requires a license. Hardware, software, and
        other items sold through the dashboard store are purchased by the
        organization. Payments are processed by Stripe; we do not receive or
        store full payment card numbers. Prices, taxes, and any refund terms
        are shown at checkout, and you agree to pay the amounts shown for the
        items you order.
    </p>

    <h2>5. Acceptable use</h2>
    <p>You agree not to:</p>
    <ul>
        <li>Use the Service unlawfully or to violate anyone's rights.</li>
        <li>
            Record time on behalf of another person, or use another person's
            card or account, without their authorization.
        </li>
        <li>
            Attempt to gain unauthorized access to the Service, other
            organizations' data, or our systems.
        </li>
        <li>
            Interfere with or overload the Service, or reverse engineer or
            resell it except as permitted by law or in writing.
        </li>
        <li>Send unsolicited or abusive content through the Service.</li>
    </ul>

    <h2>6. Your organization's responsibilities</h2>
    <p>
        Organizations control the people whose information is entered into
        RobotClock. Each organization is responsible for giving those people
        any required notices, obtaining any required consents (including from
        parents or guardians when they are minors), and using recorded time
        lawfully. RobotClock is a record-keeping tool. It does not provide
        payroll, legal, or labor-compliance advice, and organizations are
        responsible for verifying that recorded times are correct.
    </p>

    <h2>7. Discord integration</h2>
    <p>
        The /clock command works through Discord. Your use of Discord remains
        subject to Discord's own terms and policies. To use the command, an
        administrator must link your Discord account to your RobotClock
        profile and link your organization's Discord server to the
        organization.
    </p>

    <h2>8. Software and downloads</h2>
    <p>
        Kiosk software and other downloads we provide are licensed to you, not
        sold, for use by your licensed organization in accordance with these
        Terms. We retain all rights in the Service, including the RobotClock
        name and logo.
    </p>

    <h2>9. Your data</h2>
    <p>
        Your organization retains its rights in the data it puts into the
        Service. You grant us the right to host, process, and display that
        data as needed to operate and improve the Service. How we handle
        personal information is described in our
        <Link :href="privacy()">Privacy Policy</Link>.
    </p>

    <h2>10. Availability and changes to the Service</h2>
    <p>
        We work to keep the Service available, but we do not guarantee
        uninterrupted or error-free operation. Kiosks are designed to keep
        recording punches when offline and sync them later, but you are
        responsible for confirming that important records have synced. We may
        modify or discontinue features, and will try to give reasonable notice
        of significant changes.
    </p>

    <h2>11. Disclaimers</h2>
    <p>
        The Service is provided "as is" and "as available," without
        warranties of any kind, whether express or implied, including
        warranties of merchantability, fitness for a particular purpose, and
        non-infringement, to the fullest extent permitted by law.
    </p>

    <h2>12. Limitation of liability</h2>
    <p>
        To the fullest extent permitted by law, RobotClock will not be liable
        for indirect, incidental, special, consequential, or punitive damages,
        or for lost profits, lost data, or lost or inaccurate time records.
        Our total liability for any claim relating to the Service is limited to
        the amount your organization paid us in the twelve months before the
        claim arose.
    </p>

    <h2>13. Suspension and termination</h2>
    <p>
        You may stop using the Service at any time. We may suspend or
        terminate access if you or your organization violate these Terms, if a
        license lapses, or if required by law. Sections that by their nature
        should survive termination will survive.
    </p>

    <h2>14. Changes to these Terms</h2>
    <p>
        We may update these Terms from time to time. When we do, we will
        change the effective date above, and where changes are significant we
        will make reasonable efforts to notify organization administrators.
        Continued use of the Service after changes take effect means you accept
        the updated Terms.
    </p>

    <h2>15. Governing law</h2>
    <p>
        These Terms are governed by the laws of the jurisdiction in which
        RobotClock is established, without regard to its conflict-of-laws
        rules.
    </p>

    <h2>16. Contact</h2>
    <p>
        Questions about these Terms? Email
        <a :href="`mailto:${legalContactEmail}`">{{ legalContactEmail }}</a
        >.
    </p>
</template>
