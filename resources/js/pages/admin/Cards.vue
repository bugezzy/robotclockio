<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { CreditCard, Plus } from '@lucide/vue';
import { ref } from 'vue';
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

type UserOption = { id: string; full_name: string };

defineProps<{
    cards: CardRow[];
    users: UserOption[];
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
                        @success="createOpen = false"
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
                                        {{ employee.full_name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.user_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-card-uid">Card UID</Label>
                            <Input
                                id="create-card-uid"
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
                        <th class="p-3 font-medium">Card UID</th>
                        <th class="p-3 font-medium">User</th>
                        <th class="p-3 font-medium">Organization</th>
                        <th class="p-3 font-medium">Label</th>
                        <th class="p-3 font-medium">Status</th>
                        <th class="p-3 font-medium">Issued</th>
                        <th class="p-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody
                    class="divide-sidebar-border/70 dark:divide-sidebar-border divide-y"
                >
                    <tr v-for="card in cards" :key="card.card_uid">
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
