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

    // Create Elements instance with Stripe-branded styling
    elements.value = stripe.value.elements({
      appearance: {
        theme: 'stripe',
        variables: {
          colorPrimary: 'hsl(243 75% 59%)',  // Stripe purple
          colorBackground: '#ffffff',
          colorText: '#1a1f36',
          colorDanger: '#df1b41',
          fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Ubuntu, sans-serif',
          fontSizeBase: '16px',
          spacingUnit: '4px',
          borderRadius: '6px',
        },
        rules: {
          '.Input': {
            border: '1px solid #e3e8ee',
            padding: '12px',
            boxShadow: '0 1px 3px 0 rgba(50, 50, 93, 0.05)',
          },
          '.Input:focus': {
            border: '1px solid hsl(243 75% 59%)',
            boxShadow: '0 0 0 3px hsl(243 75% 59% / 0.1)',
            outline: 'none',
          },
          '.Label': {
            fontWeight: '600',
            fontSize: '14px',
            marginBottom: '8px',
            color: '#1a1f36',
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

    // Payment successful - redirect to the URL provided by backend
    // Backend determines if it's thank you page (all complete) or payment page (more to pay)
    if (confirmData.redirect_url) {
      window.location.href = confirmData.redirect_url
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
    class="space-y-6"
    :aria-busy="isProcessing"
    role="form"
    aria-label="Payment form"
  >
    <!-- Payment Methods Header -->
    <div class="flex items-center justify-between pb-4 border-b border-gray-200">
      <div class="flex items-center gap-2">
        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
        </svg>
        <span class="text-sm font-semibold text-gray-700">Payment method</span>
      </div>
      <div class="flex items-center">
        <img src="/images/cards.webp" alt="Accepted cards: Visa, Mastercard, American Express, Discover" class="h-6" />
      </div>
    </div>

    <!-- Card Element Container -->
    <div>
      <label
        :for="`card-element-${paymentItem.id}`"
        class="block text-sm font-semibold text-gray-700 mb-3"
      >
        Card information
      </label>
      <div
        :id="`card-element-${paymentItem.id}`"
        class="p-3 bg-white border border-gray-300 rounded-md shadow-sm transition-all duration-200 hover:border-gray-400"
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
      class="w-full relative overflow-hidden group cursor-pointer"
    >
      <div
        class="relative px-6 py-3.5 font-semibold rounded-md transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
        :class="isProcessing || !cardElementMounted ? 'bg-[hsl(243_75%_59%_/_0.6)] cursor-not-allowed' : 'bg-[hsl(243_75%_59%)] hover:bg-[hsl(243_75%_54%)] shadow-sm'"
      >
        <span v-if="!isProcessing" class="flex items-center justify-center gap-2 text-white">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
          </svg>
          {{ isRetry ? 'Retry Payment' : 'Pay now' }}
        </span>
        <span v-else class="flex items-center justify-center gap-3 text-white">
          <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span class="sr-only">Processing payment, please wait</span>
          Processing...
        </span>
      </div>
    </button>

    <!-- Security & Stripe Branding -->
    <div class="pt-4 border-t border-gray-200 space-y-3">
      <!-- Powered by Stripe -->
      <div class="flex items-center justify-center gap-1.5">
        <span class="text-xs text-gray-500">Powered by</span>
        <span class="text-sm font-semibold text-[#635BFF]">Stripe</span>
      </div>

      <!-- Security Indicators -->
      <div class="flex items-center justify-center gap-4 text-xs text-gray-500">
        <div class="flex items-center gap-1.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
          </svg>
          <span>Secure 256-bit SSL</span>
        </div>
        <span class="text-gray-300">•</span>
        <div class="flex items-center gap-1.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
          </svg>
          <span>PCI Compliant</span>
        </div>
      </div>
    </div>
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
