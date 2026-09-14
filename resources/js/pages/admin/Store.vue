<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Package, ShoppingCart, TriangleAlert } from '@lucide/vue';
import { computed, reactive } from 'vue';
import StoreController from '@/actions/App/Http/Controllers/Admin/StoreController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import admin from '@/routes/admin';

type StoreProduct = {
    id: string;
    name: string;
    description: string | null;
    imageUrl: string | null;
    priceCents: number;
};

type OrderItemRow = {
    product_name: string;
    quantity: number;
};

type OrderRow = {
    id: string;
    status: string;
    total_cents: number;
    currency: string;
    created_at: string;
    items: OrderItemRow[];
};

const props = defineProps<{
    checkoutEnabled: boolean;
    products: StoreProduct[];
    orders: OrderRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: admin.dashboard() },
            { title: 'Store', href: admin.store.index() },
        ],
    },
});

const quantities = reactive<Record<string, number>>(
    Object.fromEntries(props.products.map((product) => [product.id, 0])),
);

const totalCents = computed(() =>
    props.products.reduce(
        (sum, product) => sum + (quantities[product.id] || 0) * product.priceCents,
        0,
    ),
);

function formatCents(cents: number): string {
    return (cents / 100).toLocaleString(undefined, {
        style: 'currency',
        currency: 'USD',
    });
}

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

function statusVariant(status: string): 'default' | 'secondary' | 'destructive' {
    if (status === 'paid') return 'default';
    if (status === 'canceled') return 'destructive';
    return 'secondary';
}

</script>

<template>
    <Head title="Store" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Store"
            description="Order more RFID cards and readers for your organization."
        />

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <ShoppingCart class="text-muted-foreground size-4" />
                    Place an order
                </CardTitle>
            </CardHeader>
            <CardContent class="flex flex-col gap-4">
                <Alert v-if="!checkoutEnabled" variant="destructive">
                    <TriangleAlert />
                    <AlertTitle>Checkout is unavailable</AlertTitle>
                    <AlertDescription>
                        Ordering isn't set up for this environment yet.
                        Contact support to enable it.
                    </AlertDescription>
                </Alert>

                <Form
                    v-bind="StoreController.checkout.form()"
                    class="flex flex-col gap-4"
                    v-slot="{ errors, processing }"
                >
                    <div
                        v-for="product in products"
                        :key="product.id"
                        class="flex items-center justify-between gap-4 border-b pb-4 last:border-b-0 last:pb-0"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="border-sidebar-border/70 dark:border-sidebar-border bg-muted flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-md border"
                            >
                                <img
                                    v-if="product.imageUrl"
                                    :src="product.imageUrl"
                                    :alt="product.name"
                                    class="size-full object-cover"
                                />
                                <Package v-else class="text-muted-foreground size-5" />
                            </div>
                            <div>
                                <p class="font-medium">{{ product.name }}</p>
                                <p
                                    v-if="product.description"
                                    class="text-muted-foreground text-xs"
                                >
                                    {{ product.description }}
                                </p>
                                <p class="text-muted-foreground text-sm">
                                    {{ formatCents(product.priceCents) }} each
                                </p>
                            </div>
                        </div>
                        <div class="grid gap-1">
                            <Label :for="`quantity-${product.id}`" class="sr-only"
                                >{{ product.name }} quantity</Label
                            >
                            <Input
                                :id="`quantity-${product.id}`"
                                v-model.number="quantities[product.id]"
                                :name="product.id"
                                type="number"
                                min="0"
                                max="1000"
                                class="w-24"
                                :disabled="!checkoutEnabled"
                            />
                        </div>
                    </div>

                    <InputError :message="errors.quantities" />

                    <div class="flex items-center justify-between pt-2">
                        <p class="text-lg font-semibold">
                            Total: {{ formatCents(totalCents) }}
                        </p>
                        <Button
                            type="submit"
                            :disabled="!checkoutEnabled || processing || totalCents === 0"
                        >
                            Checkout with Stripe
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>

        <div
            class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border"
        >
            <div
                class="border-sidebar-border/70 dark:border-sidebar-border flex items-center gap-2 border-b p-4"
            >
                <Package class="text-muted-foreground size-4" />
                <h3 class="font-medium">Order history</h3>
            </div>

            <p
                v-if="orders.length === 0"
                class="text-muted-foreground p-4 text-sm"
            >
                No orders placed yet.
            </p>

            <table v-else class="w-full text-sm">
                <thead>
                    <tr
                        class="border-sidebar-border/70 dark:border-sidebar-border text-muted-foreground border-b text-left"
                    >
                        <th class="p-3 font-medium">Items</th>
                        <th class="p-3 font-medium">Total</th>
                        <th class="p-3 font-medium">Status</th>
                        <th class="p-3 font-medium">Placed at</th>
                    </tr>
                </thead>
                <tbody
                    class="divide-sidebar-border/70 dark:divide-sidebar-border divide-y"
                >
                    <tr v-for="order in orders" :key="order.id">
                        <td class="p-3">
                            <span
                                v-for="(item, index) in order.items"
                                :key="index"
                            >
                                {{ item.quantity }}&times;
                                {{ item.product_name }}{{
                                    index < order.items.length - 1 ? ', ' : ''
                                }}
                            </span>
                        </td>
                        <td class="p-3">{{ formatCents(order.total_cents) }}</td>
                        <td class="p-3">
                            <Badge :variant="statusVariant(order.status)">
                                {{ order.status }}
                            </Badge>
                        </td>
                        <td class="p-3 whitespace-nowrap">
                            {{ formatDateTime(order.created_at) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
