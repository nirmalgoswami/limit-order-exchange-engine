<script setup>
import { formatUsd, formatCrypto } from '../lib/formatters';

defineProps({
  balance: Number,
  assets: Array,
});
</script>

<template>
  <div class="md:col-span-1">
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4 space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold">Wallet</h2>
        <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-300">
          Live
        </span>
      </div>

      <div class="space-y-2 text-xs">
        <div class="flex justify-between">
          <span class="text-slate-400">USD Balance</span>
          <span class="font-semibold">
            {{ formatUsd(balance) }}
          </span>
        </div>
      </div>

      <div class="mt-3 border-t border-slate-800 pt-3">
        <h3 class="text-xs font-semibold mb-2 text-slate-300">
          Assets
        </h3>
        <div v-if="assets && assets.length" class="space-y-2 text-xs">
          <div
            v-for="asset in assets"
            :key="asset.symbol"
            class="flex items-center justify-between text-slate-200"
          >
            <div class="flex flex-col">
              <span class="font-semibold">{{ asset.symbol }}</span>
              <span class="text-[11px] text-slate-400">
                Available: {{ formatCrypto(asset.amount) }}
              </span>
            </div>
            <div class="text-right">
              <div class="text-[11px] text-slate-400">
                Locked: {{ formatCrypto(asset.locked_amount) }}
              </div>
            </div>
          </div>
        </div>
        <p v-else class="text-xs text-slate-500">
          No assets yet. Place an order to get started.
        </p>
      </div>
    </div>
  </div>
</template>