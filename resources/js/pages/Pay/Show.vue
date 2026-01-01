<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Check, Clock, AlertCircle } from 'lucide-vue-next'

interface PaymentItem {
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
  items: PaymentItem[]
  total_amount: number
}

interface Props {
  collection: Collection
  token: string
}

const props = defineProps<Props>()

const formattedAmount = computed(() => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(props.collection.total_amount)
})

const paidItemsCount = computed(() => {
  return props.collection.items.filter(item => item.status === 'paid').length
})

const progressPercentage = computed(() => {
  if (props.collection.items.length === 0) return 0
  return Math.round((paidItemsCount.value / props.collection.items.length) * 100)
})

const formatDate = (date: string) => {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(new Date(date))
}

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(amount)
}
</script>

<template>
  <div class="min-h-screen bg-[#f6f9fc]">
    <Head title="Payment Details" />

    <!-- Stripe-branded Header Bar -->
    <div class="border-b border-gray-200 bg-white shadow-sm">
      <div class="mx-auto max-w-4xl px-4 py-4">
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
    </div>

    <div class="flex items-start justify-center px-4 py-8 sm:py-12">
      <div class="w-full max-w-4xl">

        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-gray-900 sm:text-4xl">
            {{ collection.name }}
          </h1>
          <p v-if="collection.description" class="mt-2 text-gray-600">
            {{ collection.description }}
          </p>
        </div>

        <!-- Progress Section -->
        <div class="mb-8 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
          <div class="mb-4 flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Payment Progress</p>
              <p class="text-2xl font-semibold text-gray-900">
                {{ paidItemsCount }} of {{ collection.items.length }} items paid
              </p>
            </div>
            <div class="text-right">
              <p class="text-xs text-gray-500">Total Amount</p>
              <p class="text-3xl font-bold text-[#635BFF]">
                {{ formattedAmount }}
              </p>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
            <div
              class="h-full bg-gradient-to-r from-[#635BFF] to-[#0a2540] transition-all duration-300"
              :style="{ width: progressPercentage + '%' }"
            />
          </div>
          <p class="mt-2 text-right text-xs font-medium text-gray-600">
            {{ progressPercentage }}% Complete
          </p>
        </div>

        <!-- Payment Items Grid -->
        <div class="mb-8">
          <h2 class="mb-4 text-lg font-semibold text-gray-900">Payment Items</h2>
          <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
              v-for="item in collection.items"
              :key="item.id"
              class="relative flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white transition-all duration-200 hover:border-[#635BFF] hover:shadow-md"
              :class="{ 'opacity-75 bg-gray-50': item.status === 'paid' }"
            >
              <!-- Status Badge -->
              <div class="absolute right-4 top-4 z-10">
                <div
                  v-if="item.status === 'paid'"
                  class="flex h-10 w-10 items-center justify-center rounded-full bg-green-50 border border-green-200"
                >
                  <Check class="h-5 w-5 text-green-600" />
                </div>
                <div
                  v-else
                  class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 border border-blue-200"
                >
                  <Clock class="h-5 w-5 text-[#635BFF]" />
                </div>
              </div>

              <!-- Content -->
              <div class="flex flex-1 flex-col p-6 pr-12">
                <!-- Description -->
                <h3 class="font-semibold text-gray-900">
                  {{ item.description }}
                </h3>

                <!-- Due Date -->
                <p v-if="item.due_date" class="mt-2 text-xs text-gray-500">
                  Due: {{ formatDate(item.due_date) }}
                </p>

                <!-- Amount -->
                <p class="mt-4 text-2xl font-bold text-[#635BFF]">
                  {{ formatCurrency(item.amount) }}
                </p>

                <!-- Status Text -->
                <p
                  class="mt-3 text-xs font-medium uppercase tracking-wide"
                  :class="
                    item.status === 'paid'
                      ? 'text-green-600'
                      : 'text-[#635BFF]'
                  "
                >
                  {{ item.status === 'paid' ? 'Paid' : 'Pending Payment' }}
                </p>
              </div>

              <!-- Pay Button -->
              <Link
                v-if="item.status !== 'paid'"
                :href="`/pay/${token}/item/${item.id}`"
                class="block w-full cursor-pointer px-6 py-3.5 font-semibold text-center transition-all duration-200 bg-[#635BFF] text-white hover:bg-[#0a2540] group hover:shadow-lg"
              >
                Pay {{ formatCurrency(item.amount) }}
              </Link>
              <div
                v-else
                class="block w-full px-6 py-3.5 font-semibold text-center bg-gray-100 text-gray-400 cursor-not-allowed border-t border-gray-200"
              >
                ✓ Paid
              </div>
            </div>
          </div>
        </div>

        <!-- Summary Card -->
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
          <div class="grid gap-4 sm:grid-cols-3">
            <div>
              <p class="text-xs font-medium uppercase text-gray-500">Items Total</p>
              <p class="mt-1 text-2xl font-bold text-gray-900">
                {{ collection.items.length }}
              </p>
            </div>
            <div>
              <p class="text-xs font-medium uppercase text-gray-500">Paid Items</p>
              <p class="mt-1 text-2xl font-bold text-green-600">
                {{ paidItemsCount }}
              </p>
            </div>
            <div>
              <p class="text-xs font-medium uppercase text-gray-500">Remaining</p>
              <p class="mt-1 text-2xl font-bold text-[#635BFF]">
                {{ collection.items.length - paidItemsCount }}
              </p>
            </div>
          </div>

          <div v-if="collection.expires_at" class="mt-6 border-t border-gray-200 pt-4">
            <p class="text-sm text-gray-600">
              <span class="font-semibold">Payment Due Date:</span> {{ formatDate(collection.expires_at) }}
            </p>
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
