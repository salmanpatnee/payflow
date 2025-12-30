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
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-950 dark:to-slate-900">
    <Head title="Payment Details" />

    <div class="flex items-center justify-center px-4 py-8 sm:py-12">
      <div class="w-full max-w-4xl">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-slate-900 dark:text-white sm:text-4xl">
            {{ collection.name }}
          </h1>
          <p v-if="collection.description" class="mt-2 text-slate-600 dark:text-slate-400">
            {{ collection.description }}
          </p>
        </div>

        <!-- Progress Section -->
        <div class="mb-8 rounded-lg border border-slate-200 bg-white p-6 shadow-md dark:border-slate-700 dark:bg-slate-800">
          <div class="mb-4 flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Payment Progress</p>
              <p class="text-2xl font-bold text-slate-900 dark:text-white">
                {{ paidItemsCount }} of {{ collection.items.length }} items paid
              </p>
            </div>
            <div class="text-right">
              <p class="text-xs text-slate-500 dark:text-slate-400">Total Amount</p>
              <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                {{ formattedAmount }}
              </p>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
            <div
              class="h-full bg-linear-to-r from-blue-500 to-indigo-600 transition-all duration-300"
              :style="{ width: progressPercentage + '%' }"
            />
          </div>
          <p class="mt-2 text-right text-xs font-medium text-slate-600 dark:text-slate-400">
            {{ progressPercentage }}% Complete
          </p>
        </div>

        <!-- Payment Items Grid -->
        <div class="mb-8">
          <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Payment Items</h2>
          <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
              v-for="item in collection.items"
              :key="item.id"
              class="relative flex flex-col overflow-hidden rounded-lg border border-slate-200 bg-white transition-all duration-200 dark:border-slate-700 dark:bg-slate-800"
              :class="{ 'opacity-75': item.status === 'paid' }"
            >
              <!-- Status Badge -->
              <div class="absolute right-4 top-4 z-10">
                <div
                  v-if="item.status === 'paid'"
                  class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30"
                >
                  <Check class="h-5 w-5 text-green-600 dark:text-green-400" />
                </div>
                <div
                  v-else
                  class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30"
                >
                  <Clock class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                </div>
              </div>

              <!-- Content -->
              <div class="flex flex-1 flex-col p-6 pr-12">
                <!-- Description -->
                <h3 class="font-semibold text-slate-900 dark:text-white">
                  {{ item.description }}
                </h3>

                <!-- Due Date -->
                <p v-if="item.due_date" class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                  Due: {{ formatDate(item.due_date) }}
                </p>

                <!-- Amount -->
                <p class="mt-4 text-2xl font-bold text-blue-600 dark:text-blue-400">
                  {{ formatCurrency(item.amount) }}
                </p>

                <!-- Status Text -->
                <p
                  class="mt-3 text-xs font-medium uppercase tracking-wide"
                  :class="
                    item.status === 'paid'
                      ? 'text-green-600 dark:text-green-400'
                      : 'text-blue-600 dark:text-blue-400'
                  "
                >
                  {{ item.status === 'paid' ? 'Paid' : 'Pending Payment' }}
                </p>
              </div>

              <!-- Pay Button -->
              <Link
                :href="`/pay/${token}/item/${item.id}`"
                :class="[
                  'block w-full px-6 py-3 font-semibold text-center transition-all duration-200',
                  item.status === 'paid'
                    ? 'bg-slate-100 text-slate-400 cursor-not-allowed dark:bg-slate-700 dark:text-slate-500'
                    : 'bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 group hover:shadow-lg'
                ]"
                :as="item.status === 'paid' ? 'button' : 'a'"
                :disabled="item.status === 'paid'"
              >
                {{ item.status === 'paid' ? '✓ Paid' : `Pay ${formatCurrency(item.amount)}` }}
              </Link>
            </div>
          </div>
        </div>

        <!-- Summary Card -->
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-md dark:border-slate-700 dark:bg-slate-800">
          <div class="grid gap-4 sm:grid-cols-3">
            <div>
              <p class="text-xs font-medium uppercase text-slate-500 dark:text-slate-400">Items Total</p>
              <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">
                {{ collection.items.length }}
              </p>
            </div>
            <div>
              <p class="text-xs font-medium uppercase text-slate-500 dark:text-slate-400">Paid Items</p>
              <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">
                {{ paidItemsCount }}
              </p>
            </div>
            <div>
              <p class="text-xs font-medium uppercase text-slate-500 dark:text-slate-400">Remaining</p>
              <p class="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">
                {{ collection.items.length - paidItemsCount }}
              </p>
            </div>
          </div>

          <div v-if="collection.expires_at" class="mt-6 border-t border-slate-200 pt-4 dark:border-slate-700">
            <p class="text-sm text-slate-600 dark:text-slate-400">
              <span class="font-semibold">Payment Due Date:</span> {{ formatDate(collection.expires_at) }}
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
