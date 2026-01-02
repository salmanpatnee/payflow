<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { ArrowLeft } from 'lucide-vue-next'
import StripePaymentForm from '@/Components/Payment/StripePaymentForm.vue'

interface Item {
  id: number
  name: string
  description?: string
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
  <div class="min-h-screen bg-[#f6f9fc]">
    <Head title="Payment" />

    <!-- Stripe-branded Header Bar -->
    <!-- <div class="border-b border-gray-200 bg-white shadow-sm">
      <div class="mx-auto max-w-2xl px-4 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <svg viewBox="0 0 60 25" xmlns="http://www.w3.org/2000/svg" class="h-7">
              <path fill="#635BFF" d="M59.64 14.28h-8.06c.19 1.93 1.6 2.55 3.2 2.55 1.64 0 2.96-.37 4.05-.95v3.32a8.33 8.33 0 0 1-4.56 1.1c-4.01 0-6.83-2.5-6.83-7.48 0-4.19 2.39-7.52 6.3-7.52 3.92 0 5.96 3.28 5.96 7.5 0 .4-.04 1.26-.06 1.48zm-5.92-5.62c-1.03 0-2.17.73-2.17 2.58h4.25c0-1.85-1.07-2.58-2.08-2.58zM40.95 20.3c-1.44 0-2.32-.6-2.9-1.04l-.02 4.63-4.12.87V5.57h3.76l.08 1.02a4.7 4.7 0 0 1 3.23-1.29c2.9 0 5.62 2.6 5.62 7.4 0 5.23-2.7 7.6-5.65 7.6zM40 8.95c-.95 0-1.54.34-1.97.81l.02 6.12c.4.44.98.78 1.95.78 1.52 0 2.54-1.65 2.54-3.87 0-2.15-1.04-3.84-2.54-3.84zM28.24 5.57h4.13v14.44h-4.13V5.57zm0-4.7L32.37 0v3.36l-4.13.88V.88zm-4.32 9.35v9.79H19.8V5.57h3.7l.12 1.22c1-1.77 3.07-1.41 3.62-1.22v3.79c-.52-.17-2.29-.43-3.32.86zm-8.55 4.72c0 2.43 2.6 1.68 3.12 1.46v3.36c-.55.3-1.54.54-2.89.54a4.15 4.15 0 0 1-4.27-4.24l.01-13.17 4.02-.86v3.54h3.14V9.1h-3.13v5.85zm-4.91.7c0 2.97-2.31 4.66-5.73 4.66a11.2 11.2 0 0 1-4.46-.93v-3.93c1.38.75 3.1 1.31 4.46 1.31.92 0 1.53-.24 1.53-1C6.26 13.77 0 14.51 0 9.95 0 7.04 2.28 5.3 5.62 5.3c1.36 0 2.72.2 4.09.75v3.88a9.23 9.23 0 0 0-4.1-1.06c-.86 0-1.44.25-1.44.93 0 1.85 6.29.97 6.29 5.88z"/>
            </svg>
            <div class="h-6 w-px bg-gray-300"></div>
            <span class="text-sm font-medium text-gray-600">Secure Checkout</span>
          </div>
          <div class="flex items-center gap-2 text-xs text-gray-500">
            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
            <span class="hidden sm:inline">Secure</span>
          </div>
        </div>
      </div>
    </div> -->

    <div class="flex items-start justify-center px-4 py-8 sm:py-12">
      <div class="w-full max-w-2xl">
        <!-- Back Button -->
        <Link
          :href="`/pay/${token}`"
          class="mb-8 inline-flex items-center gap-2 text-[#635BFF] transition-colors hover:text-[#0a2540]"
        >
          <ArrowLeft class="h-4 w-4" />
          Back to All Items
        </Link>

        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-gray-900 sm:text-4xl">
            {{ collection.name }}
          </h1>
          <p v-if="collection.description" class="mt-2 text-gray-600">
            {{ collection.description }}
          </p>
        </div>

