<script setup>
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  collection: {
    type: Object,
    required: true
  }
})

const totalAmount = computed(() => {
  return props.collection.items.reduce((sum, item) => sum + parseFloat(item.amount || 0), 0)
})

const currency = computed(() => {
  return props.collection.items[0]?.currency || 'USD'
})

const formatCurrency = (amount, curr) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: curr.toUpperCase(),
  }).format(amount)
}
</script>

<template>
  <Head title="Payment Complete" />

  <div class="min-h-screen bg-gradient-to-br from-emerald-50 via-teal-50 to-cyan-50 dark:from-emerald-950 dark:via-teal-950 dark:to-cyan-950">
    <!-- Animated Background Pattern -->
    <div class="fixed inset-0 opacity-10 dark:opacity-5">
      <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, currentColor 1px, transparent 0); background-size: 40px 40px; animation: float 20s ease-in-out infinite;"></div>
    </div>

    <div class="relative min-h-screen flex items-center justify-center px-4 py-12">
      <div class="w-full max-w-2xl">
        <!-- Success Animation Container -->
        <div class="text-center mb-12" style="animation: scaleIn 0.6s ease-out backwards;">
          <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-full shadow-2xl shadow-emerald-900/30 dark:shadow-emerald-950/50 mb-6" style="animation: bounceIn 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55) 0.2s backwards;">
            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="animation: checkDraw 0.5s ease-out 0.8s backwards;">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
            </svg>
          </div>

          <h1 class="text-5xl font-bold text-stone-900 dark:text-zinc-100 mb-4 tracking-tight" style="font-family: 'Fraunces', Georgia, serif; animation: slideUp 0.6s ease-out 0.3s backwards;">
            Payment Complete!
          </h1>
          <p class="text-xl text-stone-600 dark:text-zinc-400" style="animation: slideUp 0.6s ease-out 0.4s backwards;">
            Thank you for your payment
          </p>
        </div>

        <!-- Summary Card -->
        <div class="bg-white/90 dark:bg-zinc-900/90 backdrop-blur-xl rounded-2xl shadow-2xl shadow-stone-900/10 dark:shadow-black/30 border border-stone-200/50 dark:border-zinc-800/50 overflow-hidden" style="animation: slideUp 0.6s ease-out 0.5s backwards;">
          <!-- Header -->
          <div class="border-b border-stone-200 dark:border-zinc-800 bg-gradient-to-br from-stone-50 to-white dark:from-zinc-900 dark:to-zinc-900/50 px-8 py-6">
            <h2 class="text-2xl font-bold text-stone-900 dark:text-zinc-100" style="font-family: 'Fraunces', Georgia, serif;">
              {{ collection.title }}
            </h2>
            <p v-if="collection.description" class="text-stone-600 dark:text-zinc-400 mt-2">
              {{ collection.description }}
            </p>
          </div>

          <!-- Payment Items -->
          <div class="p-8 space-y-4">
            <div
              v-for="(item, index) in collection.items"
              :key="index"
              class="flex items-start justify-between py-4 border-b border-stone-200 dark:border-zinc-800 last:border-0"
              :style="{ animationDelay: `${0.6 + (index * 0.1)}s` }"
              style="animation: fadeIn 0.5s ease-out backwards;"
            >
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                  <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                  <h3 class="font-semibold text-stone-900 dark:text-zinc-100">
                    {{ item.name }}
                  </h3>
                </div>
                <p v-if="item.description" class="text-sm text-stone-600 dark:text-zinc-400 ml-6">
                  {{ item.description }}
                </p>
                <p v-if="item.paid_at" class="text-sm text-stone-500 dark:text-zinc-500 ml-6">
                  Paid on {{ new Date(item.paid_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: 'numeric' }) }}
                </p>
              </div>
              <div class="text-right ml-4">
                <div class="text-lg font-bold tabular-nums text-stone-900 dark:text-zinc-100">
                  {{ formatCurrency(item.amount, item.currency) }}
                </div>
              </div>
            </div>
          </div>

          <!-- Total -->
          <div class="border-t-2 border-emerald-200 dark:border-emerald-900/50 bg-emerald-50/50 dark:bg-emerald-950/20 px-8 py-6">
            <div class="flex items-center justify-between">
              <span class="text-lg font-semibold text-stone-900 dark:text-zinc-100">
                Total Paid
              </span>
              <span class="text-3xl font-bold tabular-nums text-emerald-700 dark:text-emerald-400" style="font-family: 'Fraunces', Georgia, serif;">
                {{ formatCurrency(totalAmount, currency) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Additional Info -->
        <div class="mt-8 text-center" style="animation: fadeIn 0.6s ease-out 1s backwards;">
          <p class="text-xs text-stone-500 dark:text-zinc-500">
            Transaction ID: {{ collection.uuid }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&display=swap');

@keyframes scaleIn {
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

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

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes bounceIn {
  0% {
    opacity: 0;
    transform: scale(0.3);
  }
  50% {
    transform: scale(1.05);
  }
  70% {
    transform: scale(0.9);
  }
  100% {
    opacity: 1;
    transform: scale(1);
  }
}

@keyframes checkDraw {
  from {
    stroke-dasharray: 100;
    stroke-dashoffset: 100;
  }
  to {
    stroke-dasharray: 100;
    stroke-dashoffset: 0;
  }
}

@keyframes float {
  0%, 100% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-10px);
  }
}
</style>
