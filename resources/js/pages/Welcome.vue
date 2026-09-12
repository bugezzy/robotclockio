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
    <Head title="RobotClock — Scheduling that runs itself">
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
