# Alagance — Premium E-Commerce Platform

Alagance is a modern, luxury-focused e-commerce web application designed for high-end retail brands. Built with a decoupled architecture featuring a React (Vite) single-page application frontend and a Laravel REST API backend, integrated with Xendit payment gateway and real-time multi-currency support.

---

## ✨ Features

### Storefront (Public)
- **Dynamic 9-Point Hero Slider**: Highly customizable hero banners with auto-play, drag/swipe navigation, smooth cubic-bezier transitions, and 9-point grid positioning (`TL`, `TC`, `TR`, `ML`, `MC`, `MR`, `BL`, `BC`, `BR`) for titles, captions, and CTA buttons.
- **Real-time Multi-Currency Engine**: Live exchange rates via Open ER API (`open.er-api.com`), IP-based currency auto-detection (`ipapi.co`), and instant real-time currency switching without page reloads.
- **Product Discovery & Hover Effects**: Interactive product grid with dual image hover previews and category filtering.
- **Smooth Scrolling & Micro-interactions**: Integrated Lenis smooth scrolling and animated UI feedback.
- **Seamless Cart & Checkout**: Zustand-powered persistent cart state and streamlined checkout flow.
- **Order Tracking**: Guest order tracking via unique tracking tokens and customer order history portal.
- **Xendit Payment Gateway Integration**: Automated payment processing via Xendit invoice links and webhook callbacks.

### Backoffice (Admin Panel)
- **Interactive Hero Banner Manager**: Visual 9-point grid picker to position elements, set slide duration, order, tagline captions, and toggle active status.
- **Product & Category CRUD**: Full inventory management with main image & hover image URL support.
- **Order & Logistics Management**: Manage payment statuses (`PENDING`, `PAID`, `EXPIRED`) and shipping statuses (`pending`, `shipping`, `arrive`) with tracking number & courier assignment.
- **Sales Analytics Dashboard**: Real-time sales statistics, revenue overview, and recent order history formatted in selected administrative currency.

---

## 🛠️ Tech Stack

### Frontend
- **Framework**: [React 19](https://react.dev/) + [Vite 8](https://vitejs.dev/)
- **Routing**: [React Router DOM v7](https://reactrouter.com/)
- **State Management**: [Zustand v5](https://github.com/pmndrs/zustand)
- **Styling**: [Tailwind CSS v4](https://tailwindcss.com/)
- **Smooth Scroll**: [Lenis](https://lenis.darkroom.engineering/)
- **Location & Data**: `@countrystatecity/countries-browser`
- **Toasts**: [Sonner](https://sonner.emilkowal.si/)

### Backend
- **Framework**: [Laravel 11 / 13](https://laravel.com/) (PHP 8.3+)
- **Authentication**: Laravel Sanctum (Token-based API Auth & Middleware)
- **Database**: MySQL / MariaDB
- **Payment Gateway**: [Xendit PHP SDK](https://github.com/xendit/xendit-php)

---

## 📁 Repository Structure

```text
e-commerce_web/
├── laravel/               # Laravel RESTful API backend
│   ├── app/
│   │   ├── Http/Controllers/   # Catalog, Checkout, Admin, HeroBanner controllers
│   │   └── Models/             # Product, Category, Transaction, HeroBanner models
│   ├── database/
│   │   ├── migrations/         # Database migrations
│   │   └── seeders/            # Initial dataset seeders
│   └── routes/api.php          # API routes endpoints
└── react/                 # React Single Page Application frontend
    ├── src/
    │   ├── components/         # HeroSlider, UI components
    │   ├── layouts/            # PublicLayout & AdminLayout
    │   ├── lib/                # Axios instance, Currency helper
    │   ├── pages/              # Storefront & Admin management pages
    │   └── store/              # Zustand cart & currency stores
    └── package.json
```

---

## 🚀 Getting Started

### Prerequisites
- PHP >= 8.3
- Composer
- Node.js >= 18.x & npm
- MySQL / MariaDB Server

---

### 1. Backend Setup (Laravel)

```bash
# Navigate to backend directory
cd laravel

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database credentials in .env
# DB_DATABASE=ecommerce
# DB_USERNAME=root
# DB_PASSWORD=

# Run migrations and seed sample data
php artisan migrate --seed

# Start the Laravel development server
php artisan serve
# Server runs on http://localhost:8000
```

---

### 2. Frontend Setup (React)

```bash
# Open new terminal and navigate to frontend directory
cd react

# Install JavaScript dependencies
npm install

# Start Vite development server
npm run dev
# App runs on http://localhost:5173
```

---

## 🔑 Default Credentials & Admin Access

After running `php artisan db:seed`:

- **Admin Login**: `/login`
  - **Email**: `admin@example.com` (or as configured in database seeders)
  - **Role**: `admin`
- **Storefront**: `/`

---

## 🌐 API Overview

| Method | Endpoint | Access | Description |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/catalog` | Public | Get product catalog |
| `GET` | `/api/hero-banners` | Public | Fetch active hero slider banners |
| `POST` | `/api/checkout` | Public | Process checkout and generate Xendit invoice |
| `POST` | `/api/webhook/xendit` | Public | Webhook endpoint for payment verification |
| `GET` | `/api/admin/hero-banners` | Admin | List all hero banners |
| `POST` | `/api/admin/hero-banners` | Admin | Create new hero banner with 9-point grid pos |
| `GET` | `/api/admin/products` | Admin | Product catalog management |
| `GET` | `/api/admin/transactions` | Admin | Transaction history and order management |

---

## 📄 License

Distributed under the MIT License. See `LICENSE` for more information.
