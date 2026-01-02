<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import StripePaymentForm from '@/Components/Payment/StripePaymentForm.vue'

const props = defineProps({
  collection: {
    type: Object,
    required: true
  },
  stripeKey: {
    type: String,
    required: true
  }
})

const pollInterval = ref(null)

const hasProcessingPayments = computed(() => {
  return props.collection.items.some(item => item.status === 'processing')
})

const completedCount = computed(() => {
  return props.collection.items.filter(item => item.status === 'completed').length
})

const totalItems = computed(() => props.collection.items.length)

const progressPercentage = computed(() => {
  return (completedCount.value / totalItems.value) * 100
})

const formatCurrency = (amount, currency) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency.toUpperCase(),
  }).format(amount)
}

const getStatusColor = (status) => {
  const colors = {
    pending: 'text-amber-600 dark:text-amber-400',
    processing: 'text-blue-600 dark:text-blue-400',
    completed: 'text-emerald-600 dark:text-emerald-400',
    failed: 'text-rose-600 dark:text-rose-400'
  }
  return colors[status] || 'text-gray-600 dark:text-gray-400'
}

const getStatusBadgeColor = (status) => {
  return status === 'completed' ? 'bg-emerald-500' :
         status === 'processing' ? 'bg-blue-500 animate-pulse' :
         status === 'failed' ? 'bg-rose-500' :
         'bg-amber-500'
}

const getStatusLabel = (status) => {
  const labels = {
    pending: 'Pending Payment',
    processing: 'Processing',
    completed: 'Paid',
    failed: 'Failed'
  }
  return labels[status] || status
}

// Poll for status updates when there are processing payments
const startPolling = () => {
  if (pollInterval.value) return

  // Poll every 3 seconds if there are processing payments
  if (hasProcessingPayments.value) {
    pollInterval.value = setInterval(() => {
      // Use Inertia's reload to fetch latest collection data
      router.reload({ only: ['collection'], preserveScroll: true })
    }, 3000)
  }
}

const stopPolling = () => {
  if (pollInterval.value) {
    clearInterval(pollInterval.value)
    pollInterval.value = null
  }
}

// Watch for collection status changes and redirect to thank you when all completed
watch(
  () => props.collection.status,
  (newStatus) => {
    if (newStatus === 'completed' && !hasProcessingPayments.value) {
      stopPolling()
      // Redirect to thank you page after a short delay
      setTimeout(() => {
        router.visit(`/payment/${props.collection.uuid}/thank-you`)
      }, 1500)
    }
  },
  { immediate: true }
)

// Watch for processing payments and manage polling
watch(
  hasProcessingPayments,
  (hasProcessing) => {
    if (hasProcessing && !pollInterval.value) {
      startPolling()
    } else if (!hasProcessing && pollInterval.value) {
      stopPolling()
    }
  },
  { immediate: true }
)

// Start polling when component mounts
onMounted(() => {
  startPolling()
})

// Clean up polling on unmount
onUnmounted(() => {
  stopPolling()
})
</script>

