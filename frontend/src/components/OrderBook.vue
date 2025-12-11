<script setup>
import { formatUsd, formatCrypto } from '../lib/formatters';

defineProps({
  symbol: String,
  orderBook: Object,
});
</script>

<template>
  <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
    <div class="flex items-center justify-between mb-2">
      <h2 class="text-sm font-semibold">
        Orderbook — {{ symbol }}
      </h2>
    </div>

    <div class="grid grid-cols-2 gap-3 text-[11px]">
      <!-- Asks -->
      <div>
        <h3 class="text-rose-300 font-semibold mb-1">
          Asks (Sell)
        </h3>
        <div class="flex justify-between text-slate-500 mb-1">
          <span>Price</span>
          <span>Amount</span>
        </div>
        <div class="space-y-1 max-h-56 overflow-auto pr-1">
          <div
            v-for="o in orderBook.sells"
            :key="o.id"
            class="flex justify-between text-[11px] text-slate-200"
          >
            <span class="text-rose-300">
              {{ formatUsd(o.price) }}
            </span>
            <span>{{ formatCrypto(o.amount) }}</span>
          </div>
          <p v-if="!orderBook.sells.length" class="text-slate-500">
            No sell orders.
          </p>
        </div>
      </div>

      <!-- Bids -->
      <div>
        <h3 class="text-emerald-300 font-semibold mb-1">
          Bids (Buy)
        </h3>
        <div class="flex justify-between text-slate-500 mb-1">
          <span>Price</span>
          <span>Amount</span>
        </div>
        <div class="space-y-1 max-h-56 overflow-auto pr-1">
          <div
            v-for="o in orderBook.buys"
            :key="o.id"
            class="flex justify-between text-[11px] text-slate-200"
          >
            <span class="text-emerald-300">
              {{ formatUsd(o.price) }}
            </span>
            <span>{{ formatCrypto(o.amount) }}</span>
          </div>
          <p v-if="!orderBook.buys.length" class="text-slate-500">
            No buy orders.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>