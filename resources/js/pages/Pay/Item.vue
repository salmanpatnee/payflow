<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { ArrowLeft } from 'lucide-vue-next'
import StripePaymentForm from '@/Components/Payment/StripePaymentForm.vue'

interface Item {
  id: number
  description: string
  amount: number
  status: string
  due_date?: string
  currency?: string
}

interface Collection {
  id: number
  uuid: string
  name: string
  description?: string
  expires_at?: string
}

interface Props {
  collection: Collection
  item: Item
  token: string
  stripeKey: string
}

const props = defineProps<Props>()

const formattedAmount = computed(() => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: props.item.currency?.toUpperCase() || 'USD',
  }).format(props.item.amount)
})

const formatDate = (date: string) => {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(new Date(date))
}

const paymentItem = computed(() => ({
  id: props.item.id,
  description: props.item.description,
  amount: props.item.amount,
  currency: props.item.currency || 'usd',
  status: props.item.status,
}))
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
          <div class="p-8">
            <h3 class="mb-6 text-lg font-semibold text-slate-900 dark:text-white">
              Payment Method
            </h3>

            <!-- Stripe Payment Form -->
            <StripePaymentForm
              v-if="item.status === 'pending' || item.status === 'failed'"
              :payment-item="paymentItem"
              :collection-uuid="collection.uuid"
              :stripe-key="stripeKey"
              :is-retry="item.status === 'failed'"
            />

            <!-- Completed Payment Message -->
            <div v-else-if="item.status === 'completed'" class="rounded-lg border-2 border-green-200 bg-green-50 p-6 text-center dark:border-green-800 dark:bg-green-950/30">
              <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/50">
                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
              </div>
              <h4 class="text-lg font-semibold text-green-900 dark:text-green-100">Payment Completed</h4>
              <p class="mt-2 text-sm text-green-700 dark:text-green-300">This payment has already been processed successfully.</p>
            </div>

            <!-- Processing Payment Message -->
            <div v-else-if="item.status === 'processing'" class="rounded-lg border-2 border-blue-200 bg-blue-50 p-6 text-center dark:border-blue-800 dark:bg-blue-950/30">
              <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/50">
                <svg class="h-6 w-6 animate-spin text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
              </div>
              <h4 class="text-lg font-semibold text-blue-900 dark:text-blue-100">Processing Payment</h4>
              <p class="mt-2 text-sm text-blue-700 dark:text-blue-300">Your payment is being processed. Please wait...</p>
            </div>
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
