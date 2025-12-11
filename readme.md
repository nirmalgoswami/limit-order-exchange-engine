# 🚀 Limit Order Exchange Engine

A full-stack cryptocurrency paper trading platform built with Laravel and Vue.js, featuring real-time order matching, balance management, and live updates via Pusher.

---


## 🎯 Overview

This project is a paper trading exchange engine that allows users to place limit orders for BTC and ETH cryptocurrencies. The system handles:

- ✅ Real-time order matching
- ✅ Balance and asset management with race condition safety
- ✅ Commission-based trading (1.5% fee)
- ✅ Live order book updates via WebSockets
- ✅ Atomic transaction processing
- ✅ Private user notifications

---

## ✨ Features

### Backend (Laravel API)
- **User Authentication** - Laravel Sanctum for API token management
- **Balance Management** - USD balance tracking with decimal precision
- **Asset Management** - Cryptocurrency holdings with locked amounts
- **Order Processing** - Create, cancel, and match limit orders
- **Commission System** - 1.5% trading fee on all matched orders
- **Real-Time Broadcasting** - Pusher integration for live updates
- **Race Condition Safety** - Database locks and atomic operations
- **Transaction Integrity** - ACID-compliant order matching

### Frontend (Vue.js)
- **Responsive Dashboard** - Clean, modern UI with Tailwind CSS
- **Limit Order Form** - Place buy/sell orders with live validation
- **Order Book Display** - Real-time bid/ask visualization
- **Wallet Overview** - USD and crypto asset balances
- **Order History** - Track open, filled, and cancelled orders
- **Live Updates** - Instant balance and order updates via Pusher
- **Real-Time Notifications** - Success/error messages for all actions

---

## 🛠 Technology Stack

### Backend
```yaml
Framework: Laravel 12.x
Language: PHP 8.2+
Database: MySQL 8.0+
Real-Time: Pusher (Laravel Broadcasting)
Authentication: Laravel Sanctum
Queue: Database driver
Cache: Database driver
```

### Frontend
```yaml
Framework: Vue.js 3.x (Composition API)
Build Tool: Vite
CSS Framework: Tailwind CSS
State Management: Vue Composition API
Real-Time: Pusher JS + Laravel Echo
HTTP Client: Axios
Router: Vue Router
```

---

## 📦 Prerequisites

Before you begin, ensure you have the following installed:

```yaml
Required:
  - PHP: ^8.2
  - Composer: ^2.0
  - Node.js: ^18.0
  - npm: ^9.0
  - MySQL: ^8.0
  - Git: ^2.0

Optional:
  - Laravel Valet/Herd (for local development)
  - MySQL Workbench (for database management)
```

---

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/limit-order-exchange-engine.git
cd limit-order-exchange-engine
```

---

### 2. Backend Setup (Laravel)

#### Step 1: Navigate to backend directory
```bash
cd backend
```

#### Step 2: Install PHP dependencies
```bash
composer install
```

#### Step 3: Create environment file
```bash
cp .env.example .env
```

#### Step 4: Generate application key
```bash
php artisan key:generate
```

#### Step 5: Configure environment variables
Edit `.env` file with your settings:

```env
# Application
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=engine
DB_USERNAME=root
DB_PASSWORD=

# Session & Broadcasting
SESSION_DRIVER=database
BROADCAST_CONNECTION=pusher
QUEUE_CONNECTION=database
CACHE_STORE=database

# Pusher Configuration
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=
PUSHER_USETLS=true

# CORS & Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,localhost:5173
FRONTEND_URL=http://localhost:5173
SESSION_DOMAIN=localhost

# Vite (for frontend integration)
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

#### Step 6: Create database
```bash
mysql -u root -p
CREATE DATABASE engine CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

#### Step 7: Run migrations
```bash
php artisan migrate
```

---

### 3. Frontend Setup (Vue.js)

#### Step 1: Navigate to frontend directory
```bash
cd ../frontend
```

#### Step 2: Install Node dependencies
```bash
npm install
```

#### Step 3: Create environment file
```bash
cp .env.example .env
```

#### Step 4: Configure environment variables
Edit `.env` file:

```env
VITE_PUSHER_APP_KEY=""
VITE_PUSHER_APP_CLUSTER=""
VITE_BACKEND_URL=""
```

**Note:** Update `VITE_BACKEND_URL` to match your Laravel backend URL.

---

## ⚙️ Configuration

### Pusher Setup

1. **Create a Pusher Account** at [pusher.com](https://pusher.com)
2. **Create a new Channels app**
3. **Copy credentials** to both backend and frontend `.env` files:
   - `PUSHER_APP_ID`
   - `PUSHER_APP_KEY`
   - `PUSHER_APP_SECRET`
   - `PUSHER_APP_CLUSTER`

### CORS Configuration

Ensure your Laravel `config/cors.php` allows requests from your frontend:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/auth'],
'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')],
'supports_credentials' => true,
```

---

## 🏃 Running the Application

### Start Backend Server

#### Option 1: PHP Built-in Server
```bash
cd backend
php artisan serve
# Server running at http://localhost:8000
```


### Start Frontend Development Server
```bash
cd frontend
npm run dev
# Server running at http://localhost:5173
```

---


## 💼 Business Logic

### Order Placement

#### Buy Order Process
1. **Validation**: Check if `user.balance >= (price × amount)`
2. **Lock Funds**: Deduct `(price × amount)` from `user.balance`
3. **Create Order**: Insert order with status = 1 (open)
4. **Attempt Match**: Check for matching sell orders
5. **Broadcast**: Notify orderbook subscribers

#### Sell Order Process
1. **Validation**: Check if `asset.amount >= order.amount`
2. **Lock Assets**: Move `amount` from `assets.amount` to `assets.locked_amount`
3. **Create Order**: Insert order with status = 1 (open)
4. **Attempt Match**: Check for matching buy orders
5. **Broadcast**: Notify orderbook subscribers









