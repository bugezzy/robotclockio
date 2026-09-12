<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Users } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import admin from '@/routes/admin';

type Customer = {
    id: string;
    name: string;
    description: string | null;
    active: boolean;
    teams_count: number;
    employees_count: number;
    created_at: string;
};

defineProps<{
    customers: Customer[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Admin',
                href: admin.dashboard(),
            },
            {
                title: 'Customers',
                href: admin.customers.index(),
            },
        ],
    },
});

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString(undefined, {
        dateStyle: 'medium',
    });
}
</script>

<template>
    <Head title="Customers" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Customers"
            description="Manage robotclock.io customer accounts."
        />

        <div
            v-if="customers.length === 0"
            class="border-sidebar-border/70 dark:border-sidebar-border flex min-h-[50vh] flex-col items-center justify-center gap-3 rounded-xl border text-center"
        >
            <Users class="text-muted-foreground size-10" />
            <div class="space-y-1">
                <p class="font-medium">No customers yet</p>
                <p class="text-muted-foreground max-w-sm text-sm">
                    Customer accounts will appear here once they're created.
                </p>
            </div>
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
                        <th class="p-3 font-medium">Name</th>
                        <th class="p-3 font-medium">Status</th>
                        <th class="p-3 font-medium">Teams</th>
                        <th class="p-3 font-medium">Employees</th>
                        <th class="p-3 font-medium">Customer since</th>
                    </tr>
                </thead>
                <tbody
                    class="divide-sidebar-border/70 dark:divide-sidebar-border divide-y"
                >
                    <tr v-for="customer in customers" :key="customer.id">
                        <td class="p-3">
                            <p class="font-medium">{{ customer.name }}</p>
                            <p
                                v-if="customer.description"
                                class="text-muted-foreground text-xs"
                            >
                                {{ customer.description }}
                            </p>
                        </td>
                        <td class="p-3">
                            <Badge
                                :variant="
                                    customer.active ? 'default' : 'secondary'
                                "
                            >
                                {{ customer.active ? 'Active' : 'Inactive' }}
                            </Badge>
                        </td>
                        <td class="p-3">{{ customer.teams_count }}</td>
                        <td class="p-3">{{ customer.employees_count }}</td>
                        <td class="p-3 whitespace-nowrap">
                            {{ formatDate(customer.created_at) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
