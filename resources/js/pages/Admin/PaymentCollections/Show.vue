<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Edit, Package, Calendar, Hash, DollarSign } from 'lucide-vue-next';

import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import PaymentLinkGenerator from '@/components/PaymentLinkGenerator.vue';
import { type BreadcrumbItem } from '@/types';

interface PaymentItem {
    id: number;
    name: string;
    description: string | null;
    price: number;
    quantity: number;
    type: string;
}

interface PaymentCollection {
    id: number;
    uuid: string;
    name: string;
    description: string | null;
    status: 'active' | 'completed' | 'expired';
    created_at: string | null;
    updated_at: string | null;
    items?: PaymentItem[];
}

interface Props {
    collection: PaymentCollection;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Payment Collections',
        href: '/admin/payment-collections',
    },
    {
        title: props.collection.name,
        href: `#`,
    },
];

const statusVariant = (status: string) => {
    switch (status) {
        case 'active':
            return 'default';
        case 'completed':
            return 'success';
        case 'pending':
        case 'partially_paid':
            return 'warning';
        case 'expired':
            return 'destructive';
        default:
            return 'outline';
    }
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

const formatDate = (dateString: string | null | undefined) => {
    if (!dateString) {
        return 'N/A';
    }
    const date = new Date(dateString);
    if (isNaN(date.getTime())) {
        return 'Invalid date';
    }
    return new Intl.DateTimeFormat('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
    }).format(date);
};

const calculateTotal = computed(() => {
    if (!props.collection.items || !Array.isArray(props.collection.items)) {
        return 0;
    }
    return props.collection.items.reduce(
        (total, item) => total + item.price * item.quantity,
        0
    );
});

