<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ArrowDownLeft, ArrowUpRight, Clock } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import admin from '@/routes/admin';

type Punch = {
    id: string;
    card_uid: string;
    direction: 'in' | 'out';
    punched_at: string;
    recorded_at: string;
    employee_name: string | null;
    organization_name: string | null;
};

defineProps<{
    punches: Punch[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: admin.dashboard() },
            { title: 'Punches', href: admin.punches.index() },
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
    <Head title="Punches" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Punches"
            description="The 200 most recent clock in/out events. Recorded by the kiosk — not editable here."
        />

        <div
            v-if="punches.length === 0"
            class="border-sidebar-border/70 dark:border-sidebar-border flex min-h-[50vh] flex-col items-center justify-center gap-3 rounded-xl border text-center"
        >
            <Clock class="text-muted-foreground size-10" />
            <p class="font-medium">No punches recorded yet</p>
        </div>

        <div
            v-else
            class="border-sidebar-border/70 dark:border-sidebar-border overflow-x-auto rounded-xl border"
        >
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-sidebar-border/70 dark:border-sidebar-border text-muted-foreground border-b text-left"
                    >
                        <th class="p-3 font-medium">User</th>
                        <th class="p-3 font-medium">Organization</th>
                        <th class="p-3 font-medium">Direction</th>
                        <th class="p-3 font-medium">Card UID</th>
                        <th class="p-3 font-medium">Punched at</th>
                    </tr>
                </thead>
                <tbody
                    class="divide-sidebar-border/70 dark:divide-sidebar-border divide-y"
                >
                    <tr v-for="punch in punches" :key="punch.id">
                        <td class="p-3">
                            {{ punch.employee_name ?? 'Unknown' }}
                        </td>
                        <td class="p-3">
                            {{ punch.organization_name ?? '—' }}
                        </td>
                        <td class="p-3">
                            <span class="inline-flex items-center gap-1.5">
                                <ArrowDownLeft
                                    v-if="punch.direction === 'in'"
                                    class="text-muted-foreground size-4"
                                />
                                <ArrowUpRight
                                    v-else
                                    class="text-muted-foreground size-4"
                                />
                                {{ punch.direction }}
                            </span>
                        </td>
                        <td class="p-3 font-mono">{{ punch.card_uid }}</td>
                        <td class="p-3 whitespace-nowrap">
                            {{ formatDateTime(punch.punched_at) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
