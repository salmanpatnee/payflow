<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Check, Clock, AlertCircle } from 'lucide-vue-next'

interface PaymentItem {
  id: number
  name: string
  description?: string
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
                <!-- Name -->
                <h3 class="font-semibold text-gray-900">
                  {{ item.name }}
                </h3>

                <!-- Description -->
                <p v-if="item.description" class="mt-1 text-sm text-gray-600">
                  {{ item.description }}
                </p>

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
