<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Search, Plus, Trash2, Eye, Edit, Filter, X } from 'lucide-vue-next';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout.vue';
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
    created_at: string;
    updated_at: string;
    items_count: number;
    items?: PaymentItem[];
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    collections: {
        data: PaymentCollection[];
        links: {
            first: string;
            last: string;
            prev: string | null;
            next: string | null;
        };
        meta: {
            current_page: number;
            from: number;
            last_page: number;
            links: PaginationLink[];
            path: string;
            per_page: number;
            to: number;
            total: number;
        };
    };
}

const props = defineProps<Props>();

const searchQuery = ref('');
const statusFilter = ref<string>('all');
const deleteDialogOpen = ref(false);
const collectionToDelete = ref<PaymentCollection | null>(null);

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Payment Collections',
        href: '/admin/payment-collections',
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

const formatDate = (dateString: string | null | undefined) => {
    if (!dateString) {
        return 'N/A';
    }
    const date = new Date(dateString);
    if (isNaN(date.getTime())) {
        return 'Invalid date';
    }
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(date);
};

const openDeleteDialog = (collection: PaymentCollection) => {
    collectionToDelete.value = collection;
    deleteDialogOpen.value = true;
};

const confirmDelete = () => {
    if (collectionToDelete.value) {
        router.delete(`/admin/payment-collections/${collectionToDelete.value.id}`, {
            onSuccess: () => {
                deleteDialogOpen.value = false;
                collectionToDelete.value = null;
            },
        });
    }
};

let searchTimeout: number | null = null;

watch([searchQuery, statusFilter], ([newSearch, newStatus]) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = window.setTimeout(() => {
        const params: Record<string, string> = {};

        if (newSearch) {
            params.search = newSearch;
        }

        if (newStatus && newStatus !== 'all') {
            params.status = newStatus;
        }

        router.get('/admin/payment-collections', params, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 300);
});

const clearFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'all';
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
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Payment Collections" />

        <div class="flex min-h-screen flex-col">
            <!-- Header -->
            <div class="border-b border-border bg-background">
                <div class="flex h-16 items-center justify-between px-8">
                    <h1 class="text-xl font-semibold">Payment Collections</h1>
                    <Link href="/admin/payment-collections/create">
                        <Button size="sm" class="gap-2">
                            <Plus class="h-4 w-4" />
                            New Collection
                        </Button>
                    </Link>
                </div>

                <!-- Search and Filters -->
                <div class="border-t bg-muted/30 px-8 py-3">
                    <div class="flex items-center gap-3">
                        <div class="relative flex-1 max-w-md">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="searchQuery"
                                placeholder="Search collections..."
                                class="input-stripe h-9 pl-9 text-sm"
                            />
                        </div>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" size="sm" class="h-9 gap-2">
                                    <Filter class="h-4 w-4" />
                                    Status
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem @click="statusFilter = 'all'">
                                    All Collections
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="statusFilter = 'active'">
                                    Active Only
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="statusFilter = 'completed'">
                                    Completed Only
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="statusFilter = 'expired'">
                                    Expired Only
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                        <Button
                            v-if="searchQuery || statusFilter !== 'all'"
                            variant="ghost"
                            size="sm"
                            class="h-9 gap-2"
                            @click="clearFilters"
                        >
                            <X class="h-4 w-4" />
                            Clear
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Collections Table -->
            <div class="flex-1 px-8 py-6">
                <div class="overflow-hidden rounded-lg border">
                    <Table class="stripe-table">
                        <TableHeader>
                            <TableRow class="border-b-2 border-primary/20 bg-primary hover:bg-primary">
                                <TableHead class="h-12 px-4 text-sm font-bold uppercase tracking-wider text-primary-foreground">Collection</TableHead>
                                <TableHead class="h-12 px-4 text-sm font-bold uppercase tracking-wider text-primary-foreground">Status</TableHead>
                                <TableHead class="h-12 px-4 text-sm font-bold uppercase tracking-wider text-primary-foreground text-right">Items</TableHead>
                                <TableHead class="h-12 px-4 text-sm font-bold uppercase tracking-wider text-primary-foreground">Created</TableHead>
                                <TableHead class="h-12 px-4 text-sm font-bold uppercase tracking-wider text-primary-foreground text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="collection in collections.data"
                                :key="collection.id"
                                class="group"
                            >
                                <TableCell class="px-4 py-3">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-sm font-medium">
                                            {{ collection.name }}
                                        </span>
                                        <span
                                            v-if="collection.description"
                                            class="text-xs text-muted-foreground line-clamp-1"
                                        >
                                            {{ collection.description }}
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell class="px-4 py-3">
                                    <Badge :variant="statusVariant(collection.status)" class="badge-stripe">
                                        {{ formatStatus(collection.status) }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="px-4 py-3 text-right">
                                    <span class="font-mono text-sm font-medium tabular-nums">
                                        {{ collection.items_count }}
                                    </span>
                                </TableCell>
                                <TableCell class="px-4 py-3">
                                    <span class="text-sm text-muted-foreground">
                                        {{ formatDate(collection.created_at) }}
                                    </span>
                                </TableCell>
                                <TableCell class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <Link :href="`/admin/payment-collections/${collection.id}`">
                                            <Button variant="ghost" size="sm" class="h-8 gap-1.5 px-2 text-xs">
                                                <Eye class="h-3.5 w-3.5" />
                                                View
                                            </Button>
                                        </Link>
                                        <Link :href="`/admin/payment-collections/${collection.id}/edit`">
                                            <Button variant="ghost" size="sm" class="h-8 gap-1.5 px-2 text-xs">
                                                <Edit class="h-3.5 w-3.5" />
                                                Edit
                                            </Button>
                                        </Link>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-8 gap-1.5 px-2 text-xs text-destructive hover:text-destructive hover:bg-destructive/10"
                                            @click="openDeleteDialog(collection)"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                            Delete
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination -->
                <div v-if="collections.meta.last_page > 1" class="mt-4 flex items-center justify-between text-sm">
                    <p class="text-muted-foreground">
                        Showing {{ collections.meta.from }} to {{ collections.meta.to }} of {{ collections.meta.total }} collections
                    </p>
                    <div class="flex gap-2">
                        <Link
                            v-if="collections.links.prev"
                            :href="collections.links.prev"
                        >
                            <Button variant="outline" size="sm">Previous</Button>
                        </Link>
                        <Link
                            v-if="collections.links.next"
                            :href="collections.links.next"
                        >
                            <Button variant="outline" size="sm">Next</Button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Payment Collection</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete "{{ collectionToDelete?.name }}"?
                        This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialogOpen = false">
                        Cancel
                    </Button>
                    <Button variant="destructive" @click="confirmDelete">
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
