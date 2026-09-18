<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home, privacy, terms } from '@/routes';

const year = new Date().getFullYear();
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <header class="border-b border-border/60">
            <div class="mx-auto flex h-16 max-w-3xl items-center px-6">
                <Link :href="home()" class="flex items-center">
                    <img
                        src="/images/brand/wordmark.png"
                        alt="RobotClock"
                        class="h-8 w-auto object-contain"
                    />
                </Link>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-6 py-12">
            <article
                class="text-sm [&_a]:underline [&_a]:underline-offset-4 [&_h1]:text-3xl [&_h1]:font-semibold [&_h1]:tracking-tight [&_h2]:mt-10 [&_h2]:text-lg [&_h2]:font-semibold [&_li]:leading-7 [&_p]:mt-3 [&_p]:leading-7 [&_ul]:mt-3 [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:pl-6"
            >
                <slot />
            </article>
        </main>

        <footer class="border-t border-border/60">
            <div
                class="mx-auto flex max-w-3xl flex-col gap-4 px-6 py-8 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between"
            >
                <p>&copy; {{ year }} RobotClock.io. All rights reserved.</p>
                <nav class="flex items-center gap-6">
                    <Link :href="terms()" class="hover:text-foreground">Terms</Link>
                    <Link :href="privacy()" class="hover:text-foreground">Privacy</Link>
                </nav>
            </div>
        </footer>
    </div>
</template>
