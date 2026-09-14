<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Check } from '@lucide/vue';
import { register } from '@/routes';
import { Button } from '@/components/ui/button';

const tiers = [
    {
        name: 'Starter',
        price: '$0',
        period: 'forever',
        description: 'For small clubs and teams getting started with RFID time tracking.',
        features: ['Up to 5 team members', 'RFID kiosk badge in/out', 'Email support'],
        cta: 'Start for free',
        featured: false,
    },
    {
        name: 'Team',
        price: '$29',
        period: 'per month',
        description: 'For growing clubs and teams that need more hardware and history.',
        features: [
            'Unlimited team members',
            'Per-member hour tracking',
            'RFID card & reader ordering',
            'Priority support',
        ],
        cta: 'Start free trial',
        featured: true,
    },
    {
        name: 'Organization',
        price: 'Contact us',
        period: '',
        description: 'For multi-location operations with custom needs.',
        features: [
            'Everything in Team',
            'Custom integrations',
            'Dedicated account manager',
            'SLA & onboarding support',
        ],
        cta: 'Contact sales',
        featured: false,
    },
];
</script>

<template>
    <section id="pricing" class="mx-auto max-w-6xl px-6 py-20">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-semibold tracking-tight md:text-4xl">
                Simple, transparent pricing
            </h2>
            <p class="mt-4 text-muted-foreground">
                Start free. Upgrade when your club or team needs more
                hardware and history.
            </p>
        </div>

        <div class="mt-14 grid gap-6 lg:grid-cols-3">
            <div
                v-for="tier in tiers"
                :key="tier.name"
                class="flex flex-col rounded-xl border p-8"
                :class="
                    tier.featured
                        ? 'border-primary bg-card shadow-sm'
                        : 'border-border bg-card'
                "
            >
                <h3 class="font-medium">{{ tier.name }}</h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ tier.description }}
                </p>
                <div class="mt-6 flex items-baseline gap-1">
                    <span class="text-3xl font-semibold">{{ tier.price }}</span>
                    <span v-if="tier.period" class="text-sm text-muted-foreground">
                        / {{ tier.period }}
                    </span>
                </div>

                <ul class="mt-6 flex-1 space-y-3 text-sm">
                    <li
                        v-for="item in tier.features"
                        :key="item"
                        class="flex items-start gap-2"
                    >
                        <Check class="mt-0.5 size-4 shrink-0 text-primary" />
                        <span>{{ item }}</span>
                    </li>
                </ul>

                <Button
                    class="mt-8"
                    :variant="tier.featured ? 'default' : 'outline'"
                    as-child
                >
                    <Link :href="register()">{{ tier.cta }}</Link>
                </Button>
            </div>
        </div>
    </section>
</template>
