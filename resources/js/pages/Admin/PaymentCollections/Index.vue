<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Search, Plus, Trash2, Eye, Edit, Filter } from 'lucide-vue-next';

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
            return 'secondary';
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
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(date);
};

const calculateTotal = (collection: PaymentCollection) => {
    if (!collection.items || collection.items.length === 0) {
        return 0;
    }
    return collection.items.reduce(
        (total, item) => total + item.price * item.quantity,
        0
    );
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
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Payment Collections" />

        <div class="flex min-h-screen flex-col">
            <!-- Header with refined styling -->
            <div class="sticky top-0 z-10 border-b border-border/40 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                <div class="flex h-20 items-center justify-between px-8">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">Payment Collections</h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Manage and organize your payment collections
                        </p>
                    </div>
                    <Link href="/admin/payment-collections/create">
                        <Button size="lg" class="gap-2 shadow-sm">
                            <Plus class="h-4 w-4" />
                            New Collection
                        </Button>
                    </Link>
                </div>

                <!-- Search and Filters -->
                <div class="border-t border-border/40 bg-muted/30 px-8 py-4">
                    <div class="flex items-center gap-4">
                        <div class="relative flex-1 max-w-md">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="searchQuery"
                                placeholder="Search collections..."
                                class="pl-10 bg-background"
                            />
                        </div>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" class="gap-2">
                                    <Filter class="h-4 w-4" />
                                    Filter
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
                    </div>
                </div>
            </div>

            <!-- Collections Table -->
            <div class="flex-1 px-8 py-6">
                <div class="rounded-lg border border-border/50 bg-card shadow-sm overflow-hidden">
                    <Table>
                        <TableHeader>
                            <TableRow class="border-b border-border/50 bg-muted/50">
                                <TableHead class="font-semibold">Collection</TableHead>
                                <TableHead class="font-semibold">Status</TableHead>
                                <TableHead class="font-semibold text-right">Items</TableHead>
                                <TableHead class="font-semibold">Created</TableHead>
                                <TableHead class="font-semibold text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="collection in collections.data"
                                :key="collection.id"
                                class="group transition-colors hover:bg-muted/30"
                            >
                                <TableCell class="py-4">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-foreground">
                                            {{ collection.name }}
                                        </span>
                                        <span
                                            v-if="collection.description"
                                            class="mt-1 text-sm text-muted-foreground line-clamp-1"
                                        >
                                            {{ collection.description }}
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="statusVariant(collection.status)">
                                        {{ collection.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    <span class="font-mono text-sm font-medium">
                                        {{ collection.items_count }}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <span class="text-sm text-muted-foreground">
                                        {{ formatDate(collection.created_at) }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="`/admin/payment-collections/${collection.id}`">
                                            <Button variant="ghost" size="sm" class="gap-2">
                                                <Eye class="h-4 w-4" />
                                                View
                                            </Button>
                                        </Link>
                                        <Link :href="`/admin/payment-collections/${collection.id}/edit`">
                                            <Button variant="ghost" size="sm" class="gap-2">
                                                <Edit class="h-4 w-4" />
                                                Edit
                                            </Button>
                                        </Link>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="gap-2 text-destructive hover:text-destructive"
                                            @click="openDeleteDialog(collection)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                            Delete
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination -->
                <div v-if="collections.meta.last_page > 1" class="mt-6 flex items-center justify-between">
                    <p class="text-sm text-muted-foreground">
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

<style scoped>
/* Smooth animations for table rows */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(4px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

tbody tr {
    animation: fadeIn 0.3s ease-out;
    animation-fill-mode: both;
}

tbody tr:nth-child(1) { animation-delay: 0.05s; }
tbody tr:nth-child(2) { animation-delay: 0.1s; }
tbody tr:nth-child(3) { animation-delay: 0.15s; }
tbody tr:nth-child(4) { animation-delay: 0.2s; }
tbody tr:nth-child(5) { animation-delay: 0.25s; }
</style>
