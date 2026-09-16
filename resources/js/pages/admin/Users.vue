<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { KeyRound, Pencil, Plus, Trash2, User as UserIcon } from '@lucide/vue';
import { ref } from 'vue';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
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

type UserRow = {
    id: string;
    name: string;
    email: string | null;
    role: string;
    active: boolean;
    team_id: string | null;
    team_name: string | null;
    organization_id: string | null;
    organization_name: string | null;
    has_card: boolean;
};

type TeamOption = { id: string; name: string; organization_id: string };
type OrganizationOption = { id: string; name: string };
type RoleOption = { name: string; description: string };

const props = defineProps<{
    users: UserRow[];
    teams: TeamOption[];
    organizations: OrganizationOption[];
    roles: RoleOption[];
    canManage: boolean;
    isSystem: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: admin.dashboard() },
            { title: 'Users', href: admin.users.index() },
        ],
    },
});

function roleVariant(role: string): 'default' | 'secondary' | 'outline' {
    if (role === 'system') return 'default';
    if (role === 'owner') return 'secondary';
    if (role === 'admin') return 'outline';
    return 'outline';
}

const editing = ref<UserRow | null>(null);
const deleting = ref<UserRow | null>(null);
const settingPassword = ref<UserRow | null>(null);
const createOpen = ref(false);
const createOrganizationId = ref(props.organizations[0]?.id ?? '');
</script>

<template>
    <Head title="Users" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Users"
                description="Everyone who clocks in, plus anyone with admin access."
            />

            <Dialog v-if="canManage" v-model:open="createOpen">
                <DialogTrigger as-child>
                    <Button>
                        <Plus class="size-4" />
                        New user
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="UserController.store.form()"
                        reset-on-success
                        @success="createOpen = false"
                        class="space-y-4"
                        v-slot="{ errors, processing }"
                    >
                        <DialogHeader>
                            <DialogTitle>New user</DialogTitle>
                        </DialogHeader>

                        <div v-if="isSystem" class="grid gap-2">
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
                            <Label for="create-email">Email</Label>
                            <Input
                                id="create-email"
                                name="email"
                                type="email"
                            />
                            <InputError :message="errors.email" />
                            <p class="text-muted-foreground text-xs">
                                Only needed for staff who sign in to this
                                dashboard.
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-role">Role</Label>
                            <Select name="role" default-value="member">
                                <SelectTrigger id="create-role" class="w-full">
                                    <SelectValue placeholder="Select a role" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="role in roles"
                                        :key="role.name"
                                        :value="role.name"
                                    >
                                        {{ role.name }} — {{ role.description }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.role" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-team">Team</Label>
                            <Select name="team_id">
                                <SelectTrigger id="create-team" class="w-full">
                                    <SelectValue placeholder="No team" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="team in teams.filter(
                                            (t) =>
                                                t.organization_id ===
                                                createOrganizationId,
                                        )"
                                        :key="team.id"
                                        :value="team.id"
                                    >
                                        {{ team.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.team_id" />
                        </div>

                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="create-active"
                                name="active"
                                :default-value="true"
                            />
                            <Label for="create-active">Active</Label>
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
            v-if="users.length === 0"
            class="border-sidebar-border/70 dark:border-sidebar-border flex min-h-[50vh] flex-col items-center justify-center gap-3 rounded-xl border text-center"
        >
            <UserIcon class="text-muted-foreground size-10" />
            <p class="font-medium">No users yet</p>
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
                        <th class="p-3 font-medium">Team</th>
                        <th class="p-3 font-medium">Role</th>
                        <th class="p-3 font-medium">Status</th>
                        <th class="p-3 font-medium">Card</th>
                        <th class="p-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody
                    class="divide-sidebar-border/70 dark:divide-sidebar-border divide-y"
                >
                    <tr v-for="user in users" :key="user.id">
                        <td class="p-3">
                            <p class="font-medium">{{ user.name }}</p>
                            <p
                                v-if="user.email"
                                class="text-muted-foreground text-xs"
                            >
                                {{ user.email }}
                            </p>
                        </td>
                        <td class="p-3">
                            {{ user.organization_name ?? '—' }}
                        </td>
                        <td class="p-3">{{ user.team_name ?? '—' }}</td>
                        <td class="p-3">
                            <Badge :variant="roleVariant(user.role)">{{
                                user.role
                            }}</Badge>
                        </td>
                        <td class="p-3">
                            <Badge
                                :variant="user.active ? 'default' : 'secondary'"
                            >
                                {{ user.active ? 'Active' : 'Inactive' }}
                            </Badge>
                        </td>
                        <td class="p-3">
                            {{ user.has_card ? 'Yes' : 'No' }}
                        </td>
                        <td class="p-3 text-right whitespace-nowrap">
                            <template v-if="canManage">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    @click="editing = user"
                                >
                                    <Pencil class="size-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    @click="settingPassword = user"
                                >
                                    <KeyRound class="size-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    @click="deleting = user"
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
                    v-bind="UserController.update.form(editing.id)"
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
                        <Label for="edit-email">Email</Label>
                        <Input
                            id="edit-email"
                            name="email"
                            type="email"
                            :default-value="editing.email ?? ''"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-role">Role</Label>
                        <Select name="role" :default-value="editing.role">
                            <SelectTrigger id="edit-role" class="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="role in roles"
                                    :key="role.name"
                                    :value="role.name"
                                >
                                    {{ role.name }} — {{ role.description }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.role" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-team">Team</Label>
                        <Select
                            name="team_id"
                            :default-value="editing.team_id ?? undefined"
                        >
                            <SelectTrigger id="edit-team" class="w-full">
                                <SelectValue placeholder="No team" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="team in teams.filter(
                                        (t) =>
                                            t.organization_id ===
                                            editing!.organization_id,
                                    )"
                                    :key="team.id"
                                    :value="team.id"
                                >
                                    {{ team.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.team_id" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="edit-active"
                            name="active"
                            :default-checked="editing.active"
                        />
                        <Label for="edit-active">Active</Label>
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
            :open="settingPassword !== null"
            @update:open="(open) => !open && (settingPassword = null)"
        >
            <DialogContent v-if="settingPassword">
                <Form
                    v-bind="
                        UserController.updatePassword.form(settingPassword.id)
                    "
                    reset-on-success
                    @success="settingPassword = null"
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                >
                    <DialogHeader>
                        <DialogTitle
                            >Set password for
                            {{ settingPassword.name }}</DialogTitle
                        >
                    </DialogHeader>

                    <div class="grid gap-2">
                        <Label for="set-password">New password</Label>
                        <Input
                            id="set-password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            autofocus
                            required
                        />
                        <InputError :message="errors.password" />
                        <p class="text-muted-foreground text-xs">
                            At least 8 characters. They'll need to sign in with
                            this immediately.
                        </p>
                    </div>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="secondary">Cancel</Button>
                        </DialogClose>
                        <Button type="submit" :disabled="processing">
                            Set password
                        </Button>
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
                    v-bind="UserController.destroy.form(deleting.id)"
                    @success="deleting = null"
                    class="space-y-4"
                    v-slot="{ processing }"
                >
                    <DialogHeader>
                        <DialogTitle>Delete {{ deleting.name }}?</DialogTitle>
                    </DialogHeader>
                    <p class="text-muted-foreground text-sm">
                        This cannot be undone. Their cards are removed too; past
                        punches keep their record but lose the link to this
                        person.
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
