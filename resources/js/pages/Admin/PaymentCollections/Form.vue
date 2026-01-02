<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Save, X } from 'lucide-vue-next';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card } from '@/components/ui/card';
import InputError from '@/components/InputError.vue';
import RepeatableItems, { type PaymentItem } from '@/Components/Admin/RepeatableItems.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface PaymentCollection {
    id?: number;
    uuid?: string;
    name: string;
    description: string;
    status?: string;
    items: PaymentItem[];
}

interface Props {
    collection?: PaymentCollection;
    errors?: Record<string, string[]>;
}

const props = defineProps<Props>();

const isEditing = computed(() => !!props.collection?.id);

const form = ref<PaymentCollection>({
    name: props.collection?.name || '',
    description: props.collection?.description || '',
    items: props.collection?.items || [],
});

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Payment Collections',
        href: '/admin/payment-collections',
    },
    {
        title: isEditing.value ? 'Edit Collection' : 'New Collection',
        href: '#',
    },
];

const processing = ref(false);

const handleSubmit = () => {
    processing.value = true;

    const url = isEditing.value
        ? `/admin/payment-collections/${props.collection?.id}`
        : '/admin/payment-collections';

    const method = isEditing.value ? 'put' : 'post';

    router[method](url, form.value, {
        onFinish: () => {
            processing.value = false;
        },
    });
};

const cancel = () => {
    router.visit('/admin/payment-collections');
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="isEditing ? 'Edit Payment Collection' : 'Create Payment Collection'" />

        <div class="min-h-screen bg-muted/20">
            <!-- Header -->
            <div class="border-b border-border/40 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                <div class="flex h-20 items-center justify-between px-8">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">
                            {{ isEditing ? 'Edit Collection' : 'New Collection' }}
                        </h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{
                                isEditing
                                    ? 'Update the details of your payment collection'
                                    : 'Create a new payment collection with items'
                            }}
                        </p>
                    </div>
                    <Link href="/admin/payment-collections">
                        <Button variant="ghost" size="sm" class="gap-2">
                            <ArrowLeft class="h-4 w-4" />
                            Back
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Form Content -->
            <div class="mx-auto max-w-5xl px-8 py-8">
                <form @submit.prevent="handleSubmit" class="space-y-8">
                    <!-- Collection Details Card -->
                    <Card class="overflow-hidden border-border/50 shadow-sm pt-0">
                        <div class="border-b border-border/50 bg-muted/30 px-6 py-4 border-b-2 border-primary/20 bg-primary hover:bg-primary transition-colors ">
                            <h2 class="font-semibold text-primary-foreground">Collection Details</h2>
                            <p class="mt-1 text-sm text-primary-foreground">
                                Basic information about this payment collection
                            </p>
                        </div>

                        <div class="p-6 space-y-6 py-0">
                            <!-- Collection Name -->
                            <div class="space-y-2">
                                <Label for="name">
                                    Collection Name <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    name="name"
                                    placeholder="e.g., Q1 Marketing Package"
                                    class="transition-all focus:ring-2 focus:ring-ring"
                                    required
                                />
                                <InputError v-if="errors?.name" :message="errors.name[0]" />
                                <p class="text-sm text-muted-foreground">
                                    A clear, descriptive name for this collection
                                </p>
                            </div>

                            <!-- Collection Description -->
                            <div class="space-y-2">
                                <Label for="description">Description</Label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    name="description"
                                    rows="4"
                                    placeholder="Describe what this collection is for..."
                                    class="flex min-h-[120px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                ></textarea>
                                <InputError
                                    v-if="errors?.description"
                                    :message="errors.description[0]"
                                />
                                <p class="text-sm text-muted-foreground">
                                    Optional: Provide additional context about this collection
                                </p>
                            </div>
                        </div>
                    </Card>

                    <!-- Payment Items Card -->
                    <Card class="overflow-hidden border-border/50 shadow-sm">
                        <div class="p-6 py-0">
                            <RepeatableItems
                                v-model="form.items"
                                :errors="errors"
                            />
                        </div>
                    </Card>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-between rounded-lg border border-border/50 bg-card p-6 shadow-sm">
                        <div class="text-sm text-muted-foreground">
                            <span class="font-medium text-foreground">Required fields</span>
                            are marked with
                            <span class="text-destructive">*</span>
                        </div>
                        <div class="flex gap-3">
                            <Button
                                type="button"
                                variant="outline"
                                @click="cancel"
                                :disabled="processing"
                                class="gap-2"
                            >
                                <X class="h-4 w-4" />
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                :disabled="processing || form.items.length === 0"
                                class="gap-2 shadow-sm"
                            >
                                <Save class="h-4 w-4" />
                                {{ processing ? 'Saving...' : isEditing ? 'Update Collection' : 'Create Collection' }}
                            </Button>
                        </div>
                    </div>
                </form>
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

form > * {
    animation: fadeInUp 0.4s ease-out;
    animation-fill-mode: both;
}

form > *:nth-child(1) { animation-delay: 0.1s; }
form > *:nth-child(2) { animation-delay: 0.2s; }
form > *:nth-child(3) { animation-delay: 0.3s; }
</style>
