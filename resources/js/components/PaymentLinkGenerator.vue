<script setup lang="ts">
import { ref, computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Copy, Link as LinkIcon, Trash2, Loader2, AlertCircle } from 'lucide-vue-next'

interface Props {
  collectionId: number
  onSuccess?: (data: { token: string; url: string; expires_at: string }) => void
}

const props = defineProps<Props>()

const state = ref<'idle' | 'loading' | 'success' | 'error'>('idle')
const paymentLink = ref<{ token: string; url: string; expires_at: string } | null>(null)
const error = ref<string | null>(null)
const copied = ref(false)

const isActive = computed(() => {
  if (!paymentLink.value?.expires_at) return true
  return new Date(paymentLink.value.expires_at) > new Date()
})

const formattedExpiry = computed(() => {
  if (!paymentLink.value?.expires_at) return 'N/A'
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(new Date(paymentLink.value.expires_at))
})

const generateLink = async () => {
  state.value = 'loading'
  error.value = null

  try {
    const response = await fetch(`/admin/payment-collections/${props.collectionId}/generate-link`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    })

    if (!response.ok) {
      throw new Error('Failed to generate payment link')
    }

    const data = await response.json()
    paymentLink.value = data
    state.value = 'success'
    props.onSuccess?.(data)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'An error occurred'
    state.value = 'error'
  }
}

const copyToClipboard = async () => {
  if (!paymentLink.value?.url) return

  try {
    // Try modern clipboard API first
    if (navigator.clipboard && navigator.clipboard.writeText) {
      await navigator.clipboard.writeText(paymentLink.value.url)
      copied.value = true
      setTimeout(() => {
        copied.value = false
      }, 2000)
    } else {
      // Fallback for older browsers
      const textArea = document.createElement('textarea')
      textArea.value = paymentLink.value.url
      textArea.style.position = 'fixed'
      textArea.style.left = '-999999px'
      document.body.appendChild(textArea)
      textArea.focus()
      textArea.select()
      document.execCommand('copy')
      document.body.removeChild(textArea)
      copied.value = true
      setTimeout(() => {
        copied.value = false
      }, 2000)
    }
  } catch (err) {
    error.value = 'Failed to copy link to clipboard'
    console.error('Failed to copy:', err)
  }
}

const revokeLink = async () => {
  if (!confirm('Are you sure you want to revoke this payment link?')) return

  state.value = 'loading'
  error.value = null

  try {
    const response = await fetch(`/admin/payment-collections/${props.collectionId}/payment-link`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    })

    if (!response.ok) {
      throw new Error('Failed to revoke payment link')
    }

    paymentLink.value = null
    state.value = 'idle'
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'An error occurred'
    state.value = 'error'
  }
}

const loadPaymentLink = async () => {
  try {
    const response = await fetch(`/admin/payment-collections/${props.collectionId}/payment-link`)

    if (!response.ok) {
      if (response.status === 404) {
        state.value = 'idle'
        return
      }
      throw new Error('Failed to load payment link')
    }

    const data = await response.json()
    if (data.token) {
      paymentLink.value = { token: data.token, url: data.url, expires_at: data.expires_at }
    }
    state.value = 'idle'
  } catch (err) {
    console.error('Failed to load payment link:', err)
    state.value = 'idle'
  }
}

// Load existing link on mount
loadPaymentLink()
</script>

<template>
  <Card class="overflow-hidden border-border/50 shadow-sm pt-0">
    <div class="border-b border-border/50 bg-muted/30 px-6 py-4 bg-primary hover:bg-primary">
      <div class="flex items-center justify-between ">
        <h2 class="font-semibold text-primary-foreground">Payment Link</h2>
        <Badge v-if="paymentLink && isActive" variant="default">Active</Badge>
        <Badge v-else-if="paymentLink" variant="destructive">Expired</Badge>
      </div>
    </div>

    <div class="p-6 py-0">
      <!-- Error State -->
      <div v-if="error" class="mb-4 flex items-start gap-3 rounded-lg border border-destructive/30 bg-destructive/5 p-4">
        <AlertCircle class="mt-0.5 h-5 w-5 text-destructive flex-shrink-0" />
        <p class="text-sm text-destructive">{{ error }}</p>
      </div>

      <!-- No Link State -->
      <div v-if="!paymentLink" class="text-center py-8">
        <LinkIcon class="mx-auto h-12 w-12 text-muted-foreground/50 mb-4" />
        <p class="text-sm text-muted-foreground mb-4">
          Generate a shareable payment link for this collection
        </p>
        <Button
          @click="generateLink"
          :disabled="state === 'loading'"
          class="gap-2"
        >
          <Loader2 v-if="state === 'loading'" class="h-4 w-4 animate-spin" />
          <LinkIcon v-else class="h-4 w-4" />
          {{ state === 'loading' ? 'Generating...' : 'Generate Payment Link' }}
        </Button>
      </div>

      <!-- Active Link State -->
      <div v-else class="space-y-4">
        <!-- Link Display -->
        <div class="space-y-2">
          <label class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
            Payment Link URL
          </label>
          <div class="flex items-center gap-2 rounded-lg border border-border/50 bg-muted/20 p-3 font-mono text-sm">
            <span class="flex-1 overflow-x-auto whitespace-nowrap text-foreground">
              {{ paymentLink.url }}
            </span>
            <Button
              @click="copyToClipboard"
              size="sm"
              variant="ghost"
              class="gap-2 flex-shrink-0"
              :class="{ 'text-green-600': copied }"
            >
              <Copy class="h-4 w-4" />
              {{ copied ? 'Copied!' : 'Copy' }}
            </Button>
          </div>
        </div>

        <!-- Token Display -->
        <div class="space-y-2">
          <label class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
            Access Token
          </label>
          <p class="rounded-lg border border-border/50 bg-muted/20 p-3 font-mono text-sm text-muted-foreground break-all">
            {{ paymentLink.token }}
          </p>
        </div>

        <!-- Expiry Info -->
        <div class="space-y-2">
          <label class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
            Expires On
          </label>
          <p class="text-sm text-foreground font-medium">
            {{ formattedExpiry }}
          </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-2 pt-4">
          <Button
            @click="generateLink"
            variant="outline"
            size="sm"
            class="gap-2"
            :disabled="state === 'loading'"
          >
            <LinkIcon class="h-4 w-4" />
            Regenerate Link
          </Button>
          <Button
            @click="revokeLink"
            variant="destructive"
            size="sm"
            class="gap-2"
            :disabled="state === 'loading'"
          >
            <Trash2 class="h-4 w-4" />
            Revoke Link
          </Button>
        </div>
      </div>
    </div>
  </Card>
</template>
