<script setup>
import { ref, computed } from 'vue';
import api from '../lib/http';

const props = defineProps({
  symbol: String,
  symbols: Array,
});

const emit = defineEmits(['update:symbol', 'order-placed', 'order-error']);

const side = ref('buy');
const price = ref('');
const amount = ref('');
const placingOrder = ref(false);

const quoteVolume = computed(() => {
  const p = parseFloat(price.value || '0');
  const a = parseFloat(amount.value || '0');
  if (!p || !a) return '0.00';
  return (p * a).toFixed(2);
});

async function placeOrder() {
  if (!price.value || !amount.value) {
    emit('order-error', 'Price and amount are required.');
    return;
  }

  try {
    placingOrder.value = true;
    const payload = {
      symbol: props.symbol,
      side: side.value,
      price: price.value,
      amount: amount.value,
    };

    await api.post('/orders', payload);

    price.value = '';
    amount.value = '';
    emit('order-placed');
  } catch (err) {
    console.error(err);
    if (err.response?.data?.message) {
      emit('order-error', err.response.data.message);
    } else {
      emit('order-error', 'Failed to place order.');
    }
  } finally {
    placingOrder.value = false;
  }
}
</script>

<template>
  <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4 space-y-4">
    <div class="flex items-center justify-between">
      <h2 class="text-sm font-semibold">
        Place Limit Order
      </h2>
      <div class="flex gap-2 items-center">
        <label class="text-xs text-slate-400">Symbol</label>
        <select
          :value="symbol"
          class="text-xs rounded-lg border border-slate-700 bg-slate-950 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-sky-500"
          @change="$emit('update:symbol', $event.target.value)"
        >
          <option
            v-for="s in symbols"
            :key="s"
            :value="s"
          >
            {{ s }}
          </option>
        </select>
      </div>
    </div>

    <form class="grid sm:grid-cols-4 gap-3 text-xs" @submit.prevent="placeOrder">
      <div class="sm:col-span-1 space-y-1.5">
        <label class="block text-[11px] text-slate-400">Side</label>
        <div class="grid grid-cols-2 gap-1 bg-slate-950 rounded-lg p-1 border border-slate-700">
          <button
            type="button"
            class="py-1.5 rounded-md text-[11px] font-medium transition"
            :class="side === 'buy'
              ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/60'
              : 'text-slate-300'"
            @click="side = 'buy'"
          >
            Buy
          </button>
          <button
            type="button"
            class="py-1.5 rounded-md text-[11px] font-medium transition"
            :class="side === 'sell'
              ? 'bg-rose-500/20 text-rose-300 border border-rose-500/60'
              : 'text-slate-300'"
            @click="side = 'sell'"
          >
            Sell
          </button>
        </div>
      </div>

      <div class="space-y-1.5">
        <label class="block text-[11px] text-slate-400">Price (USD)</label>
        <input
          v-model="price"
          type="number"
          step="0.01"
          min="0"
          class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-sky-500"
          placeholder="e.g. 95000"
        >
      </div>

      <div class="space-y-1.5">
        <label class="block text-[11px] text-slate-400">Amount ({{ symbol }})</label>
        <input
          v-model="amount"
          type="number"
          step="0.0001"
          min="0"
          class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-sky-500"
          placeholder="e.g. 0.01"
        >
      </div>

      <div class="space-y-1.5 flex flex-col justify-between">
        <div>
          <div class="flex justify-between text-[11px] text-slate-400 mb-1">
            <span>Volume</span>
            <span>{{ quoteVolume }} USD</span>
          </div>
          <p class="text-[11px] text-slate-500">
            Commission 1.5% applied on match.
          </p>
        </div>
        <button
          type="submit"
          class="mt-2 inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-xs font-medium shadow-sm transition border border-sky-500/60 bg-sky-500/20 text-sky-100 hover:bg-sky-500/30 disabled:opacity-60"
          :disabled="placingOrder"
        >
          <span v-if="!placingOrder">Place Order</span>
          <span v-else>Placing…</span>
        </button>
      </div>
    </form>
  </section>
</template>