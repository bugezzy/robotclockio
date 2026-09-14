<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import MarketingCta from '@/components/marketing/MarketingCta.vue';
import MarketingFeatures from '@/components/marketing/MarketingFeatures.vue';
import MarketingFooter from '@/components/marketing/MarketingFooter.vue';
import MarketingHero from '@/components/marketing/MarketingHero.vue';
import MarketingNavbar from '@/components/marketing/MarketingNavbar.vue';
import MarketingPricing from '@/components/marketing/MarketingPricing.vue';

const page = usePage();
</script>

<template>
    <Head title="RobotClock — RFID time tracking for clubs and teams">
        <meta
            name="description"
            content="RobotClock is an RFID-based time clock for clubs and teams: each member's RFID tag tracks their individual hours at a kiosk, while admins manage teams, cards, and attendance from one dashboard."
        />
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen bg-background text-foreground">
        <MarketingNavbar :auth="page.props.auth" />
        <main>
            <MarketingHero />
            <MarketingFeatures />
            <MarketingPricing />
            <MarketingCta />
        </main>
        <MarketingFooter />
    </div>
</template>
