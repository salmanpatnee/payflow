<script setup lang="ts">
import { ref, computed, nextTick } from 'vue';
import { Plus, Trash2, GripVertical } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import InputError from '@/components/InputError.vue';

export interface PaymentItem {
    id?: number;
    name: string;
    description: string;
    price: number | string;
    quantity: number | string;
    type: string;
}

interface Props {
    modelValue: PaymentItem[];
    errors?: Record<string, string[]>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:modelValue': [value: PaymentItem[]];
}>();

const items = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const itemsContainer = ref<HTMLDivElement>();

const itemTypes = [
    { value: 'service', label: 'Service' },
    { value: 'product', label: 'Product' },
    { value: 'fee', label: 'Fee' },
];

const addItem = async () => {
    items.value = [
        ...items.value,
        {
            name: '',
            description: '',
            price: '',
            quantity: 1,
            type: 'service',
        },
    ];

    // Scroll to the newly added item header
    await nextTick();
    if (itemsContainer.value) {
        const lastItem = itemsContainer.value.lastElementChild as HTMLElement;
        if (lastItem) {
            // Scroll to the item header (first child) instead of the entire card
            const itemHeader = lastItem.firstElementChild as HTMLElement;
            if (itemHeader) {
                itemHeader.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            // Add a brief highlight effect to the entire card
            lastItem.classList.add('highlight-new');
            setTimeout(() => {
                lastItem.classList.remove('highlight-new');
            }, 2000);
        }
    }
};

const removeItem = (index: number) => {
    items.value = items.value.filter((_, i) => i !== index);
};

const getError = (field: string, index: number) => {
    if (!props.errors) return undefined;
    const errorKey = `items.${index}.${field}`;
    return props.errors[errorKey]?.[0];
};

const calculateItemTotal = (item: PaymentItem) => {
    const price = typeof item.price === 'string' ? parseFloat(item.price) || 0 : item.price;
    const quantity = typeof item.quantity === 'string' ? parseInt(item.quantity) || 0 : item.quantity;
    return price * quantity;
};

const calculateGrandTotal = computed(() => {
    return items.value.reduce((total, item) => {
        return total + calculateItemTotal(item);
    }, 0);
});

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-foreground">Payment Items</h3>
                <p class="text-xs text-muted-foreground">
                    Add items to this collection
                </p>
            </div>
            <Button type="button" size="sm" @click="addItem" class="gap-1.5 h-8 text-xs">
                <Plus class="h-3.5 w-3.5" />
                Add Item
            </Button>
        </div>

        <div v-if="items.length === 0" class="rounded-lg border-2 border-dashed border-primary/30 bg-primary/5 p-8 text-center">
            <div class="mx-auto max-w-sm">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 mb-3">
                    <Plus class="h-6 w-6 text-primary" />
                </div>
                <h3 class="text-sm font-bold text-foreground">No items yet</h3>
                <p class="mt-1 text-xs text-muted-foreground">
                    Get started by adding your first payment item to this collection.
                </p>
                <Button
                    type="button"
                    size="sm"
                    @click="addItem"
                    class="mt-4 gap-1.5"
                >
                    <Plus class="h-3.5 w-3.5" />
                    Add First Item
                </Button>
            </div>
        </div>

        <div v-else ref="itemsContainer" class="space-y-3">
            <div
                v-for="(item, index) in items"
                :key="index"
                class="item-card group relative rounded-lg border border-border bg-card p-4 shadow-sm transition-all hover:border-primary/30 hover:shadow-md"
            >
                <!-- Item Header -->
                <div class="mb-4 flex items-start justify-between">
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-md bg-primary font-mono text-sm font-bold text-primary-foreground">
                            {{ index + 1 }}
                        </div>
                        <div>
                            <span class="block text-xs font-bold uppercase tracking-wide text-foreground">
                                Item #{{ index + 1 }}
                            </span>
                            <span class="block text-xs text-muted-foreground capitalize">
                                {{ item.type || 'Service' }}
                            </span>
                        </div>
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="removeItem(index)"
                        class="gap-1.5 h-7 text-xs text-destructive opacity-0 group-hover:opacity-100 transition-opacity hover:text-destructive hover:bg-destructive/10"
                    >
                        <Trash2 class="h-3.5 w-3.5" />
                        Remove
                    </Button>
                </div>

                <!-- Item Fields -->
                <div class="grid gap-3 md:grid-cols-2">
                    <!-- Name -->
                    <div class="space-y-1.5">
                        <Label :for="`item-name-${index}`" class="text-xs font-medium">
                            Item Name <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            :id="`item-name-${index}`"
                            v-model="item.name"
                            :name="`items[${index}][name]`"
                            placeholder="e.g., Web Development Service"
                            class="input-stripe h-9 text-sm"
                        />
                        <InputError :message="getError('name', index)" />
                    </div>

                    <!-- Type -->
                    <div class="space-y-1.5">
                        <Label :for="`item-type-${index}`" class="text-xs font-medium">
                            Type <span class="text-destructive">*</span>
                        </Label>
                        <Select v-model="item.type" :name="`items[${index}][type]`">
                            <SelectTrigger :id="`item-type-${index}`" class="h-9 text-sm">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="type in itemTypes"
                                    :key="type.value"
                                    :value="type.value"
                                >
                                    {{ type.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="getError('type', index)" />
                    </div>

                    <!-- Description -->
                    <div class="space-y-1.5 md:col-span-2">
                        <Label :for="`item-description-${index}`" class="text-xs font-medium">
                            Description
                        </Label>
                        <Input
                            :id="`item-description-${index}`"
                            v-model="item.description"
                            :name="`items[${index}][description]`"
                            placeholder="Brief description of this item"
                            class="input-stripe h-9 text-sm"
                        />
                        <InputError :message="getError('description', index)" />
                    </div>

                    <!-- Price -->
                    <div class="space-y-1.5">
                        <Label :for="`item-price-${index}`" class="text-xs font-medium">
                            Price <span class="text-destructive">*</span>
                        </Label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-medium text-muted-foreground">
                                $
                            </span>
                            <Input
                                :id="`item-price-${index}`"
                                v-model="item.price"
                                :name="`items[${index}][price]`"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                class="input-stripe h-9 pl-7 font-mono text-sm"
                            />
                        </div>
                        <InputError :message="getError('price', index)" />
                    </div>

                    <!-- Quantity -->
                    <div class="space-y-1.5">
                        <Label :for="`item-quantity-${index}`" class="text-xs font-medium">
                            Quantity <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            :id="`item-quantity-${index}`"
                            v-model="item.quantity"
                            :name="`items[${index}][quantity]`"
                            type="number"
                            min="1"
                            placeholder="1"
                            class="input-stripe h-9 font-mono text-sm"
                        />
                        <InputError :message="getError('quantity', index)" />
                    </div>
                </div>

                <!-- Item Subtotal -->
                <div class="mt-3 flex justify-end border-t border-border pt-3">
                    <div class="text-right">
                        <span class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Subtotal:</span>
                        <span class="ml-3 font-mono text-lg font-bold text-foreground">
                            {{ formatCurrency(calculateItemTotal(item)) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Grand Total -->
            <div class="rounded-lg border-2 border-primary/20 bg-primary/5 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wide text-foreground">Collection Total</h4>
                        <p class="text-xs text-muted-foreground">
                            {{ items.length }} {{ items.length === 1 ? 'item' : 'items' }}
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="font-mono text-3xl font-bold text-primary">
                            {{ formatCurrency(calculateGrandTotal) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Another Item Button -->
            <div class="flex justify-center pt-1">
                <Button type="button" size="sm" @click="addItem" class="gap-1.5 h-9">
                    <Plus class="h-4 w-4" />
                    Add Another Item
                </Button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Smooth entrance animation for items */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Highlight new item */
@keyframes highlightPulse {
    0%, 100% {
        box-shadow: 0 0 0 0 hsl(243 75% 59% / 0.4);
    }
    50% {
        box-shadow: 0 0 0 8px hsl(243 75% 59% / 0);
    }
}

.item-card {
    animation: slideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.highlight-new {
    animation: highlightPulse 1s ease-out 2;
    border-color: hsl(243 75% 59%);
}
</style>
