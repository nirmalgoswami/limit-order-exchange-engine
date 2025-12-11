<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import api, { setAuthToken } from '../lib/http';
import { echo } from '../lib/echo';
import TopBar from '../components/TopBar.vue';
import AlertMessages from '../components/AlertMessages.vue';
import WalletCard from '../components/WalletCard.vue';
import LimitOrderForm from '../components/LimitOrderForm.vue';
import OrderBook from '../components/OrderBook.vue';
import MyOrders from '../components/MyOrders.vue';

const router = useRouter();
const route = useRoute();

const loading = ref(false);
const user = ref(null);
const profile = ref({
  usd_balance: 0,
  assets: [],
});

const symbols = ['BTC', 'ETH'];
const selectedSymbol = ref('BTC');

const orderBook = ref({
  buys: [],
  sells: [],
});
const myOrders = ref([]);

const errorMessage = ref('');
const successMessage = ref('');

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

async function handleOrderPlaced() {
  successMessage.value = 'Order placed successfully.';
  await Promise.all([
    fetchProfile(),
    fetchOrderBook(),
    fetchMyOrders(),
  ]);
}

function handleOrderError(message) {
  errorMessage.value = message;
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

let privateUserChannel = null;

function setupEchoListeners() {
  if (!echo || !user.value?.id) return;

  const channelName = `user.${user.value.id}`;

  if (!privateUserChannel) {
    privateUserChannel = echo
      .private(channelName)
      .listen('.OrderMatched', async () => {
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

watch(user, (newUser) => {
  if (newUser?.id) {
    setupEchoListeners();
  }
});
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-slate-100">
    <TopBar 
      :user="user" 
      :balance="profile.usd_balance" 
      @logout="logout" 
    />

    <main class="max-w-6xl mx-auto px-4 py-6 space-y-6">
      <AlertMessages 
        :error="errorMessage" 
        :success="successMessage" 
        @clear-error="errorMessage = ''"
        @clear-success="successMessage = ''"
      />

      <section class="grid md:grid-cols-3 gap-4">
        <WalletCard 
          :balance="profile.usd_balance" 
          :assets="profile.assets" 
        />

        <div class="md:col-span-2 space-y-4">
          <LimitOrderForm
            v-model:symbol="selectedSymbol"
            :symbols="symbols"
            @order-placed="handleOrderPlaced"
            @order-error="handleOrderError"
          />

          <section class="grid md:grid-cols-2 gap-4">
            <OrderBook 
              :symbol="selectedSymbol" 
              :order-book="orderBook" 
            />
            <MyOrders 
              :orders="myOrders" 
              @cancel-order="cancelOrder" 
            />
          </section>
        </div>
      </section>
    </main>
  </div>
</template>