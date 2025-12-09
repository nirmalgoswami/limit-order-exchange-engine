<script setup>
import { ref, computed } from 'vue'
import api from '../lib/http'

const symbol = ref('BTC')
const side = ref('buy')
const price = ref('')
const amount = ref('')
const loading = ref(false)
const error = ref('')
const success = ref('')

const volume = computed(() => {
  const p = Number(price.value)
  const a = Number(amount.value)
  if (!p || !a) return 0
  return p * a
})

const submit = async () => {
  error.value = ''
  success.value = ''
  loading.value = true

  try {
    await api.post('/orders', {
      symbol: symbol.value,
      side: side.value,
      price: price.value,
      amount: amount.value
    })
    success.value = 'Order placed successfully'
    price.value = ''
    amount.value = ''
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to place order'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow">
    <form @submit.prevent="submit" class="space-y-4">
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-medium mb-1 uppercase tracking-wide">Symbol</label>
          <select
            v-model="symbol"
            class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
          >
            <option value="BTC">BTC</option>
            <option value="ETH">ETH</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-medium mb-1 uppercase tracking-wide">Side</label>
          <div class="flex bg-slate-900 border border-slate-700 rounded-lg overflow-hidden text-xs">
            <button
              type="button"
              @click="side = 'buy'"
              :class="[
                'flex-1 py-2 text-center',
                side === 'buy' ? 'bg-emerald-600 text-white' : 'bg-slate-900 text-slate-300'
              ]"
            >
              Buy
            </button>
            <button
              type="button"
              @click="side = 'sell'"
              :class="[
                'flex-1 py-2 text-center',
                side === 'sell' ? 'bg-rose-600 text-white' : 'bg-slate-900 text-slate-300'
              ]"
            >
              Sell
            </button>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-medium mb-1 uppercase tracking-wide">Price (USD)</label>
          <input
            v-model="price"
            type="number"
            step="0.01"
            min="0"
            class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
          />
        </div>

        <div>
          <label class="block text-xs font-medium mb-1 uppercase tracking-wide">Amount</label>
          <input
            v-model="amount"
            type="number"
            step="0.00000001"
            min="0"
            class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
          />
        </div>
      </div>

      <div class="text-xs text-slate-400 flex items-center justify-between">
        <span>Volume preview:</span>
        <span class="font-mono">
          {{ volume.toLocaleString(undefined, { maximumFractionDigits: 2 }) }} USD
        </span>
      </div>

      <div class="space-y-2">
        <button
          type="submit"
          :disabled="loading"
          class="w-full inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold hover:bg-blue-500 disabled:opacity-60"
        >
          {{ loading ? 'Placing...' : 'Place Order' }}
        </button>

        <p v-if="error" class="text-xs text-rose-400">{{ error }}</p>
        <p v-if="success" class="text-xs text-emerald-400">{{ success }}</p>
      </div>
    </form>
  </div>
</template>
