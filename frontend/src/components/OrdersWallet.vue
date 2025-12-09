<script setup>
import { onMounted, ref, watch } from 'vue'
import api from '../lib/http'
import useEcho from '../services/echo'

const profile = ref(null)
const orders = ref([])
const symbol = ref('BTC')
const loading = ref(false)
const error = ref('')

const echo = useEcho()

const fetchProfile = async () => {
  try {
    const { data } = await api.get('/profile')
    profile.value = data
  } catch (e) {
    console.error(e)
  }
}

const fetchOrders = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/orders', { params: { symbol: symbol.value } })
    orders.value = data
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to load orders'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await Promise.all([fetchProfile(), fetchOrders()])

  // private-user.{id}
  if (profile.value?.id) {
    echo.private(`user.${profile.value.id}`)
      .listen('OrderMatched', (payload) => {
        // payload: { order, counterOrder, trade, balances, assets }
        fetchProfile()
        fetchOrders()
      })
  }
})

watch(symbol, fetchOrders)
</script>

<template>
  <div class="space-y-4">
    <!-- Wallet -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4">
      <h2 class="text-sm font-semibold mb-3">Wallet Overview</h2>
      <div v-if="profile" class="space-y-2 text-sm">
        <div class="flex items-center justify-between">
          <span class="text-slate-400">USD Balance</span>
          <span class="font-mono">
            $ {{ Number(profile.balance).toLocaleString(undefined, { maximumFractionDigits: 2 }) }}
          </span>
        </div>

        <div class="mt-3">
          <div class="text-xs text-slate-500 mb-1">Assets</div>
          <div class="space-y-1">
            <div
              v-for="asset in profile.assets"
              :key="asset.symbol"
              class="flex items-center justify-between text-xs"
            >
              <span>{{ asset.symbol }}</span>
              <span class="font-mono">
                {{ asset.amount }} (locked: {{ asset.locked_amount }})
              </span>
            </div>
          </div>
        </div>
      </div>
      <p v-else class="text-xs text-slate-500">Loading profile…</p>
    </div>

    <!-- Orders -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-sm font-semibold">Orders</h2>
        <select
          v-model="symbol"
          class="text-xs rounded-lg border border-slate-700 bg-slate-900 px-2 py-1"
        >
          <option value="BTC">BTC</option>
          <option value="ETH">ETH</option>
        </select>
      </div>

      <p v-if="error" class="text-xs text-rose-400 mb-2">{{ error }}</p>

      <div v-if="loading" class="text-xs text-slate-500">Loading orders…</div>

      <div v-else class="space-y-1 text-xs max-h-64 overflow-auto">
        <div
          v-for="order in orders"
          :key="order.id"
          class="flex items-center justify-between border-b border-slate-800/80 py-1 last:border-none"
        >
          <div>
            <div class="flex items-center gap-2">
              <span
                :class="[
                  'px-1.5 py-0.5 rounded-full text-[10px] font-semibold',
                  order.side === 'buy'
                    ? 'bg-emerald-500/20 text-emerald-300'
                    : 'bg-rose-500/20 text-rose-300'
                ]"
              >
                {{ order.side.toUpperCase() }}
              </span>
              <span class="font-mono">{{ order.symbol }}</span>
            </div>
            <div class="text-[10px] text-slate-500">
              Status:
              <span>
                {{ order.status === 1 ? 'Open' : order.status === 2 ? 'Filled' : 'Cancelled' }}
              </span>
            </div>
          </div>
          <div class="text-right">
            <div class="font-mono">
              {{ order.amount }} @ {{ order.price }}
            </div>
            <div class="text-[10px] text-slate-500">
              {{ new Date(order.created_at).toLocaleString() }}
            </div>
          </div>
        </div>

        <p v-if="!orders.length" class="text-xs text-slate-500">No orders yet.</p>
      </div>
    </div>
  </div>
</template>
