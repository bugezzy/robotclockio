<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { privacy, terms } from '@/routes';

const year = new Date().getFullYear();
</script>

<template>
    <footer class="border-t border-border/60">
        <div class="mx-auto flex max-w-6xl flex-col gap-6 px-6 py-10 text-sm text-muted-foreground md:flex-row md:items-center md:justify-between">
            <img
                src="/images/brand/wordmark.png"
                alt="RobotClock"
                class="h-6 w-auto object-contain"
            />
            <nav class="flex flex-wrap items-center gap-6">
                <a href="#features" class="hover:text-foreground">Features</a>
                <a href="#pricing" class="hover:text-foreground">Pricing</a>
                <a href="#faq" class="hover:text-foreground">FAQ</a>
                <Link :href="terms()" class="hover:text-foreground">Terms</Link>
                <Link :href="privacy()" class="hover:text-foreground">Privacy</Link>
            </nav>
            <p>&copy; {{ year }} RobotClock.io. All rights reserved.</p>
        </div>
    </footer>
</template>
