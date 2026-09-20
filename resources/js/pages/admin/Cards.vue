<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    CreditCard,
    Plus,
    Search,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import CardController from '@/actions/App/Http/Controllers/Admin/CardController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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

type CardRow = {
    card_uid: string;
    label: string | null;
    issued_at: string;
    revoked_at: string | null;
    employee_name: string | null;
    organization_name: string | null;
};

type UserOption = { id: string; full_name: string; email: string | null };
type UnmatchedScan = { card_uid: string; last_seen_at: string };

const props = defineProps<{
    cards: CardRow[];
    users: UserOption[];
    unmatchedScans: UnmatchedScan[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: admin.dashboard() },
            { title: 'Cards', href: admin.cards.index() },
        ],
    },
});

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

const revoking = ref<CardRow | null>(null);
const createOpen = ref(false);
const cardUid = ref('');

type SortKey =
    | 'card_uid'
    | 'user'
    | 'organization'
    | 'label'
    | 'status'
    | 'issued';

const columns: { key: SortKey; label: string }[] = [
    { key: 'card_uid', label: 'Card UID' },
    { key: 'user', label: 'User' },
    { key: 'organization', label: 'Organization' },
    { key: 'label', label: 'Label' },
    { key: 'status', label: 'Status' },
    { key: 'issued', label: 'Issued' },
];

const search = ref('');
const statusFilter = ref('all');
const organizationFilter = ref('all');
const sortKey = ref<SortKey>('status');
const sortDirection = ref<'asc' | 'desc'>('asc');

const organizationOptions = computed(() =>
    [
        ...new Set(
            props.cards
                .map((card) => card.organization_name)
                .filter((name): name is string => name !== null),
        ),
    ].sort((a, b) => a.localeCompare(b, undefined, { sensitivity: 'base' })),
);

const hasActiveFilters = computed(
    () =>
        search.value.trim() !== '' ||
        statusFilter.value !== 'all' ||
        organizationFilter.value !== 'all',
);

function clearFilters(): void {
    search.value = '';
    statusFilter.value = 'all';
    organizationFilter.value = 'all';
}

function toggleSort(key: SortKey): void {
    if (sortKey.value === key) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';

        return;
    }

    sortKey.value = key;
    sortDirection.value = 'asc';
}

/**
 * Empty values are returned as '' and always sort last, regardless of
 * direction. Issued sorts by timestamp; the rest by their displayed text.
 */
function sortValue(card: CardRow, key: SortKey): string | number {
    switch (key) {
        case 'card_uid':
            return card.card_uid;
        case 'user':
            return card.employee_name ?? '';
        case 'organization':
            return card.organization_name ?? '';
        case 'label':
            return card.label ?? '';
        case 'status':
            return card.revoked_at ? 'Revoked' : 'Active';
        case 'issued':
            return Date.parse(card.issued_at);
    }
}

function compareValues(a: string | number, b: string | number): number {
    if (typeof a === 'number' && typeof b === 'number') {
        return a - b;
    }

    return String(a).localeCompare(String(b), undefined, {
        sensitivity: 'base',
    });
}

const visibleCards = computed(() => {
    const term = search.value.trim().toLowerCase();

    const matches = props.cards.filter((card) => {
        if (
            term !== '' &&
            ![
                card.card_uid,
                card.employee_name,
                card.organization_name,
                card.label,
            ].some((field) => field?.toLowerCase().includes(term))
        ) {
            return false;
        }

        if (
            statusFilter.value !== 'all' &&
            (card.revoked_at === null) !== (statusFilter.value === 'active')
        ) {
            return false;
        }

        return (
            organizationFilter.value === 'all' ||
            card.organization_name === organizationFilter.value
        );
    });

    return matches.sort((a, b) => {
        const first = sortValue(a, sortKey.value);
        const second = sortValue(b, sortKey.value);

        if (first === '' && second !== '') {
            return 1;
        }

        if (second === '' && first !== '') {
            return -1;
        }

        const order = compareValues(first, second);

        if (order === 0) {
            return Date.parse(b.issued_at) - Date.parse(a.issued_at);
        }

        return sortDirection.value === 'asc' ? order : -order;
    });
});
</script>

