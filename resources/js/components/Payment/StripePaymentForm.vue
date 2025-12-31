<script setup>
import { ref, onMounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { loadStripe } from '@stripe/stripe-js'

const props = defineProps({
  paymentItem: {
    type: Object,
    required: true
  },
  collectionUuid: {
    type: String,
    required: true
  },
  stripeKey: {
    type: String,
    required: true
  },
  isRetry: {
    type: Boolean,
    default: false
  }
})

const stripe = ref(null)
const elements = ref(null)
const cardElement = ref(null)
const isProcessing = ref(false)
const error = ref('')
const cardElementMounted = ref(false)

// TEMPORARY: Force enable button for testing (remove after rebuild)
setTimeout(() => {
  if (!cardElementMounted.value) {
    console.warn('Card element not ready after 3s, enabling button anyway for testing')
    cardElementMounted.value = true
  }
}, 3000)

const initializeStripe = async () => {
  try {
    console.log('Initializing Stripe with key:', props.stripeKey ? 'Key provided' : 'No key')
    stripe.value = await loadStripe(props.stripeKey)

    if (!stripe.value) {
      console.error('Stripe failed to initialize')
      error.value = 'Failed to load payment system. Please refresh the page.'
      return
    }
    console.log('Stripe loaded successfully')

    // Create Elements instance with custom styling
    elements.value = stripe.value.elements({
      appearance: {
        theme: 'stripe',
        variables: {
          colorPrimary: '#059669',
          colorBackground: '#ffffff',
          colorText: '#1c1917',
          colorDanger: '#dc2626',
          fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
          spacingUnit: '4px',
          borderRadius: '8px',
        },
        rules: {
          '.Input': {
            border: '1px solid #e7e5e4',
            boxShadow: '0 1px 2px 0 rgb(0 0 0 / 0.05)',
          },
          '.Input:focus': {
            border: '1px solid #059669',
            boxShadow: '0 0 0 3px rgb(5 150 105 / 0.1)',
          },
          '.Label': {
            fontWeight: '500',
            marginBottom: '8px',
          }
        }
      }
    })

    // Create card element
    cardElement.value = elements.value.create('card', {
      style: {
        base: {
          fontSize: '16px',
          color: '#1c1917',
          fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
          '::placeholder': {
            color: '#a8a29e',
          },
        },
        invalid: {
          color: '#dc2626',
          iconColor: '#dc2626',
        },
      },
    })

    // Mount card element
    const cardElementContainer = document.getElementById(`card-element-${props.paymentItem.id}`)
    if (cardElementContainer) {
      console.log('Mounting Stripe card element to:', cardElementContainer)
      cardElement.value.mount(cardElementContainer)

      // Wait for Stripe Elements to be ready
      cardElement.value.on('ready', () => {
        console.log('Stripe card element is ready!')
        cardElementMounted.value = true
      })

      // Listen for changes
      cardElement.value.on('change', (event) => {
        if (event.error) {
          error.value = event.error.message
        } else {
          error.value = ''
        }
      })
    }
  } catch (err) {
    console.error('Stripe initialization error:', err)
    error.value = 'Failed to initialize payment system. Please refresh the page.'
  }
}

const handleSubmit = async () => {
  if (isProcessing.value || !stripe.value || !cardElement.value) {
    return
  }

  isProcessing.value = true
  error.value = ''

  try {
    // Step 1: Create PaymentIntent
    const intentResponse = await fetch(`/payment/${props.collectionUuid}/payment-intent`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify({
        payment_item_id: props.paymentItem.id,
      }),
    })

    const intentData = await intentResponse.json()

    if (!intentResponse.ok) {
      throw new Error(intentData.error || 'Failed to create payment intent')
    }

    // Step 2: Confirm payment with Stripe
    const { error: stripeError, paymentIntent } = await stripe.value.confirmCardPayment(
      intentData.client_secret,
      {
        payment_method: {
          card: cardElement.value,
        },
      }
    )

    if (stripeError) {
      throw new Error(stripeError.message)
    }

    // Step 3: Confirm payment on backend
    const confirmResponse = await fetch(`/payment/${props.collectionUuid}/confirm-payment`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify({
        payment_item_id: props.paymentItem.id,
        payment_intent_id: paymentIntent.id,
      }),
    })

    const confirmData = await confirmResponse.json()

    if (!confirmResponse.ok) {
      throw new Error(confirmData.error || 'Failed to confirm payment')
    }

    // Payment successful - redirect based on collection status
    if (confirmData.redirect_url) {
      // Show success message briefly before redirect
      const collectionStatus = confirmData.collection_status

      if (collectionStatus?.all_completed) {
        // All payments completed - redirect to collection page (will show thank you)
        window.location.href = confirmData.redirect_url
      } else {
        // More payments remaining - redirect to collection page with status update
        window.location.href = confirmData.redirect_url
      }
    } else {
      // Fallback: reload current page
      router.reload({ only: ['collection'] })
    }

  } catch (err) {
    console.error('Payment error:', err)
    error.value = err.message || 'Payment failed. Please try again.'
  } finally {
    isProcessing.value = false
  }
}

