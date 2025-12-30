<script setup lang="ts">
import { ref, computed } from 'vue';
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

const itemTypes = [
    { value: 'service', label: 'Service' },
    { value: 'product', label: 'Product' },
    { value: 'fee', label: 'Fee' },
];

const addItem = () => {
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
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold">Payment Items</h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    Add items to this collection
                </p>
            </div>
            <Button type="button" variant="outline" size="sm" @click="addItem" class="gap-2">
                <Plus class="h-4 w-4" />
                Add Item
            </Button>
        </div>

        <div v-if="items.length === 0" class="rounded-lg border-2 border-dashed border-border/60 bg-muted/20 p-12 text-center">
            <div class="mx-auto max-w-sm">
                <h3 class="font-semibold text-foreground">No items yet</h3>
                <p class="mt-2 text-sm text-muted-foreground">
                    Get started by adding your first payment item to this collection.
                </p>
                <Button
                    type="button"
                    variant="default"
                    size="sm"
                    @click="addItem"
                    class="mt-4 gap-2"
                >
                    <Plus class="h-4 w-4" />
                    Add First Item
                </Button>
            </div>
        </div>

        <div v-else class="space-y-4">
            <div
                v-for="(item, index) in items"
                :key="index"
                class="group relative rounded-lg border border-border/50 bg-card p-6 shadow-sm transition-all hover:shadow-md hover:border-border"
            >
                <!-- Item Header -->
                <div class="mb-4 flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-md bg-muted text-sm font-semibold text-muted-foreground">
                            {{ index + 1 }}
                        </div>
                        <span class="font-medium text-sm text-muted-foreground">
                            Item #{{ index + 1 }}
                        </span>
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="removeItem(index)"
                        class="gap-2 text-destructive opacity-0 group-hover:opacity-100 transition-opacity hover:text-destructive"
                    >
                        <Trash2 class="h-4 w-4" />
                        Remove
                    </Button>
                </div>

                <!-- Item Fields -->
                <div class="grid gap-6 md:grid-cols-2">
                    <!-- Name -->
                    <div class="space-y-2">
                        <Label :for="`item-name-${index}`">
                            Item Name <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            :id="`item-name-${index}`"
                            v-model="item.name"
                            :name="`items[${index}][name]`"
                            placeholder="e.g., Web Development Service"
                            class="transition-all focus:ring-2 focus:ring-ring"
                        />
                        <InputError :message="getError('name', index)" />
                    </div>

                    <!-- Type -->
                    <div class="space-y-2">
                        <Label :for="`item-type-${index}`">
                            Type <span class="text-destructive">*</span>
                        </Label>
                        <Select v-model="item.type" :name="`items[${index}][type]`">
                            <SelectTrigger :id="`item-type-${index}`">
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
                    <div class="space-y-2 md:col-span-2">
                        <Label :for="`item-description-${index}`">
                            Description
                        </Label>
                        <Input
                            :id="`item-description-${index}`"
                            v-model="item.description"
                            :name="`items[${index}][description]`"
                            placeholder="Brief description of this item"
                        />
                        <InputError :message="getError('description', index)" />
                    </div>

                    <!-- Price -->
                    <div class="space-y-2">
                        <Label :for="`item-price-${index}`">
                            Price <span class="text-destructive">*</span>
                        </Label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">
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
                                class="pl-7 font-mono transition-all focus:ring-2 focus:ring-ring"
                            />
                        </div>
                        <InputError :message="getError('price', index)" />
                    </div>

                    <!-- Quantity -->
                    <div class="space-y-2">
                        <Label :for="`item-quantity-${index}`">
                            Quantity <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            :id="`item-quantity-${index}`"
                            v-model="item.quantity"
                            :name="`items[${index}][quantity]`"
                            type="number"
                            min="1"
                            placeholder="1"
                            class="font-mono transition-all focus:ring-2 focus:ring-ring"
                        />
                        <InputError :message="getError('quantity', index)" />
                    </div>
                </div>

                <!-- Item Subtotal -->
                <div class="mt-4 flex justify-end border-t border-border/50 pt-4">
                    <div class="text-right">
                        <span class="text-sm text-muted-foreground">Subtotal:</span>
                        <span class="ml-3 font-mono text-lg font-semibold text-foreground">
                            {{ formatCurrency(calculateItemTotal(item)) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Grand Total -->
            <div class="rounded-lg border border-border/50 bg-muted/30 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-semibold text-foreground">Collection Total</h4>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ items.length }} {{ items.length === 1 ? 'item' : 'items' }}
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="font-mono text-3xl font-bold text-foreground">
                            {{ formatCurrency(calculateGrandTotal) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Smooth entrance animation for items */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.group {
    animation: slideIn 0.3s ease-out;
}
</style>
