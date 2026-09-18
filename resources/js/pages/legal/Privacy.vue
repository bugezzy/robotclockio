<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { legalContactEmail, legalEffectiveDate } from '@/lib/legal';
import { terms } from '@/routes';
</script>

<template>
    <Head title="Privacy Policy" />

    <h1>Privacy Policy</h1>
    <p class="text-muted-foreground">Effective {{ legalEffectiveDate }}</p>

    <h2>1. Who we are and what this covers</h2>
    <p>
        RobotClock provides an RFID-based time clock for clubs and teams: a
        kiosk where people tap a card to clock in and out, a web dashboard
        for administrators, and a Discord bot that supports the /clock
        command. This policy explains what personal information we collect
        through those services, how we use it, and the choices you have. It
        should be read with our <Link :href="terms()">Terms of Service</Link>.
    </p>
    <p>
        Most information in RobotClock is entered by the organization you
        belong to. The organization decides who has an account, so for time
        records we handle information on the organization's behalf, and
        the organization is your first point of contact for questions about
        it.
    </p>

    <h2>2. Information we collect</h2>
    <ul>
        <li>
            <strong>Account information:</strong> your name, email address,
            role, team, and whether your account is active. Passwords are
            stored only as a one-way hash. If you enable two-factor
            authentication or passkeys, we store the data needed to verify
            them.
        </li>
        <li>
            <strong>RFID card information:</strong> the unique ID of each card
            issued to you, an optional label, and when the card was issued or
            revoked.
        </li>
        <li>
            <strong>Time records:</strong> each clock-in and clock-out
            ("punch"), including its direction, timestamp, the organization it
            belongs to, and the card or Discord account used. If an
            unrecognized card is scanned, we record the card ID without a
            person attached so the punch is not lost.
        </li>
        <li>
            <strong>Discord information:</strong> if your administrator links
            your Discord account, we store your Discord user ID. Your
            organization's Discord server ID is stored with the organization.
        </li>
        <li>
            <strong>Purchase information:</strong> for store orders, the items,
            quantities, totals, and Stripe order and payment references.
            Payment card details are collected and processed by Stripe and are
            not stored by us.
        </li>
        <li>
            <strong>Technical information:</strong> a session cookie that keeps
            you signed in, along with the IP address and browser user agent
            recorded for that session, and a preference cookie for your
            light or dark appearance setting.
        </li>
    </ul>

    <h2>3. How we use information</h2>
    <ul>
        <li>To record and report attendance and hours for your organization.</li>
        <li>To authenticate users and keep accounts secure.</li>
        <li>To process orders, issue licenses, and provide software updates.</li>
        <li>To respond to support requests and to detect and prevent abuse.</li>
        <li>To maintain, troubleshoot, and improve the Service.</li>
    </ul>
    <p>We do not sell personal information.</p>

    <h2>4. The Discord bot</h2>
    <p>
        When someone uses /clock in Discord, Discord sends our server the
        command, the user's Discord user ID, and the ID of the server where it
        was used. We use those to match the person and organization and record
        the punch, and we reply privately to the person who ran the command. We
        do not read messages in your server, and the bot does not request
        access to message content.
    </p>

    <h2>5. Who we share information with</h2>
    <ul>
        <li>
            <strong>Your organization:</strong> administrators can see the
            accounts, cards, and time records for their own organization.
        </li>
        <li>
            <strong>Service providers</strong> that help us run the Service,
            including database and hosting providers, Stripe for payments, and
            Discord for the bot. They may process information only to provide
            their services to us.
        </li>
        <li>
            <strong>Legal and safety:</strong> when required by law, or to
            protect the rights, property, or safety of RobotClock, our users,
            or others.
        </li>
        <li>
            <strong>Business transfers:</strong> as part of a merger,
            acquisition, or sale of assets, with notice to affected
            organizations.
        </li>
    </ul>

    <h2>6. Cookies</h2>
    <p>
        We use only the cookies needed to operate the Service: session and
        security cookies that keep you signed in and protect forms, and a
        cookie that remembers your appearance preference. We do not use
        advertising cookies.
    </p>

    <h2>7. Retention</h2>
    <p>
        We keep information for as long as your organization's account is
        active so that attendance history remains available. When an
        organization closes its account or asks us to delete information, we
        delete or anonymize it within a reasonable period, except where we
        must keep it for legal, accounting, or security reasons. Cards are
        revoked rather than deleted, so the history of who held a card stays
        accurate.
    </p>

    <h2>8. Security</h2>
    <p>
        We use safeguards designed to protect information, including
        encrypted connections, hashed passwords, optional two-factor
        authentication and passkeys, and verification of requests from Discord
        and Stripe. No system is perfectly secure, so we cannot guarantee
        absolute security.
    </p>

    <h2>9. Your choices and rights</h2>
    <p>
        You can ask to access, correct, or delete your personal information.
        Because your organization controls your account and time records,
        please start with your organization's administrator. You can also
        contact us using the address below, and we will work with your
        organization to respond. Depending on where you live, you may have
        additional rights under local privacy laws, and we will honor them as
        they apply.
    </p>

    <h2>10. Children</h2>
    <p>
        Many clubs and teams include students. RobotClock is provided to
        organizations, not directly to children, and we do not knowingly
        collect personal information from children under 13 without the
        involvement of the organization. Organizations are responsible for
        obtaining any parental or school consent required to add minors to the
        Service. If you believe we hold a child's information without proper
        authorization, contact us and we will take appropriate steps.
    </p>

    <h2>11. Changes to this policy</h2>
    <p>
        We may update this policy from time to time. We will change the
        effective date above and, for significant changes, make reasonable
        efforts to notify organization administrators.
    </p>

    <h2>12. Contact</h2>
    <p>
        Privacy questions or requests? Email
        <a :href="`mailto:${legalContactEmail}`">{{ legalContactEmail }}</a
        >.
    </p>
</template>
