<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2, UsersRound } from '@lucide/vue';
import { ref } from 'vue';
import TeamController from '@/actions/App/Http/Controllers/Admin/TeamController';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import admin from '@/routes/admin';

type Team = {
    id: string;
    name: string;
    description: string | null;
    active: boolean;
    employees_count: number;
    organization_id: string;
    organization_name: string;
};

type OrganizationOption = {
    id: string;
    name: string;
};

const props = defineProps<{
    teams: Team[];
    organizations: OrganizationOption[];
    canManage: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: admin.dashboard() },
            { title: 'Teams', href: admin.teams.index() },
        ],
    },
});

const editing = ref<Team | null>(null);
const deleting = ref<Team | null>(null);
const createOpen = ref(false);
const createOrganizationId = ref(props.organizations[0]?.id ?? '');
</script>

<template>
    <Head title="Teams" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Teams"
                description="Groups of users within an organization."
            />

            <Dialog v-if="canManage" v-model:open="createOpen">
                <DialogTrigger as-child>
                    <Button>
                        <Plus class="size-4" />
                        New team
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="TeamController.store.form()"
                        reset-on-success
                        @success="createOpen = false"
                        class="space-y-4"
                        v-slot="{ errors, processing }"
                    >
                        <DialogHeader>
                            <DialogTitle>New team</DialogTitle>
                        </DialogHeader>

                        <div v-if="organizations.length > 1" class="grid gap-2">
                            <Label for="create-organization"
                                >Organization</Label
                            >
                            <Select
                                v-model="createOrganizationId"
                                name="organization_id"
                            >
                                <SelectTrigger
                                    id="create-organization"
                                    class="w-full"
                                >
                                    <SelectValue
                                        placeholder="Select an organization"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="organization in organizations"
                                        :key="organization.id"
                                        :value="organization.id"
                                    >
                                        {{ organization.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.organization_id" />
                        </div>
                        <input
                            v-else
                            type="hidden"
                            name="organization_id"
                            :value="createOrganizationId"
                        />

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
                            <Button type="submit" :disabled="processing"
                                >Create</Button
                            >
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>

        <div
            v-if="teams.length === 0"
            class="border-sidebar-border/70 dark:border-sidebar-border flex min-h-[50vh] flex-col items-center justify-center gap-3 rounded-xl border text-center"
        >
            <UsersRound class="text-muted-foreground size-10" />
            <p class="font-medium">No teams yet</p>
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
                        <th class="p-3 font-medium">Organization</th>
                        <th class="p-3 font-medium">Status</th>
                        <th class="p-3 font-medium">Employees</th>
                        <th class="p-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody
                    class="divide-sidebar-border/70 dark:divide-sidebar-border divide-y"
                >
                    <tr v-for="team in teams" :key="team.id">
                        <td class="p-3">
                            <p class="font-medium">{{ team.name }}</p>
                            <p
                                v-if="team.description"
                                class="text-muted-foreground text-xs"
                            >
                                {{ team.description }}
                            </p>
                        </td>
                        <td class="p-3">{{ team.organization_name }}</td>
                        <td class="p-3">
                            <Badge
                                :variant="team.active ? 'default' : 'secondary'"
                            >
                                {{ team.active ? 'Active' : 'Inactive' }}
                            </Badge>
                        </td>
                        <td class="p-3">{{ team.employees_count }}</td>
                        <td class="p-3 text-right whitespace-nowrap">
                            <template v-if="canManage">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    @click="editing = team"
                                >
                                    <Pencil class="size-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    @click="deleting = team"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </template>
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
                    v-bind="TeamController.update.form(editing.id)"
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

                    <div class="flex items-center gap-2">
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
                        <Button type="submit" :disabled="processing"
                            >Save</Button
                        >
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="deleting !== null"
            @update:open="(open) => !open && (deleting = null)"
        >
            <DialogContent v-if="deleting">
                <Form
                    v-bind="TeamController.destroy.form(deleting.id)"
                    @success="deleting = null"
                    class="space-y-4"
                    v-slot="{ processing }"
                >
                    <DialogHeader>
                        <DialogTitle>Delete {{ deleting.name }}?</DialogTitle>
                    </DialogHeader>
                    <p class="text-muted-foreground text-sm">
                        Employees on this team aren't deleted — they just lose
                        their team assignment.
                    </p>
                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="secondary">Cancel</Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="processing"
                        >
                            Delete
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
