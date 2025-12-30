<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { ArrowLeft } from 'lucide-vue-next'

interface Item {
  id: number
  description: string
  amount: number
  status: string
  due_date?: string
}

interface Collection {
  id: number
  name: string
  description?: string
  expires_at?: string
}

interface Props {
  collection: Collection
  item: Item
  token: string
}

const props = defineProps<Props>()

const formattedAmount = computed(() => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(props.item.amount)
})

const formatDate = (date: string) => {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(new Date(date))
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-950 dark:to-slate-900">
    <Head title="Payment" />

    <div class="flex items-center justify-center px-4 py-8 sm:py-12">
      <div class="w-full max-w-2xl">
        <!-- Back Button -->
        <Link
          :href="`/pay/${token}`"
          class="mb-8 inline-flex items-center gap-2 text-blue-600 transition-colors hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
        >
          <ArrowLeft class="h-4 w-4" />
          Back to All Items
        </Link>

        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-slate-900 dark:text-white sm:text-4xl">
            {{ collection.name }}
          </h1>
          <p v-if="collection.description" class="mt-2 text-slate-600 dark:text-slate-400">
            {{ collection.description }}
          </p>
        </div>

        <!-- Payment Card -->
        <div class="rounded-lg border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-800">
          <!-- Item Details -->
          <div class="border-b border-slate-200 p-8 dark:border-slate-700">
            <div class="mb-6">
              <h2 class="text-2xl font-bold text-slate-900 dark:text-white">
                {{ item.description }}
              </h2>
              <p v-if="item.due_date" class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                Due: {{ formatDate(item.due_date) }}
              </p>
            </div>

            <!-- Amount Display -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6 dark:from-blue-950/30 dark:to-indigo-950/30">
              <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Amount Due</p>
              <p class="mt-2 text-4xl font-bold text-blue-600 dark:text-blue-400">
                {{ formattedAmount }}
              </p>
            </div>
          </div>

          <!-- Payment Method Section -->
          <div class="border-b border-slate-200 p-8 dark:border-slate-700">
            <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">
              Payment Method
            </h3>
            <p class="text-slate-600 dark:text-slate-400">
              Select your preferred payment method to complete this payment.
            </p>
          </div>

          <!-- Action Button -->
          <div class="p-8">
            <button
              disabled
              class="w-full rounded-lg bg-blue-600 px-6 py-4 font-semibold text-white opacity-50 cursor-not-allowed hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 transition-colors"
            >
              Pay {{ formattedAmount }}
            </button>
            <p class="mt-4 text-center text-sm text-slate-500 dark:text-slate-400">
              Stripe payment integration coming soon. You can return to the payment list to view all items.
            </p>
          </div>

          <!-- Info Box -->
          <div class="border-t border-slate-200 bg-blue-50 px-8 py-6 dark:border-slate-700 dark:bg-blue-950/20">
            <p class="text-sm text-blue-900 dark:text-blue-300">
              <span class="font-semibold">Note:</span> After completing this payment, you'll be redirected back to the payment list to continue with any remaining items.
            </p>
          </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center text-sm text-slate-600 dark:text-slate-400">
          <p>Questions? Contact the payment administrator for assistance.</p>
        </div>
      </div>
    </div>
  </div>
</template>
