import { useEffect } from 'react';
import Lenis from 'lenis';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { PublicLayout } from './layouts/PublicLayout';
import { AdminLayout } from './layouts/AdminLayout';
import { Home } from './pages/Home';
import { Cart } from './pages/Cart';
import { Checkout } from './pages/Checkout';
import { Login } from './pages/Login';
import { Dashboard } from './pages/Dashboard';
import { AdminCategories } from './pages/AdminCategories';
import { AdminProducts } from './pages/AdminProducts';
import { AdminTransactions } from './pages/AdminTransactions';
import { OrderStatus } from './pages/OrderStatus';
import { CheckoutSuccess } from './pages/CheckoutSuccess';
import { Profile } from './pages/Profile';

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
      <Routes>
        <Route element={<PublicLayout />}>
          <Route path="/" element={<Home />} />
          <Route path="/cart" element={<Cart />} />
          <Route path="/checkout" element={<Checkout />} />
          <Route path="/checkout/success" element={<CheckoutSuccess />} />
          <Route path="/orders/:id" element={<OrderStatus />} />
          <Route path="/login" element={<Login />} />
          <Route path="/profile" element={<Profile />} />
        </Route>
        
        <Route element={<AdminLayout />}>
          <Route path="/admin" element={<Dashboard />} />
          <Route path="/admin/categories" element={<AdminCategories />} />
          <Route path="/admin/products" element={<AdminProducts />} />
          <Route path="/admin/transactions" element={<AdminTransactions />} />
        </Route>
      </Routes>
    </BrowserRouter>
  );
}

export default App;
