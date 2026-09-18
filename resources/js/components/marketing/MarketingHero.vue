<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { register } from '@/routes';
import { Button } from '@/components/ui/button';
</script>

<template>
    <section class="mx-auto max-w-6xl px-6 pt-20 pb-16 text-center md:pt-28 md:pb-24">
        <p class="mb-4 inline-flex items-center rounded-full border border-border px-3 py-1 text-xs font-medium text-muted-foreground">
            Now tracking effort for clubs and teams worldwide
        </p>
        <h1 class="mx-auto max-w-3xl text-4xl font-semibold tracking-tight text-balance md:text-6xl">
            Track everyone's effort with a simple tap.
        </h1>
        <p class="mx-auto mt-6 max-w-2xl text-lg text-muted-foreground">
            RobotClock is an RFID-based time clock built for clubs and
            teams — everyone badges in and out at a kiosk or with
            /clock in Discord, and admins manage teams, cards, and
            attendance from one dashboard.
        </p>
        <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <Button size="lg" as-child>
                <Link :href="register()">Start for free</Link>
            </Button>
            <Button size="lg" variant="outline" as-child>
                <a href="#features">See how it works</a>
            </Button>
        </div>
        <p class="mt-4 text-sm text-muted-foreground">
            No credit card required &middot; RFID cards & readers available
            in the store
        </p>
    </section>
</template>