        <!-- Payment Card -->
        <div class="rounded-lg border border-gray-200 bg-white shadow-md overflow-hidden">
          <!-- Stripe Header Badge -->
          <div class="flex items-center justify-between border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white px-6 py-4">
            <div class="flex items-center gap-2">
              <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
              </svg>
              <span class="text-sm font-medium text-gray-700">Secure payment powered by</span>
              <svg viewBox="0 0 60 25" xmlns="http://www.w3.org/2000/svg" class="h-5">
                <path fill="#635BFF" d="M59.64 14.28h-8.06c.19 1.93 1.6 2.55 3.2 2.55 1.64 0 2.96-.37 4.05-.95v3.32a8.33 8.33 0 0 1-4.56 1.1c-4.01 0-6.83-2.5-6.83-7.48 0-4.19 2.39-7.52 6.3-7.52 3.92 0 5.96 3.28 5.96 7.5 0 .4-.04 1.26-.06 1.48zm-5.92-5.62c-1.03 0-2.17.73-2.17 2.58h4.25c0-1.85-1.07-2.58-2.08-2.58zM40.95 20.3c-1.44 0-2.32-.6-2.9-1.04l-.02 4.63-4.12.87V5.57h3.76l.08 1.02a4.7 4.7 0 0 1 3.23-1.29c2.9 0 5.62 2.6 5.62 7.4 0 5.23-2.7 7.6-5.65 7.6zM40 8.95c-.95 0-1.54.34-1.97.81l.02 6.12c.4.44.98.78 1.95.78 1.52 0 2.54-1.65 2.54-3.87 0-2.15-1.04-3.84-2.54-3.84zM28.24 5.57h4.13v14.44h-4.13V5.57zm0-4.7L32.37 0v3.36l-4.13.88V.88zm-4.32 9.35v9.79H19.8V5.57h3.7l.12 1.22c1-1.77 3.07-1.41 3.62-1.22v3.79c-.52-.17-2.29-.43-3.32.86zm-8.55 4.72c0 2.43 2.6 1.68 3.12 1.46v3.36c-.55.3-1.54.54-2.89.54a4.15 4.15 0 0 1-4.27-4.24l.01-13.17 4.02-.86v3.54h3.14V9.1h-3.13v5.85zm-4.91.7c0 2.97-2.31 4.66-5.73 4.66a11.2 11.2 0 0 1-4.46-.93v-3.93c1.38.75 3.1 1.31 4.46 1.31.92 0 1.53-.24 1.53-1C6.26 13.77 0 14.51 0 9.95 0 7.04 2.28 5.3 5.62 5.3c1.36 0 2.72.2 4.09.75v3.88a9.23 9.23 0 0 0-4.1-1.06c-.86 0-1.44.25-1.44.93 0 1.85 6.29.97 6.29 5.88z"/>
              </svg>
            </div>
            <div class="flex items-center">
              <img src="/images/cards.webp" alt="Accepted cards: Visa, Mastercard, American Express, Discover" class="h-6" />
            </div>
          </div>

          <!-- Item Details -->
          <div class="border-b border-gray-200 p-8">
            <div class="mb-6">
              <h2 class="text-xl font-semibold text-gray-900">
                {{ item.name }}
              </h2>
              <p v-if="item.description" class="mt-2 text-sm text-gray-600">
                {{ item.description }}
              </p>
              <p v-if="item.due_date" class="mt-1 text-sm text-gray-600">
                Due: {{ formatDate(item.due_date) }}
              </p>
            </div>

            <!-- Amount Display -->
            <div class="rounded-lg border-2 border-[hsl(243_75%_59%_/_0.2)] bg-[hsl(243_75%_59%_/_0.05)] p-6">
              <p class="text-sm font-medium text-gray-700">Amount due</p>
              <p class="mt-2 text-4xl font-bold tabular-nums text-[hsl(243_75%_59%)]">
                {{ formattedAmount }}
              </p>
            </div>
          </div>

          <!-- Payment Method Section -->
          <div class="p-8">
            <h3 class="mb-6 text-base font-semibold text-gray-900">
              Pay with card
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
            <div v-else-if="item.status === 'completed'" class="rounded-lg border-2 border-green-200 bg-green-50 p-6 text-center">
              <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
              </div>
              <h4 class="text-lg font-semibold text-green-900">Payment Completed</h4>
              <p class="mt-2 text-sm text-green-700">This payment has already been processed successfully.</p>
            </div>

            <!-- Processing Payment Message -->
            <div v-else-if="item.status === 'processing'" class="rounded-lg border-2 border-[#635BFF]/30 bg-[#635BFF]/5 p-6 text-center">
              <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-[#635BFF]/10">
                <svg class="h-6 w-6 animate-spin text-[#635BFF]" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
              </div>
              <h4 class="text-lg font-semibold text-gray-900">Processing Payment</h4>
              <p class="mt-2 text-sm text-gray-600">Your payment is being processed. Please wait...</p>
            </div>
          </div>

          <!-- Info Box -->
          <div class="border-t border-gray-200 bg-gray-50 px-8 py-5">
            <div class="flex items-start gap-3">
              <svg class="w-5 h-5 text-gray-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <p class="text-sm text-gray-600">
                After completing this payment, you'll be redirected back to the payment list to continue with any remaining items.
              </p>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 space-y-3">
          <div class="flex items-center justify-center gap-6 text-xs text-gray-500">
            <div class="flex items-center gap-1.5">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
              </svg>
              <span>SSL Encrypted</span>
            </div>
            <span class="text-gray-300">•</span>
            <div class="flex items-center gap-1.5">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
              </svg>
              <span>PCI DSS Compliant</span>
            </div>
            <span class="text-gray-300">•</span>
            <div class="flex items-center gap-1.5">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
              </svg>
              <span>Trusted by millions</span>
            </div>
          </div>
          <p class="text-center text-xs text-gray-500">
            Questions? Contact the payment administrator for assistance.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
