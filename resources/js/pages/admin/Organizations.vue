<script setup lang="ts">
import { Form, Head, useHttp } from '@inertiajs/vue3';
import { Building2, Check, Copy, KeyRound, Pencil, Plus, ShieldOff } from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
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
    canManageLicenses: boolean;
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

type LicenseIssuance = {
    issued_at: string;
    issued_by: string | null;
};

const licensing = ref<OrganizationRow | null>(null);
const licenseText = ref<string | null>(null);
const licenseHistory = ref<LicenseIssuance[]>([]);
const licenseError = ref<string | null>(null);
const licenseCopied = ref(false);
const issuingLicense = ref(false);
const confirmingRevoke = ref(false);
const revokingLicense = ref(false);

const licenseHttp = useHttp();

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

async function openLicense(organization: OrganizationRow) {
    licensing.value = organization;
    licenseText.value = null;
    licenseHistory.value = [];
    licenseError.value = null;
    licenseCopied.value = false;
    confirmingRevoke.value = false;

    try {
        const { history } = (await licenseHttp.submit(
            OrganizationController.licenseHistory(organization.id),
        )) as { history: LicenseIssuance[] };

        licenseHistory.value = history;
    } catch {
        licenseError.value = 'Could not load license history for this organization.';
    }
}

async function issueLicense() {
    if (!licensing.value) {
        return;
    }

    issuingLicense.value = true;
    licenseError.value = null;
    licenseCopied.value = false;

    try {
        const { license, history } = (await licenseHttp.submit(
            OrganizationController.issueLicense(licensing.value.id),
        )) as { license: string; history: LicenseIssuance[] };

        licenseText.value = license;
        licenseHistory.value = history;
    } catch {
        licenseError.value = 'Could not generate a license for this organization.';
    } finally {
        issuingLicense.value = false;
    }
}

async function revokeLicense() {
    if (!licensing.value) {
        return;
    }

    revokingLicense.value = true;
    licenseError.value = null;

    try {
        const { history } = (await licenseHttp.submit(
            OrganizationController.revokeLicense(licensing.value.id),
        )) as { history: LicenseIssuance[] };

        licenseHistory.value = history;
        licenseText.value = null;
        licenseCopied.value = false;
        confirmingRevoke.value = false;
        toast.success(`License revoked for ${licensing.value.name}.`, {
            description: 'Every previously issued license is now invalid — each kiosk needs a new one pasted in.',
        });
    } catch {
        licenseError.value = 'Could not revoke the license for this organization.';
    } finally {
        revokingLicense.value = false;
    }
}

async function copyLicense() {
    if (!licenseText.value) {
        return;
    }

    await navigator.clipboard.writeText(licenseText.value);
    licenseCopied.value = true;
}
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
                        <td class="p-3 text-right whitespace-nowrap">
                            <Button
                                v-if="canManageLicenses"
                                variant="ghost"
                                size="icon"
                                title="Kiosk license"
                                @click="openLicense(organization)"
                            >
                                <KeyRound class="size-4" />
                            </Button>
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
                            :default-value="editing.active"
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

        <Dialog
            :open="licensing !== null"
            @update:open="(open) => !open && (licensing = null)"
        >
            <DialogContent v-if="licensing">
                <DialogHeader>
                    <DialogTitle>License for {{ licensing.name }}</DialogTitle>
                </DialogHeader>

                <p class="text-muted-foreground text-sm">
                    Generating a license creates a new one to paste into the
                    kiosk's activation dialog. It's as sensitive as the
                    organization's kiosk key — anyone holding it can read
                    this organization's roster and record punches for it.
                </p>

                <p v-if="licenseError" class="text-destructive text-sm">
                    {{ licenseError }}
                </p>

                <textarea
                    v-if="licenseText"
                    readonly
                    :value="licenseText"
                    rows="4"
                    class="border-input bg-muted w-full resize-none rounded-md border p-2 font-mono text-xs"
                    @focus="($event.target as HTMLTextAreaElement).select()"
                />

                <div class="flex gap-2">
                    <Button :disabled="issuingLicense" @click="issueLicense">
                        <KeyRound class="size-4" />
                        {{
                            issuingLicense
                                ? 'Generating…'
                                : licenseText
                                  ? 'Generate new license'
                                  : 'Generate license'
                        }}
                    </Button>
                    <Button
                        v-if="licenseText"
                        variant="secondary"
                        @click="copyLicense"
                    >
                        <component
                            :is="licenseCopied ? Check : Copy"
                            class="size-4"
                        />
                        {{ licenseCopied ? 'Copied' : 'Copy' }}
                    </Button>
                </div>

                <div v-if="licenseHistory.length > 0" class="space-y-2">
                    <p class="text-muted-foreground text-xs font-medium">
                        Issuance history
                    </p>
                    <ul class="max-h-32 space-y-1 overflow-y-auto text-xs">
                        <li
                            v-for="(issuance, index) in licenseHistory"
                            :key="index"
                            class="text-muted-foreground flex justify-between gap-2"
                        >
                            <span>{{ formatDateTime(issuance.issued_at) }}</span>
                            <span v-if="issuance.issued_by">{{
                                issuance.issued_by
                            }}</span>
                        </li>
                    </ul>
                </div>

                <div class="space-y-2 border-t pt-4">
                    <p class="text-muted-foreground text-xs font-medium">
                        Danger zone
                    </p>

                    <Button
                        v-if="!confirmingRevoke"
                        variant="destructive"
                        size="sm"
                        @click="confirmingRevoke = true"
                    >
                        <ShieldOff class="size-4" />
                        Revoke license
                    </Button>

                    <template v-else>
                        <p class="text-destructive text-sm">
                            This immediately invalidates every license
                            currently issued for this organization — every
                            kiosk running it will need a newly generated
                            license pasted in before it can log in or record
                            punches again. This cannot be undone.
                        </p>
                        <div class="flex gap-2">
                            <Button
                                variant="secondary"
                                size="sm"
                                @click="confirmingRevoke = false"
                            >
                                Cancel
                            </Button>
                            <Button
                                variant="destructive"
                                size="sm"
                                :disabled="revokingLicense"
                                @click="revokeLicense"
                            >
                                {{
                                    revokingLicense
                                        ? 'Revoking…'
                                        : 'Confirm revoke'
                                }}
                            </Button>
                        </div>
                    </template>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Close</Button>
                    </DialogClose>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
