<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import api, { setAuthToken } from '../lib/http';
import { echo } from '../lib/echo';

const router = useRouter();
const route = useRoute();

const loading = ref(false);
const placingOrder = ref(false);

const user = ref(null);
const profile = ref({
  usd_balance: 0,
  assets: [],
});

const symbols = ['BTC', 'ETH'];
const selectedSymbol = ref('BTC');
const side = ref('buy');
const price = ref('');
const amount = ref('');

const orderBook = ref({
  buys: [],
  sells: [],
});
const myOrders = ref([]);

const errorMessage = ref('');
const successMessage = ref('');

let privateUserChannel = null;
let testChannel = null;

const quoteVolume = computed(() => {
  const p = parseFloat(price.value || '0');
  const a = parseFloat(amount.value || '0');
  if (!p || !a) return '0.00';
  return (p * a).toFixed(2);
});

function handleAuthError(err) {
  if (err.response && err.response.status === 401) {
    localStorage.removeItem('auth_token');
    setAuthToken(null);
    router.replace({
      name: 'login',
      query: { redirect: route.fullPath },
    });
  }
}

async function fetchMe() {
  try {
    const { data } = await api.get('/me');
    user.value = data;
  } catch (err) {
    console.error(err);
    handleAuthError(err);
  }
}

async function fetchProfile() {
  try {
    loading.value = true;
    const { data } = await api.get('/profile');

    profile.value = {
      usd_balance: Number(data.user?.balance ?? 0),
      assets: data.assets ?? [],
    };
  } catch (err) {
    console.error(err);
    handleAuthError(err);
  } finally {
    loading.value = false;
  }
}

async function fetchOrderBook() {
  try {
    const { data } = await api.get('/orders', {
      params: { symbol: selectedSymbol.value },
    });

    const all = Array.isArray(data) ? data : data?.orders || [];

    orderBook.value = {
      buys: all.filter((o) => o.side === 'buy'),
      sells: all.filter((o) => o.side === 'sell'),
    };
  } catch (err) {
    console.error(err);
    handleAuthError(err);
  }
}

async function fetchMyOrders() {
  try {
    const { data } = await api.get('/my-orders');
    myOrders.value = Array.isArray(data) ? data : data?.orders || [];
  } catch (err) {
    console.error(err);
    handleAuthError(err);
  }
}

async function placeOrder() {
  errorMessage.value = '';
  successMessage.value = '';

  if (!price.value || !amount.value) {
    errorMessage.value = 'Price and amount are required.';
    return;
  }

  try {
    placingOrder.value = true;
    const payload = {
      symbol: selectedSymbol.value,
      side: side.value,
      price: price.value,
      amount: amount.value,
    };

    await api.post('/orders', payload);

    successMessage.value = 'Order placed successfully.';
    price.value = '';
    amount.value = '';

    await Promise.all([
      fetchProfile(),
      fetchOrderBook(),
      fetchMyOrders(),
    ]);
  } catch (err) {
    console.error(err);
    if (err.response?.data?.message) {
      errorMessage.value = err.response.data.message;
    } else {
      errorMessage.value = 'Failed to place order.';
    }
    handleAuthError(err);
  } finally {
    placingOrder.value = false;
  }
}

async function cancelOrder(orderId) {
  errorMessage.value = '';
  successMessage.value = '';

  try {
    await api.post(`/orders/${orderId}/cancel`);
    successMessage.value = 'Order cancelled.';
    await Promise.all([
      fetchProfile(),
      fetchOrderBook(),
      fetchMyOrders(),
    ]);
  } catch (err) {
    console.error(err);
    if (err.response?.data?.message) {
      errorMessage.value = err.response.data.message;
    } else {
      errorMessage.value = 'Failed to cancel order.';
    }
    handleAuthError(err);
  }
}

async function logout() {
  try {
    await api.post('/logout');
  } catch (err) {
    console.error(err);
  } finally {
    localStorage.removeItem('auth_token');
    setAuthToken(null);
    router.replace({ name: 'login' });
  }
}