<template>
    <Head title="Cards" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading title="Cards" description="Issue and revoke RFID cards." />

            <Dialog v-model:open="createOpen">
                <DialogTrigger as-child>
                    <Button>
                        <Plus class="size-4" />
                        Issue card
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="CardController.store.form()"
                        reset-on-success
                        @success="createOpen = false; cardUid = ''"
                        class="space-y-4"
                        v-slot="{ errors, processing }"
                    >
                        <DialogHeader>
                            <DialogTitle>Issue a card</DialogTitle>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="create-employee">User</Label>
                            <Select name="user_id">
                                <SelectTrigger
                                    id="create-employee"
                                    class="w-full"
                                >
                                    <SelectValue placeholder="Select a user" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="employee in users"
                                        :key="employee.id"
                                        :value="employee.id"
                                    >
                                        {{
                                            employee.email
                                                ? `${employee.full_name} (${employee.email})`
                                                : employee.full_name
                                        }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.user_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-card-uid">Card UID</Label>
                            <Input
                                id="create-card-uid"
                                v-model="cardUid"
                                name="card_uid"
                                required
                                placeholder="04A1B2C3"
                                class="font-mono uppercase"
                            />
                            <InputError :message="errors.card_uid" />
                            <p class="text-muted-foreground text-xs">
                                Hex characters only, as printed on the card or
                                scanned by the reader.
                            </p>
                        </div>

                        <div
                            v-if="unmatchedScans.length > 0"
                            class="grid gap-2"
                        >
                            <Label>Don't know the UID?</Label>
                            <div
                                class="border-sidebar-border/70 dark:border-sidebar-border flex max-h-32 flex-col gap-1 overflow-y-auto rounded-md border p-2"
                            >
                                <button
                                    v-for="scan in unmatchedScans"
                                    :key="scan.card_uid"
                                    type="button"
                                    class="hover:bg-accent flex items-center justify-between rounded px-2 py-1 text-left text-sm"
                                    @click="cardUid = scan.card_uid"
                                >
                                    <span class="font-mono">{{
                                        scan.card_uid
                                    }}</span>
                                    <span
                                        class="text-muted-foreground text-xs"
                                        >{{
                                            formatDateTime(scan.last_seen_at)
                                        }}</span
                                    >
                                </button>
                            </div>
                            <p class="text-muted-foreground text-xs">
                                Recent kiosk scans that didn't match anyone.
                                Click one to fill it in above.
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-label">Label</Label>
                            <Input
                                id="create-label"
                                name="label"
                                placeholder="e.g. Spare, Badge #4"
                            />
                            <InputError :message="errors.label" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button variant="secondary">Cancel</Button>
                            </DialogClose>
                            <Button type="submit" :disabled="processing"
                                >Issue</Button
                            >
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>

        <div v-if="cards.length > 0" class="flex flex-wrap items-center gap-2">
            <div class="relative w-full sm:w-64">
                <Search
                    class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2"
                />
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search UID, user, label…"
                    aria-label="Search cards"
                    class="pl-9"
                />
            </div>

            <Select v-model="statusFilter">
                <SelectTrigger class="w-36" aria-label="Filter by status">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Any status</SelectItem>
                    <SelectItem value="active">Active</SelectItem>
                    <SelectItem value="revoked">Revoked</SelectItem>
                </SelectContent>
            </Select>

            <Select
                v-if="organizationOptions.length > 1"
                v-model="organizationFilter"
            >
                <SelectTrigger class="w-44" aria-label="Filter by organization">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All organizations</SelectItem>
                    <SelectItem
                        v-for="organization in organizationOptions"
                        :key="organization"
                        :value="organization"
                    >
                        {{ organization }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Button
                v-if="hasActiveFilters"
                variant="ghost"
                @click="clearFilters"
            >
                <X class="size-4" />
                Clear
            </Button>

            <p class="text-muted-foreground ml-auto text-sm">
                {{ visibleCards.length }} of {{ cards.length }}
                {{ cards.length === 1 ? 'card' : 'cards' }}
            </p>
        </div>

        <div
            v-if="cards.length === 0"
            class="border-sidebar-border/70 dark:border-sidebar-border flex min-h-[50vh] flex-col items-center justify-center gap-3 rounded-xl border text-center"
        >
            <CreditCard class="text-muted-foreground size-10" />
            <p class="font-medium">No cards issued yet</p>
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
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            class="p-3 font-medium"
                            :aria-sort="
                                sortKey === column.key
                                    ? sortDirection === 'asc'
                                        ? 'ascending'
                                        : 'descending'
                                    : 'none'
                            "
                        >
                            <button
                                type="button"
                                class="hover:text-foreground -m-1 inline-flex cursor-pointer items-center gap-1 rounded p-1 font-medium"
                                :class="{
                                    'text-foreground': sortKey === column.key,
                                }"
                                @click="toggleSort(column.key)"
                            >
                                {{ column.label }}
                                <ArrowUp
                                    v-if="
                                        sortKey === column.key &&
                                        sortDirection === 'asc'
                                    "
                                    class="size-3.5"
                                />
                                <ArrowDown
                                    v-else-if="sortKey === column.key"
                                    class="size-3.5"
                                />
                                <ArrowUpDown
                                    v-else
                                    class="size-3.5 opacity-40"
                                />
                            </button>
                        </th>
                        <th class="p-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody
                    class="divide-sidebar-border/70 dark:divide-sidebar-border divide-y"
                >
                    <tr v-if="visibleCards.length === 0">
                        <td
                            colspan="7"
                            class="text-muted-foreground p-8 text-center"
                        >
                            No cards match your search or filters.
                        </td>
                    </tr>
                    <tr v-for="card in visibleCards" :key="card.card_uid">
                        <td class="p-3 font-mono">{{ card.card_uid }}</td>
                        <td class="p-3">{{ card.employee_name ?? '—' }}</td>
                        <td class="p-3">{{ card.organization_name ?? '—' }}</td>
                        <td class="p-3">{{ card.label ?? '—' }}</td>
                        <td class="p-3">
                            <Badge
                                :variant="
                                    card.revoked_at ? 'secondary' : 'default'
                                "
                            >
                                {{ card.revoked_at ? 'Revoked' : 'Active' }}
                            </Badge>
                        </td>
                        <td class="p-3 whitespace-nowrap">
                            {{ formatDateTime(card.issued_at) }}
                        </td>
                        <td class="p-3 text-right">
                            <Button
                                v-if="!card.revoked_at"
                                variant="ghost"
                                size="sm"
                                @click="revoking = card"
                            >
                                Revoke
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Dialog
            :open="revoking !== null"
            @update:open="(open) => !open && (revoking = null)"
        >
            <DialogContent v-if="revoking">
                <Form
                    v-bind="CardController.destroy.form(revoking.card_uid)"
                    @success="revoking = null"
                    class="space-y-4"
                    v-slot="{ processing }"
                >
                    <DialogHeader>
                        <DialogTitle>Revoke this card?</DialogTitle>
                    </DialogHeader>
                    <p class="text-muted-foreground text-sm">
                        {{ revoking.employee_name }}'s card ({{
                            revoking.card_uid
                        }}) will no longer work at the kiosk. This can't be
                        undone — issue a new card if they need one.
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
                            Revoke
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
