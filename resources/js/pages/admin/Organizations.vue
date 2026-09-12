<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Building2, Pencil, Plus } from '@lucide/vue';
import { ref } from 'vue';
import OrganizationController from '@/actions/App/Http/Controllers/Admin/OrganizationController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import admin from '@/routes/admin';

type OrganizationRow = {
    id: string;
    name: string;
    description: string | null;
    active: boolean;
    teams_count: number;
    employees_count: number;
    created_at: string;
};

const props = defineProps<{
    organizations: OrganizationRow[];
    canCreate: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Admin',
                href: admin.dashboard(),
            },
            {
                title: 'Organizations',
                href: admin.organizations.index(),
            },
        ],
    },
});

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString(undefined, {
        dateStyle: 'medium',
    });
}

const editing = ref<OrganizationRow | null>(null);
const createOpen = ref(false);
</script>

<template>
    <Head title="Organizations" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Organizations"
                description="Manage robotclock.io customer organizations."
            />

            <Dialog v-if="canCreate" v-model:open="createOpen">
                <DialogTrigger as-child>
                    <Button>
                        <Plus class="size-4" />
                        New organization
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="OrganizationController.store.form()"
                        reset-on-success
                        @success="createOpen = false"
                        class="space-y-4"
                        v-slot="{ errors, processing }"
                    >
                        <DialogHeader>
                            <DialogTitle>New organization</DialogTitle>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="create-name">Name</Label>
                            <Input id="create-name" name="name" required />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-description">Description</Label>
                            <Input id="create-description" name="description" />
                            <InputError :message="errors.description" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button variant="secondary">Cancel</Button>
                            </DialogClose>
                            <Button type="submit" :disabled="processing">
                                Create
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>

        <div
            v-if="organizations.length === 0"
            class="border-sidebar-border/70 dark:border-sidebar-border flex min-h-[50vh] flex-col items-center justify-center gap-3 rounded-xl border text-center"
        >
            <Building2 class="text-muted-foreground size-10" />
            <div class="space-y-1">
                <p class="font-medium">No organizations yet</p>
                <p class="text-muted-foreground max-w-sm text-sm">
                    Customer organizations will appear here once they're
                    created.
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
                        <th class="p-3 font-medium">Users</th>
                        <th class="p-3 font-medium">Customer since</th>
                        <th class="p-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody
                    class="divide-sidebar-border/70 dark:divide-sidebar-border divide-y"
                >
                    <tr
                        v-for="organization in organizations"
                        :key="organization.id"
                    >
                        <td class="p-3">
                            <p class="font-medium">{{ organization.name }}</p>
                            <p
                                v-if="organization.description"
                                class="text-muted-foreground text-xs"
                            >
                                {{ organization.description }}
                            </p>
                        </td>
                        <td class="p-3">
                            <Badge
                                :variant="
                                    organization.active
                                        ? 'default'
                                        : 'secondary'
                                "
                            >
                                {{
                                    organization.active ? 'Active' : 'Inactive'
                                }}
                            </Badge>
                        </td>
                        <td class="p-3">{{ organization.teams_count }}</td>
                        <td class="p-3">{{ organization.employees_count }}</td>
                        <td class="p-3 whitespace-nowrap">
                            {{ formatDate(organization.created_at) }}
                        </td>
                        <td class="p-3 text-right">
                            <Button
                                variant="ghost"
                                size="icon"
                                @click="editing = organization"
                            >
                                <Pencil class="size-4" />
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Dialog
            :open="editing !== null"
            @update:open="(open) => !open && (editing = null)"
        >
            <DialogContent v-if="editing">
                <Form
                    v-bind="OrganizationController.update.form(editing.id)"
                    @success="editing = null"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                >
                    <DialogHeader>
                        <DialogTitle>Edit {{ editing.name }}</DialogTitle>
                    </DialogHeader>

                    <div class="grid gap-2">
                        <Label for="edit-name">Name</Label>
                        <Input
                            id="edit-name"
                            name="name"
                            :default-value="editing.name"
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-description">Description</Label>
                        <Input
                            id="edit-description"
                            name="description"
                            :default-value="editing.description ?? ''"
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <div v-if="canCreate" class="flex items-center gap-2">
                        <Checkbox
                            id="edit-active"
                            name="active"
                            :default-checked="editing.active"
                        />
                        <Label for="edit-active">Active</Label>
                        <InputError :message="errors.active" />
                    </div>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="secondary">Cancel</Button>
                        </DialogClose>
                        <Button type="submit" :disabled="processing">
                            Save
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