function setupEchoListeners() {
  if (!echo) {
    console.warn('Echo instance not available');
    return;
  }

  console.log('Echo / Pusher config (frontend):', {
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
  });

  // log when pusher connects
  if (echo.connector?.pusher?.connection) {
    echo.connector.pusher.connection.bind('connected', () => {
      //console.log('Pusher connected');
    });
  }

  // 2) Private user channel for OrderMatched
  if (!user.value?.id) {
    return;
  }

  const channelName = `user.${user.value.id}`;
  //console.log('Subscribing to private channel:', channelName);

  if (!privateUserChannel) {
    privateUserChannel = echo
      .private(channelName)
      .subscribed(() => {
        //console.log('Subscribed to', channelName);
      })
      
      .listen('.OrderMatched', async (event) => {
        //console.log('OrderMatched event received:', event);
        

        // refresh UI
        await Promise.all([
          fetchProfile(),
          fetchOrderBook(),
          fetchMyOrders(),
        ]);

        successMessage.value = 'Order matched!';
        setTimeout(() => {
          if (successMessage.value === 'Order matched!') {
            successMessage.value = '';
          }
        }, 3000);
      });
  }
}

onMounted(async () => {
  await fetchMe();
  await Promise.all([
    fetchProfile(),
    fetchOrderBook(),
    fetchMyOrders(),
  ]);

  setupEchoListeners();
});

watch(selectedSymbol, () => {
  fetchOrderBook();
});

// when user gets loaded later, attach private channel then
watch(user, (newUser) => {
  if (newUser?.id) {
    setupEchoListeners();
  }
});
</script>


<template>
  <div class="min-h-screen bg-slate-950 text-slate-100">
    <!-- Top Bar -->
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
              USD: {{ Number(profile.usd_balance || 0).toFixed(2) }}
            </div>
          </div>
          <button
            type="button"
            class="inline-flex items-center text-xs px-3 py-1.5 rounded-lg border border-slate-700 hover:bg-slate-800 transition"
            @click="logout"
          >
            Logout
          </button>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-4 py-6 space-y-6">
      <!-- Alerts -->
      <div v-if="errorMessage" class="text-sm rounded-lg border border-red-500/40 bg-red-500/10 px-3 py-2 text-red-200">
        {{ errorMessage }}
      </div>
      <div v-if="successMessage" class="text-sm rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-3 py-2 text-emerald-200">
        {{ successMessage }}
      </div>

      <!-- Wallet Overview -->
      <section class="grid md:grid-cols-3 gap-4">
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
                  {{ Number(profile.usd_balance || 0).toFixed(2) }}
                </span>
              </div>
            </div>

            <div class="mt-3 border-t border-slate-800 pt-3">
              <h3 class="text-xs font-semibold mb-2 text-slate-300">
                Assets
              </h3>
              <div v-if="profile.assets && profile.assets.length" class="space-y-2 text-xs">
                <div
                  v-for="asset in profile.assets"
                  :key="asset.symbol"
                  class="flex items-center justify-between text-slate-200"
                >
                  <div class="flex flex-col">
                    <span class="font-semibold">{{ asset.symbol }}</span>
                    <span class="text-[11px] text-slate-400">
                      Available: {{ asset.amount }}
                    </span>
                  </div>
                  <div class="text-right">
                    <div class="text-[11px] text-slate-400">
                      Locked: {{ asset.locked_amount }}
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

        <!-- Limit Order + Orders -->
        <div class="md:col-span-2 space-y-4">
          <!-- Limit Order Form -->
          <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4 space-y-4">
            <div class="flex items-center justify-between">
              <h2 class="text-sm font-semibold">
                Place Limit Order
              </h2>
              <div class="flex gap-2 items-center">
                <label class="text-xs text-slate-400">Symbol</label>
                <select
                  v-model="selectedSymbol"
                  class="text-xs rounded-lg border border-slate-700 bg-slate-950 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-sky-500"
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
                <label class="block text-[11px] text-slate-400">Amount ({{ selectedSymbol }})</label>
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

          <!-- Orderbook + My Orders -->
          <section class="grid md:grid-cols-2 gap-4">
            <!-- Orderbook -->
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
              <div class="flex items-center justify-between mb-2">
                <h2 class="text-sm font-semibold">
                  Orderbook — {{ selectedSymbol }}
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
                        {{ o.price }}
                      </span>
                      <span>{{ o.amount }}</span>
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
                        {{ o.price }}
                      </span>
                      <span>{{ o.amount }}</span>
                    </div>
                    <p v-if="!orderBook.buys.length" class="text-slate-500">
                      No buy orders.
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- My Orders -->
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
                      v-for="o in myOrders"
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
                        {{ o.price }}
                      </td>
                      <td class="py-1.5 text-right">
                        {{ o.amount }}
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
                          @click="cancelOrder(o.id)"
                        >
                          Cancel
                        </button>
                      </td>
                    </tr>
                    <tr v-if="!myOrders.length">
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
          </section>
        </div>
      </section>
    </main>
  </div>
</template>
