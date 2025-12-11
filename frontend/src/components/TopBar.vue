<script setup>
import { formatUsd } from '../lib/formatters';

defineProps({
  user: Object,
  balance: Number,
});

defineEmits(['logout']);
</script>

<template>
  <header class="border-b border-slate-800 bg-slate-900/70 backdrop-blur">
    <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between gap-4">
      <div>
        <h1 class="text-lg font-semibold tracking-tight">
          Limit Order Exchange
        </h1>
        <p class="text-xs text-slate-400">
          Simple BTC/ETH paper trading engine
        </p>
      </div>
      <div class="flex items-center gap-3">
        <div class="text-right text-xs">
          <div class="font-medium">
            {{ user?.name || user?.email || 'User' }}
          </div>
          <div class="text-slate-400">
            USD: {{ formatUsd(balance) }}
          </div>
        </div>
        <button
          type="button"
          class="inline-flex items-center text-xs px-3 py-1.5 rounded-lg border border-slate-700 hover:bg-slate-800 transition"
          @click="$emit('logout')"
        >
          Logout
        </button>
      </div>
    </div>
  </header>
</template>