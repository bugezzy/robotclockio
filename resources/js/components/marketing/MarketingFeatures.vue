<script setup lang="ts">
import { CreditCard, Clock, UsersRound, Package } from '@lucide/vue';

const features = [
    {
        icon: CreditCard,
        title: 'Tap-and-go kiosk',
        description:
            'Members badge in and out with an RFID card at a kiosk — no app to open, no PIN to remember, no fumbling.',
    },
    {
        icon: Clock,
        title: 'Accurate, per-person hours',
        description:
            'Every tap is tied to one member’s RFID tag, so hours are tracked individually, not just totaled for the team.',
    },
    {
        icon: UsersRound,
        title: 'Organized by team',
        description:
            'Group members into teams within your club, and see attendance broken down by team from the admin dashboard.',
    },
    {
        icon: Package,
        title: 'RFID hardware, delivered',
        description:
            'Order standard or premium cards and readers right from the admin store whenever your club needs more.',
    },
];
</script>

<template>
    <section id="features" class="border-t border-border/60 bg-muted/30">
        <div class="mx-auto max-w-6xl px-6 py-20">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-semibold tracking-tight md:text-4xl">
                    Everything you need to track hours
                </h2>
                <p class="mt-4 text-muted-foreground">
                    RobotClock handles the busywork of attendance so your
                    club or team can focus on the people, not the
                    spreadsheet.
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