onMounted(() => {
  initializeStripe()
})
</script>

<template>
  <div
    class="space-y-4"
    :aria-busy="isProcessing"
    role="form"
    aria-label="Payment form"
  >
    <!-- Card Element Container -->
    <div>
      <label
        :for="`card-element-${paymentItem.id}`"
        class="block text-sm font-medium text-stone-700 dark:text-zinc-300 mb-2"
      >
        Card Information
      </label>
      <div
        :id="`card-element-${paymentItem.id}`"
        class="p-4 bg-white dark:bg-zinc-900 border border-stone-300 dark:border-zinc-700 rounded-lg shadow-sm transition-all duration-200"
        :class="{ 'opacity-50': isProcessing }"
        role="textbox"
        aria-label="Secure card input"
        :aria-describedby="error ? `error-${paymentItem.id}` : undefined"
        :aria-invalid="!!error"
      ></div>
    </div>

    <!-- Error Message -->
    <div
      v-if="error"
      :id="`error-${paymentItem.id}`"
      class="flex items-start gap-3 p-4 bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/50 rounded-lg"
      role="alert"
      aria-live="assertive"
      aria-atomic="true"
    >
      <svg class="w-5 h-5 text-rose-600 dark:text-rose-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      <p class="text-sm text-rose-700 dark:text-rose-300">{{ error }}</p>
    </div>

    <!-- Submit Button -->
    <button
      @click="handleSubmit"
      :disabled="isProcessing || !cardElementMounted"
      :aria-label="isRetry ? 'Retry payment' : 'Complete secure payment'"
      :aria-busy="isProcessing"
      class="w-full relative overflow-hidden group"
    >
      <div
        class="relative px-6 py-3.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 dark:from-emerald-500 dark:to-teal-500 dark:hover:from-emerald-600 dark:hover:to-teal-600 rounded-lg shadow-lg shadow-emerald-900/20 dark:shadow-emerald-950/40 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <span v-if="!isProcessing" class="flex items-center justify-center gap-2 text-white font-semibold tracking-wide">
          <svg v-if="!isRetry" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
          </svg>
          <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
          {{ isRetry ? 'Retry Payment' : 'Complete Payment' }}
        </span>
        <span v-else class="flex items-center justify-center gap-3 text-white font-semibold">
          <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span class="sr-only">Processing payment, please wait</span>
          Processing Payment...
        </span>
      </div>

      <!-- Button shine effect -->
      <div class="absolute inset-0 -translate-x-full group-hover:translate-x-full transition-transform duration-1000 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
    </button>

    <!-- Security Note -->
    <p class="text-xs text-center text-stone-500 dark:text-zinc-500">
      Your payment is secured with 256-bit encryption
    </p>
  </div>
</template>

<style scoped>
/* Additional Stripe Element Dark Mode Support */
@media (prefers-color-scheme: dark) {
  :deep(.StripeElement) {
    background-color: rgb(24 24 27);
    color: rgb(250 250 250);
  }
}
</style>
