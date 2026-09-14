<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ArrowDownLeft, ArrowUpRight } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import admin from '@/routes/admin';

type Stats = {
    primary: {
        label: string;
        count: number;
        subLabel: string;
        subCount: number;
    } | null;
    users: number;
    activeCards: number;
    punchesToday: number;
};

type RecentPunch = {
    id: string;
    direction: 'in' | 'out';
    punched_at: string;
    employee_name: string | null;
    organization_name: string | null;
};

defineProps<{
    stats: Stats;
    recentPunches: RecentPunch[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Admin',
                href: admin.dashboard(),
            },
        ],
    },
});

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}
</script>

<template>
    <Head title="Admin" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
        <Heading
            title="Dashboard"
            description="Overview of your activity."
        />

        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <Card v-if="stats.primary">
                <CardHeader>
                    <CardTitle
                        class="text-muted-foreground text-sm font-medium"
                    >
                        {{ stats.primary.label }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold">
                        {{ stats.primary.count }}
                    </p>
                    <p class="text-muted-foreground text-xs">
                        {{ stats.primary.subCount }} {{ stats.primary.subLabel }}
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle
                        class="text-muted-foreground text-sm font-medium"
                    >
                        Users
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold">
                        {{ stats.users }}
                    </p>
                    <p class="text-muted-foreground text-xs">
                        {{ stats.activeCards }} active cards issued
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle
                        class="text-muted-foreground text-sm font-medium"
                    >
                        Punches today
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-semibold">
                        {{ stats.punchesToday }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <div
            class="border-sidebar-border/70 dark:border-sidebar-border flex-1 rounded-xl border"
        >
            <div
                class="border-sidebar-border/70 dark:border-sidebar-border border-b p-4"
            >
                <h3 class="font-medium">Recent punches</h3>
            </div>

            <p
                v-if="recentPunches.length === 0"
                class="text-muted-foreground p-4 text-sm"
            >
                No punches recorded yet.
            </p>

            <ul
                v-else
                class="divide-sidebar-border/70 dark:divide-sidebar-border divide-y"
            >
                <li
                    v-for="punch in recentPunches"
                    :key="punch.id"
                    class="flex items-center justify-between gap-4 p-4"
                >
                    <div class="flex items-center gap-3">
                        <ArrowDownLeft
                            v-if="punch.direction === 'in'"
                            class="text-muted-foreground size-4 shrink-0"
                        />
                        <ArrowUpRight
                            v-else
                            class="text-muted-foreground size-4 shrink-0"
                        />
                        <div>
                            <p class="text-sm font-medium">
                                {{ punch.employee_name ?? 'Unknown user' }}
                            </p>
                            <p class="text-muted-foreground text-xs">
                                {{
                                    punch.organization_name ??
                                    'Unknown organization'
                                }}
                            </p>
                        </div>
                    </div>
                    <p class="text-muted-foreground text-xs whitespace-nowrap">
                        {{ formatDateTime(punch.punched_at) }}
                    </p>
                </li>
            </ul>
        </div>
    </div>
</template>