const calculateItemTotal = (item: PaymentItem) => {
    return item.price * item.quantity;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`${collection.name} - Payment Collection`" />

        <div class="min-h-screen bg-muted/20">
            <!-- Header -->
            <div class="border-b border-border/40 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                <div class="flex h-20 items-center justify-between px-8">
                    <div class="flex items-center gap-3">
                        <div>
                            <h1 class="text-3xl font-bold tracking-tight">
                                {{ collection.name }}
                            </h1>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Collection details and items
                            </p>
                        </div>
                        <Badge :variant="statusVariant(collection.status)" class="ml-2">
                            {{ collection.status }}
                        </Badge>
                    </div>
                    <div class="flex items-center gap-2">
                        <Link :href="`/admin/payment-collections/${collection.id}/edit`">
                            <Button size="sm" variant="outline" class="gap-2">
                                <Edit class="h-4 w-4" />
                                Edit
                            </Button>
                        </Link>
                        <Link href="/admin/payment-collections">
                            <Button variant="ghost" size="sm" class="gap-2">
                                <ArrowLeft class="h-4 w-4" />
                                Back
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="mx-auto max-w-6xl px-8 py-8">
                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Description Card -->
                        <Card v-if="collection.description" class="overflow-hidden border-border/50 shadow-sm pt-0">
                            <div class="border-b border-border/50 bg-muted/30 px-6 py-4 border-b-2 border-primary/20 bg-primary hover:bg-primary">
                                <h2 class="font-semibold text-primary-foreground">Description</h2>
                            </div>
                            <div class="p-6">
                                <p class="text-foreground leading-relaxed">
                                    {{ collection.description }}
                                </p>
                            </div>
                        </Card>

                        <!-- Items Card -->
                        <Card class="overflow-hidden border-border/50 shadow-sm pt-0">
                            <div class="border-b border-border/50 bg-muted/30 px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <h2 class="font-semibold text-foreground">Payment Items</h2>
                                    <Badge variant="secondary">
                                        {{ collection.items?.length || 0 }} {{ collection.items?.length === 1 ? 'item' : 'items' }}
                                    </Badge>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow class="border-b-2 border-primary/20 bg-primary hover:bg-primary">
                                            <TableHead class="h-12 px-4 text-sm font-bold uppercase tracking-wider text-primary-foreground">Item</TableHead>
                                            <TableHead class="h-12 px-4 text-sm font-bold uppercase tracking-wider text-primary-foreground">Type</TableHead>
                                            <TableHead class="h-12 px-4 text-sm font-bold uppercase tracking-wider text-primary-foreground text-right">Price</TableHead>
                                            <TableHead class="h-12 px-4 text-sm font-bold uppercase tracking-wider text-primary-foreground text-right">Qty</TableHead>
                                            <TableHead class="h-12 px-4 text-sm font-bold uppercase tracking-wider text-primary-foreground text-right">Total</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow
                                            v-for="item in collection.items || []"
                                            :key="item.id"
                                            class="transition-colors hover:bg-muted/20"
                                        >
                                            <TableCell class="py-4">
                                                <div class="flex flex-col">
                                                    <span class="font-medium text-foreground">
                                                        {{ item.name }}
                                                    </span>
                                                    <span
                                                        v-if="item.description"
                                                        class="mt-1 text-sm text-muted-foreground"
                                                    >
                                                        {{ item.description }}
                                                    </span>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline" class="capitalize">
                                                    {{ item.type }}
                                                </Badge>
                                            </TableCell>
                                            <TableCell class="text-right font-mono text-sm">
                                                {{ formatCurrency(item.price) }}
                                            </TableCell>
                                            <TableCell class="text-right font-mono text-sm">
                                                {{ item.quantity }}
                                            </TableCell>
                                            <TableCell class="text-right font-mono font-semibold">
                                                {{ formatCurrency(calculateItemTotal(item)) }}
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>

                            <!-- Total Footer -->
                            <div class="border-t border-border/50 bg-muted/30 px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-foreground">
                                        Collection Total
                                    </span>
                                    <span class="font-mono text-2xl font-bold text-foreground">
                                        {{ formatCurrency(calculateTotal) }}
                                    </span>
                                </div>
                            </div>
                        </Card>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Payment Link Generator -->
                        <PaymentLinkGenerator :collectionId="collection.id" />

                        <!-- Metadata Card -->
                        <Card class="overflow-hidden border-border/50 shadow-sm pt-0">
                            <div class="border-b border-border/50 bg-muted/30 px-6 py-4 bg-primary hover:bg-primary">
                                <h2 class="font-semibold text-primary-foreground">Details</h2>
                            </div>
                            <div class="divide-y divide-border/50">
                                <!-- UUID -->
                                <div class="p-4">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-md bg-muted/50">
                                            <Hash class="h-4 w-4 text-muted-foreground" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                                                UUID
                                            </p>
                                            <p class="mt-1 text-sm font-mono text-foreground break-all">
                                                {{ collection.uuid }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Created Date -->
                                <div class="p-4">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-md bg-muted/50">
                                            <Calendar class="h-4 w-4 text-muted-foreground" />
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                                                Created
                                            </p>
                                            <p class="mt-1 text-sm text-foreground">
                                                {{ formatDate(collection.created_at) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Updated Date -->
                                <div class="p-4">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-md bg-muted/50">
                                            <Calendar class="h-4 w-4 text-muted-foreground" />
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                                                Last Updated
                                            </p>
                                            <p class="mt-1 text-sm text-foreground">
                                                {{ formatDate(collection.updated_at) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Items Count -->
                                <div class="p-4">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-md bg-muted/50">
                                            <Package class="h-4 w-4 text-muted-foreground" />
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                                                Items
                                            </p>
                                            <p class="mt-1 text-sm font-semibold text-foreground">
                                                {{ collection.items?.length || 0 }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Total Value -->
                                <div class="p-4 bg-muted/20">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-md bg-primary/10">
                                            <DollarSign class="h-4 w-4 text-primary" />
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                                                Total Value
                                            </p>
                                            <p class="mt-1 font-mono text-xl font-bold text-foreground">
                                                {{ formatCurrency(calculateTotal) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Smooth page entrance */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.grid > * {
    animation: fadeInUp 0.4s ease-out;
    animation-fill-mode: both;
}

.grid > *:nth-child(1) { animation-delay: 0.1s; }
.grid > *:nth-child(2) { animation-delay: 0.2s; }
</style>
