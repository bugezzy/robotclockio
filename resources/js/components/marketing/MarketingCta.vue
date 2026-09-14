<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { register } from '@/routes';
import { Button } from '@/components/ui/button';
</script>

<template>
    <section class="border-t border-border/60 bg-muted/30">
        <div class="mx-auto max-w-6xl px-6 py-20 text-center">
            <h2 class="text-3xl font-semibold tracking-tight md:text-4xl">
                Ready to track hours with a tap?
            </h2>
            <p class="mx-auto mt-4 max-w-xl text-muted-foreground">
                Join clubs and teams using RobotClock to track member hours
                accurately and stop chasing paper sign-in sheets.
            </p>
            <Button size="lg" class="mt-8" as-child>
                <Link :href="register()">Start for free</Link>
            </Button>
        </div>
    </section>
</template>
