<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { register } from '@/routes';
import { Button } from '@/components/ui/button';
</script>

<template>
    <section class="border-t border-border/60 bg-muted/30">
        <div class="mx-auto max-w-6xl px-6 py-20 text-center">
            <h2 class="text-3xl font-semibold tracking-tight md:text-4xl">
                Ready to put your schedule on autopilot?
            </h2>
            <p class="mx-auto mt-4 max-w-xl text-muted-foreground">
                Join teams using RobotClock to cut scheduling time and stop
                chasing coverage gaps.
            </p>
            <Button size="lg" class="mt-8" as-child>
                <Link :href="register()">Start your free trial</Link>
            </Button>
        </div>
    </section>
</template>
