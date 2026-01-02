<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { DollarSign, Package, TrendingUp, CheckCircle, Clock, Eye, ArrowUpRight } from 'lucide-vue-next';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

interface Stats {
    total_collections: number;
    total_revenue: number;
    total_pending: number;
    completed_payments: number;
    collections_by_status: {
        active: number;
        completed: number;
        pending: number;
        partially_paid: number;
        expired: number;
    };
}

interface RecentCollection {
    id: number;
    name: string;
    status: string;
    items_count: number;
    total_amount: number;
    created_at: string;
}

interface Props {
    stats: Stats;
    recent_collections: RecentCollection[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(date);
};

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

const formatStatus = (status: string) => {
    return status
        .replace(/_/g, ' ')
        .split(' ')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex min-h-screen flex-col">
            <!-- Header -->
            <div class="border-b border-border bg-background">
                <div class="flex h-16 items-center px-8">
                    <h1 class="text-xl font-semibold">Dashboard</h1>
                </div>
            </div>

            <!-- Content -->
            <div class="flex-1 px-8 py-6">
                <!-- Stats Grid -->
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Total Collections -->
                    <Card class="stripe-card border p-5">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Collections</p>
                                <p class="mt-2 font-mono text-3xl font-semibold tabular-nums">{{ stats.total_collections }}</p>
                            </div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-md bg-primary/10">
                                <Package class="h-5 w-5 text-primary" />
                            </div>
                        </div>
                    </Card>

                    <!-- Total Revenue -->
                    <Card class="stripe-card border p-5">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Revenue</p>
                                <p class="mt-2 font-mono text-3xl font-semibold tabular-nums">{{ formatCurrency(stats.total_revenue) }}</p>
                            </div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-md bg-green-500/10">
                                <DollarSign class="h-5 w-5 text-green-600 dark:text-green-500" />
                            </div>
                        </div>
                    </Card>

                    <!-- Pending Amount -->
                    <Card class="stripe-card border p-5">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Pending</p>
                                <p class="mt-2 font-mono text-3xl font-semibold tabular-nums">{{ formatCurrency(stats.total_pending) }}</p>
                            </div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-md bg-yellow-500/10">
                                <Clock class="h-5 w-5 text-yellow-600 dark:text-yellow-500" />
                            </div>
                        </div>
                    </Card>

                    <!-- Completed Payments -->
                    <Card class="stripe-card border p-5">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Completed</p>
                                <p class="mt-2 font-mono text-3xl font-semibold tabular-nums">{{ stats.completed_payments }}</p>
                            </div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-md bg-green-500/10">
                                <CheckCircle class="h-5 w-5 text-green-600 dark:text-green-500" />
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Collections Overview & Recent Activity -->
                <div class="mt-6 grid gap-6 lg:grid-cols-12">
                    <!-- Status Breakdown -->
                    <Card class="border lg:col-span-4">
                        <div class="border-b px-5 py-4">
                            <h2 class="text-sm font-semibold">Status Overview</h2>
                        </div>
                        <div class="p-5">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-3">
                                        <div class="h-2 w-2 rounded-full bg-primary"></div>
                                        <span class="text-foreground">Active</span>
                                    </div>
                                    <span class="font-mono font-semibold tabular-nums">{{ stats.collections_by_status.active }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-3">
                                        <div class="h-2 w-2 rounded-full bg-green-500"></div>
                                        <span class="text-foreground">Completed</span>
                                    </div>
                                    <span class="font-mono font-semibold tabular-nums">{{ stats.collections_by_status.completed }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-3">
                                        <div class="h-2 w-2 rounded-full bg-yellow-500"></div>
                                        <span class="text-foreground">Pending</span>
                                    </div>
                                    <span class="font-mono font-semibold tabular-nums">{{ stats.collections_by_status.pending }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-3">
                                        <div class="h-2 w-2 rounded-full bg-yellow-500"></div>
                                        <span class="text-foreground">Partially Paid</span>
                                    </div>
                                    <span class="font-mono font-semibold tabular-nums">{{ stats.collections_by_status.partially_paid }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-3">
                                        <div class="h-2 w-2 rounded-full bg-red-500"></div>
                                        <span class="text-foreground">Expired</span>
                                    </div>
                                    <span class="font-mono font-semibold tabular-nums">{{ stats.collections_by_status.expired }}</span>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <!-- Recent Collections -->
                    <Card class="border lg:col-span-8">
                        <div class="flex items-center justify-between border-b px-5 py-4">
                            <h2 class="text-sm font-semibold">Recent Collections</h2>
                            <Link href="/admin/payment-collections">
                                <Button variant="ghost" size="sm" class="h-8 text-xs">
                                    View all
                                    <ArrowUpRight class="ml-1 h-3 w-3" />
                                </Button>
                            </Link>
                        </div>
                        <div class="divide-y">
                            <div
                                v-for="collection in recent_collections"
                                :key="collection.id"
                                class="group px-5 py-4 transition-colors hover:bg-muted/30"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-sm font-medium truncate">{{ collection.name }}</h3>
                                            <Badge :variant="statusVariant(collection.status)" class="badge-stripe shrink-0">
                                                {{ formatStatus(collection.status) }}
                                            </Badge>
                                        </div>
                                        <div class="mt-1 flex items-center gap-3 text-xs text-muted-foreground">
                                            <span>{{ collection.items_count }} {{ collection.items_count === 1 ? 'item' : 'items' }}</span>
                                            <span>•</span>
                                            <span class="font-mono font-medium tabular-nums">{{ formatCurrency(collection.total_amount) }}</span>
                                            <span>•</span>
                                            <span>{{ formatDate(collection.created_at) }}</span>
                                        </div>
                                    </div>
                                    <Link :href="`/admin/payment-collections/${collection.id}`">
                                        <Button variant="ghost" size="sm" class="h-8 gap-1.5 text-xs opacity-0 transition-opacity group-hover:opacity-100">
                                            <Eye class="h-3.5 w-3.5" />
                                            View
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                            <div v-if="recent_collections.length === 0" class="px-5 py-12 text-center">
                                <Package class="mx-auto h-12 w-12 text-muted-foreground/30" />
                                <p class="mt-3 text-sm font-medium">No collections yet</p>
                                <p class="mt-1 text-xs text-muted-foreground">Create your first collection to get started</p>
                                <Link href="/admin/payment-collections/create" class="mt-4 inline-block">
                                    <Button size="sm">Create Collection</Button>
                                </Link>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