<template>
  <Head :title="collection.title" />

  <div class="min-h-screen bg-gradient-to-br from-stone-50 via-white to-stone-100 dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950">
    <!-- Background Pattern -->
    <div class="fixed inset-0 opacity-[0.03] dark:opacity-[0.02]" style="background-image: radial-gradient(circle at 1px 1px, rgb(0 0 0) 1px, transparent 0); background-size: 24px 24px;"></div>

    <div class="relative min-h-screen flex items-center justify-center px-4 py-12">
      <div class="w-full max-w-3xl">
        <!-- Progress Bar -->
        <div v-if="totalItems > 1" class="mb-8" role="region" aria-label="Payment progress">
          <div class="flex items-center justify-between mb-3">
            <span id="progress-label" class="text-sm font-medium tracking-wide uppercase text-stone-500 dark:text-zinc-500">
              Payment Progress
            </span>
            <span class="text-sm tabular-nums font-semibold text-stone-900 dark:text-zinc-100" aria-live="polite">
              {{ completedCount }} / {{ totalItems }}
            </span>
          </div>
          <div
            class="h-1.5 bg-stone-200 dark:bg-zinc-800 rounded-full overflow-hidden"
            role="progressbar"
            :aria-valuenow="progressPercentage"
            aria-valuemin="0"
            aria-valuemax="100"
            :aria-label="`Payment progress: ${completedCount} of ${totalItems} items completed`"
          >
            <div
              class="h-full bg-gradient-to-r from-emerald-600 to-teal-600 dark:from-emerald-500 dark:to-teal-500 transition-all duration-700 ease-out rounded-full"
              :style="{ width: `${progressPercentage}%` }"
            ></div>
          </div>
        </div>

        <!-- Main Card -->
        <div class="bg-white/80 dark:bg-zinc-900/80 backdrop-blur-xl rounded-2xl shadow-2xl shadow-stone-900/5 dark:shadow-black/20 border border-stone-200/50 dark:border-zinc-800/50 overflow-hidden">
          <!-- Header -->
          <div class="border-b border-stone-200 dark:border-zinc-800 bg-gradient-to-br from-stone-50 to-white dark:from-zinc-900 dark:to-zinc-900/50 px-8 py-10">
            <h1 class="text-4xl font-bold text-stone-900 dark:text-zinc-100 tracking-tight mb-3" style="font-family: 'Fraunces', Georgia, serif;">
              {{ collection.title }}
            </h1>
            <p v-if="collection.description" class="text-lg text-stone-600 dark:text-zinc-400 leading-relaxed max-w-2xl">
              {{ collection.description }}
            </p>
          </div>

          <!-- Payment Items -->
          <div class="p-8 space-y-6">
            <div
              v-for="(item, index) in collection.items"
              :key="item.id"
              class="group relative"
              :style="{ animationDelay: `${index * 100}ms` }"
              style="animation: slideUp 0.6s ease-out backwards;"
            >
              <!-- Item Card -->
              <div class="relative bg-stone-50 dark:bg-zinc-800/50 rounded-xl border border-stone-200 dark:border-zinc-700/50 overflow-hidden transition-all duration-300 hover:shadow-lg hover:shadow-stone-900/5 dark:hover:shadow-black/20">
                <!-- Status Badge -->
                <div class="absolute top-4 right-4">
                  <span
                    :class="['inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold tracking-wide uppercase', getStatusColor(item.status)]"
                    class="bg-white/80 dark:bg-zinc-900/80 backdrop-blur-sm border border-stone-200/50 dark:border-zinc-700/50"
                    role="status"
                    :aria-label="`Payment status: ${getStatusLabel(item.status)}`"
                  >
                    <span class="w-1.5 h-1.5 rounded-full mr-2" :class="getStatusBadgeColor(item.status)" aria-hidden="true"></span>
                    {{ getStatusLabel(item.status) }}
                  </span>
                </div>

                <div class="p-6 pr-32">
                  <h3 class="text-lg font-semibold text-stone-900 dark:text-zinc-100 mb-1">
                    {{ item.name }}
                  </h3>
                  <p v-if="item.description" class="text-sm text-stone-600 dark:text-zinc-400 mb-3">
                    {{ item.description }}
                  </p>
                  <div class="flex items-baseline gap-2">
                    <span class="text-3xl font-bold tabular-nums text-stone-900 dark:text-zinc-100" style="font-family: 'Fraunces', Georgia, serif;">
                      {{ formatCurrency(item.amount, item.currency) }}
                    </span>
                    <span class="text-sm text-stone-500 dark:text-zinc-500 uppercase tracking-wider">
                      {{ item.currency }}
                    </span>
                  </div>
                </div>

                <!-- Payment Form for Pending Items -->
                <div v-if="item.status === 'pending'" class="px-6 pb-6 pt-4 border-t border-stone-200 dark:border-zinc-700/50 bg-white/50 dark:bg-zinc-900/30">
                  <StripePaymentForm
                    :payment-item="item"
                    :collection-uuid="collection.uuid"
                    :stripe-key="stripeKey"
                  />
                </div>

                <!-- Failed Payment State with Retry Option -->
                <div v-if="item.status === 'failed'" class="px-6 pb-6 pt-4 border-t border-stone-200 dark:border-zinc-700/50 bg-rose-50/50 dark:bg-rose-950/20" role="alert">
                  <div class="space-y-4">
                    <div class="flex items-start gap-3 text-sm text-rose-700 dark:text-rose-300">
                      <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                      </svg>
                      <div>
                        <p class="font-medium">Payment failed</p>
                        <p class="text-rose-600 dark:text-rose-400 mt-1">Please try again with a different payment method</p>
                      </div>
                    </div>
                    <StripePaymentForm
                      :payment-item="item"
                      :collection-uuid="collection.uuid"
                      :stripe-key="stripeKey"
                      :is-retry="true"
                    />
                  </div>
                </div>

                <!-- Completed State -->
                <div v-if="item.status === 'completed' && item.paid_at" class="px-6 pb-6 pt-4 border-t border-stone-200 dark:border-zinc-700/50 bg-emerald-50/50 dark:bg-emerald-950/20" role="status">
                  <div class="flex items-center gap-2 text-sm text-emerald-700 dark:text-emerald-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>Paid on {{ new Date(item.paid_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="border-t border-stone-200 dark:border-zinc-800 bg-stone-50/50 dark:bg-zinc-900/30 px-8 py-6">
            <div class="flex items-center gap-3 text-sm text-stone-600 dark:text-zinc-400">
              <svg class="w-5 h-5 text-stone-400 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
              </svg>
              <span>Secured by Stripe • PCI DSS Compliant</span>
            </div>
          </div>
        </div>

        <!-- Footer Note -->
        <div class="mt-8 text-center">
          <p class="text-sm text-stone-500 dark:text-zinc-500">
            Your payment information is encrypted and secure
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&display=swap');

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
