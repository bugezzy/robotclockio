<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';
import { Button } from '@/components/ui/button';

defineProps<{
    auth: { user: unknown | null };
}>();
</script>

<template>
    <header class="border-border/60 sticky top-0 z-40 border-b bg-background/80 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-6">
            <Link :href="dashboard()" class="flex items-center">
                <img
                    src="/images/brand/wordmark.png"
                    alt="RobotClock"
                    class="h-8 w-auto object-contain"
                />
            </Link>

            <nav class="hidden items-center gap-8 text-sm font-medium text-muted-foreground md:flex">
                <a href="#features" class="hover:text-foreground">Features</a>
                <a href="#pricing" class="hover:text-foreground">Pricing</a>
                <a href="#faq" class="hover:text-foreground">FAQ</a>
            </nav>

            <div class="flex items-center gap-2">
                <template v-if="auth.user">
                    <Button as-child>
                        <Link :href="dashboard()">Dashboard</Link>
                    </Button>
                </template>
                <template v-else>
                    <Button variant="ghost" as-child>
                        <Link :href="login()">Log in</Link>
                    </Button>
                    <Button as-child>
                        <Link :href="register()">Get started</Link>
                    </Button>
                </template>
            </div>
        </div>
    </header>
</template>
