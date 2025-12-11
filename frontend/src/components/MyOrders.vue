<script setup>
import { formatUsd, formatCrypto } from '../lib/formatters';

defineProps({
  orders: Array,
});

defineEmits(['cancel-order']);
</script>

<template>
  <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
    <div class="flex items-center justify-between mb-2">
      <h2 class="text-sm font-semibold">
        My Orders
      </h2>
    </div>

    <div class="text-[11px] max-h-64 overflow-auto">
      <table class="w-full border-collapse">
        <thead class="text-slate-400 text-[10px] border-b border-slate-800">
          <tr>
            <th class="py-1.5 text-left font-normal">Side</th>
            <th class="py-1.5 text-left font-normal">Symbol</th>
            <th class="py-1.5 text-right font-normal">Price</th>
            <th class="py-1.5 text-right font-normal">Amount</th>
            <th class="py-1.5 text-center font-normal">Status</th>
            <th class="py-1.5 text-right font-normal">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="o in orders"
            :key="o.id"
            class="border-b border-slate-900/60"
          >
            <td class="py-1.5">
              <span
                class="px-1.5 py-0.5 rounded-full text-[10px] font-medium"
                :class="o.side === 'buy'
                  ? 'bg-emerald-500/15 text-emerald-300'
                  : 'bg-rose-500/15 text-rose-300'"
              >
                {{ o.side }}
              </span>
            </td>
            <td class="py-1.5">
              {{ o.symbol }}
            </td>
            <td class="py-1.5 text-right">
              {{ formatUsd(o.price) }}
            </td>
            <td class="py-1.5 text-right">
              {{ formatCrypto(o.amount) }}
            </td>
            <td class="py-1.5 text-center">
              <span
                class="px-1.5 py-0.5 rounded-full text-[10px]"
                :class="{
                  'bg-blue-500/15 text-blue-300': o.status === 1 || o.status === 'open',
                  'bg-emerald-500/15 text-emerald-300': o.status === 2 || o.status === 'filled',
                  'bg-slate-600/30 text-slate-200': o.status === 3 || o.status === 'cancelled',
                }"
              >
                <span v-if="o.status === 1 || o.status === 'open'">Open</span>
                <span v-else-if="o.status === 2 || o.status === 'filled'">Filled</span>
                <span v-else-if="o.status === 3 || o.status === 'cancelled'">Cancelled</span>
                <span v-else>{{ o.status }}</span>
              </span>
            </td>
            <td class="py-1.5 text-right">
              <button
                v-if="o.status === 1 || o.status === 'open'"
                type="button"
                class="text-[10px] px-2 py-1 rounded border border-slate-700 hover:bg-slate-800"
                @click="$emit('cancel-order', o.id)"
              >
                Cancel
              </button>
            </td>
          </tr>
          <tr v-if="!orders.length">
            <td
              colspan="6"
              class="py-4 text-center text-slate-500"
            >
              No orders yet.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>