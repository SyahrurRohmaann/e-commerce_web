import { useEffect } from 'react';
import Lenis from 'lenis';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { Toaster } from 'sonner';
import { PublicLayout } from './layouts/PublicLayout';
import { AdminLayout } from './layouts/AdminLayout';
import { Home } from './pages/Home';
import { Cart } from './pages/Cart';
import { Checkout } from './pages/Checkout';
import { Login } from './pages/Login';
import { Register } from './pages/Register';
import { Dashboard } from './pages/Dashboard';
import { AdminCategories } from './pages/AdminCategories';
import { AdminProducts } from './pages/AdminProducts';
import { AdminAnnouncements } from './pages/AdminAnnouncements';
import { AdminTransactions } from './pages/AdminTransactions';
import { AdminHeroBanners } from './pages/AdminHeroBanners';
import { OrderStatus } from './pages/OrderStatus';
import { CheckoutSuccess } from './pages/CheckoutSuccess';
import { CheckoutFailure } from './pages/CheckoutFailure';
import { Profile } from './pages/Profile';
import { TrackOrder } from './pages/TrackOrder';
import { Forbidden } from './pages/Forbidden';

function App() {
  useEffect(() => {
    const lenis = new Lenis();

    function raf(time) {
      lenis.raf(time);
      requestAnimationFrame(raf);
    }

    requestAnimationFrame(raf);
    
    return () => {
      lenis.destroy();
    };
  }, []);

  return (
    <BrowserRouter>
      <Toaster position="top-center" toastOptions={{
        className: 'font-sans rounded-none border border-gallery-stone',
        style: {
          background: 'var(--color-gallery-white)',
          color: 'var(--color-gallery-ink)',
        },
      }} />
      <Routes>
        <Route element={<PublicLayout />}>
          <Route path="/" element={<Home />} />
          <Route path="/cart" element={<Cart />} />
          <Route path="/checkout" element={<Checkout />} />
          <Route path="/checkout/success" element={<CheckoutSuccess />} />
          <Route path="/checkout/failure" element={<CheckoutFailure />} />
          <Route path="/orders/:id" element={<OrderStatus />} />
          <Route path="/order/:id" element={<OrderStatus />} />
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />
          <Route path="/profile" element={<Profile />} />
          <Route path="/track-order" element={<TrackOrder />} />
          <Route path="/forbidden" element={<Forbidden />} />
        </Route>
        
        <Route element={<AdminLayout />}>
          <Route path="/admin" element={<Dashboard />} />
          <Route path="/admin/categories" element={<AdminCategories />} />
          <Route path="/admin/products" element={<AdminProducts />} />
          <Route path="/admin/announcements" element={<AdminAnnouncements />} />
          <Route path="/admin/transactions" element={<AdminTransactions />} />
          <Route path="/admin/hero-banners" element={<AdminHeroBanners />} />
        </Route>
      </Routes>
    </BrowserRouter>
  );
}

export default App;
