<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Package, Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
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

type ProductRow = {
    id: string;
    name: string;
    description: string | null;
    image_url: string | null;
    price_cents: number;
    active: boolean;
    created_at: string;
};

defineProps<{
    products: ProductRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: admin.dashboard() },
            { title: 'Products', href: admin.products.index() },
        ],
    },
});

function formatCents(cents: number): string {
    return (cents / 100).toLocaleString(undefined, {
        style: 'currency',
        currency: 'USD',
    });
}

function priceInputValue(cents: number): string {
    return (cents / 100).toFixed(2);
}

const editing = ref<ProductRow | null>(null);
const deleting = ref<ProductRow | null>(null);
const createOpen = ref(false);
</script>

<template>
    <Head title="Products" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Products"
                description="Manage the store catalog that owners can order from."
            />

            <Dialog v-model:open="createOpen">
                <DialogTrigger as-child>
                    <Button>
                        <Plus class="size-4" />
                        New product
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="ProductController.store.form()"
                        reset-on-success
                        @success="createOpen = false"
                        class="space-y-4"
                        v-slot="{ errors, processing }"
                    >
                        <DialogHeader>
                            <DialogTitle>New product</DialogTitle>
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

                        <div class="grid gap-2">
                            <Label for="create-price">Price (USD)</Label>
                            <Input
                                id="create-price"
                                name="price"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                            />
                            <InputError :message="errors.price" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="create-image">Picture</Label>
                            <input
                                id="create-image"
                                type="file"
                                name="image"
                                accept="image/*"
                                class="border-input file:text-foreground h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm file:mr-3 file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium"
                            />
                            <InputError :message="errors.image" />
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
            v-if="products.length === 0"
            class="border-sidebar-border/70 dark:border-sidebar-border flex min-h-[50vh] flex-col items-center justify-center gap-3 rounded-xl border text-center"
        >
            <Package class="text-muted-foreground size-10" />
            <p class="font-medium">No products yet</p>
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
                        <th class="p-3 font-medium"></th>
                        <th class="p-3 font-medium">Name</th>
                        <th class="p-3 font-medium">Price</th>
                        <th class="p-3 font-medium">Status</th>
                        <th class="p-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody
                    class="divide-sidebar-border/70 dark:divide-sidebar-border divide-y"
                >
                    <tr v-for="product in products" :key="product.id">
                        <td class="p-3">
                            <div
                                class="border-sidebar-border/70 dark:border-sidebar-border bg-muted flex size-10 items-center justify-center overflow-hidden rounded-md border"
                            >
                                <img
                                    v-if="product.image_url"
                                    :src="product.image_url"
                                    :alt="product.name"
                                    class="size-full object-cover"
                                />
                                <Package v-else class="text-muted-foreground size-4" />
                            </div>
                        </td>
                        <td class="p-3">
                            <p class="font-medium">{{ product.name }}</p>
                            <p
                                v-if="product.description"
                                class="text-muted-foreground text-xs"
                            >
                                {{ product.description }}
                            </p>
                        </td>
                        <td class="p-3">{{ formatCents(product.price_cents) }}</td>
                        <td class="p-3">
                            <Badge
                                :variant="product.active ? 'default' : 'secondary'"
                            >
                                {{ product.active ? 'Active' : 'Inactive' }}
                            </Badge>
                        </td>
                        <td class="p-3 text-right whitespace-nowrap">
                            <Button
                                variant="ghost"
                                size="icon"
                                @click="editing = product"
                            >
                                <Pencil class="size-4" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                @click="deleting = product"
                            >
                                <Trash2 class="size-4" />
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
                    v-bind="ProductController.update.form(editing.id)"
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

                    <div class="grid gap-2">
                        <Label for="edit-price">Price (USD)</Label>
                        <Input
                            id="edit-price"
                            name="price"
                            type="number"
                            step="0.01"
                            min="0.01"
                            :default-value="priceInputValue(editing.price_cents)"
                            required
                        />
                        <InputError :message="errors.price" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="edit-image">Picture</Label>
                        <img
                            v-if="editing.image_url"
                            :src="editing.image_url"
                            :alt="editing.name"
                            class="border-sidebar-border/70 dark:border-sidebar-border size-16 rounded-md border object-cover"
                        />
                        <input
                            id="edit-image"
                            type="file"
                            name="image"
                            accept="image/*"
                            class="border-input file:text-foreground h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm file:mr-3 file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium"
                        />
                        <p class="text-muted-foreground text-xs">
                            Leave blank to keep the current picture.
                        </p>
                        <InputError :message="errors.image" />
                    </div>

                    <div class="flex items-center gap-2">
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
            :open="deleting !== null"
            @update:open="(open) => !open && (deleting = null)"
        >
            <DialogContent v-if="deleting">
                <Form
                    v-bind="ProductController.destroy.form(deleting.id)"
                    @success="deleting = null"
                    class="space-y-4"
                    v-slot="{ processing }"
                >
                    <DialogHeader>
                        <DialogTitle>Delete {{ deleting.name }}?</DialogTitle>
                    </DialogHeader>
                    <p class="text-muted-foreground text-sm">
                        Past orders keep their own record of this product's
                        name and price — this only removes it from the
                        catalog owners can order from.
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
