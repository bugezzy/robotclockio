<script setup lang="ts">
import { CalendarClock, Bell, Users, BarChart3 } from '@lucide/vue';

const features = [
    {
        icon: CalendarClock,
        title: 'Automated scheduling',
        description:
            'Build shift schedules in minutes with rules-based automation that accounts for availability, overtime, and coverage needs.',
    },
    {
        icon: Bell,
        title: 'Real-time coverage alerts',
        description:
            'Get notified the moment a shift is at risk of going uncovered, with one-tap options to fill the gap.',
    },
    {
        icon: Users,
        title: 'Team self-service',
        description:
            'Let employees swap shifts, request time off, and clock in from anywhere, with manager approval built in.',
    },
    {
        icon: BarChart3,
        title: 'Labor insights',
        description:
            'See labor costs, attendance trends, and overtime risk at a glance with reporting built for operators.',
    },
];
</script>

<template>
    <section id="features" class="border-t border-border/60 bg-muted/30">
        <div class="mx-auto max-w-6xl px-6 py-20">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-semibold tracking-tight md:text-4xl">
                    Everything you need to run the schedule
                </h2>
                <p class="mt-4 text-muted-foreground">
                    RobotClock handles the busywork of scheduling so your
                    managers can focus on the people, not the spreadsheet.
                </p>
            </div>

            <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="feature in features"
                    :key="feature.title"
                    class="rounded-xl border border-border bg-card p-6 text-card-foreground"
                >
                    <div class="mb-4 flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <component :is="feature.icon" class="size-5" />
                    </div>
                    <h3 class="font-medium">{{ feature.title }}</h3>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ feature.description }}
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
