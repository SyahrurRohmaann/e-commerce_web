import { Outlet, Link, useNavigate } from 'react-router-dom';
import { useCartStore } from '../store/cart';

export function PublicLayout() {
  const items = useCartStore((state) => state.items);
  const cartCount = items.reduce((sum, item) => sum + item.quantity, 0);
  const navigate = useNavigate();

  const handleAuthClick = (e) => {
    e.preventDefault();
    const token = localStorage.getItem('token');
    const role = localStorage.getItem('role');

    if (!token) {
      navigate('/login');
      return;
    }

    if (role === 'admin') {
      navigate('/admin');
    } else {
      navigate('/profile');
    }
  };

  const getAuthLabel = () => {
    const token = localStorage.getItem('token');
    const role = localStorage.getItem('role');
    
    if (!token) return 'Login';
    if (role === 'admin') return 'Dashboard';
    return 'Profile';
  };

  const role = localStorage.getItem('role');

  return (
    <div className="min-h-screen flex flex-col font-sans">
      <header className="fixed w-full top-0 z-50 bg-gallery-white/80 backdrop-blur-md  transition-all duration-300">
        <div className="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
          <Link to="/" className="text-2xl font-serif tracking-wide text-gallery-ink hover:opacity-70 transition-opacity">
            ALAGANCE
          </Link>
          <nav className="flex items-center gap-8 text-sm tracking-widest uppercase">
            <Link to="/" className="text-gallery-ink hover:text-gallery-subtle transition-colors">Catalog</Link>
            <Link to="/cart" className="text-gallery-ink hover:text-gallery-subtle transition-colors flex items-center gap-2">
              Cart {cartCount > 0 && <span className="bg-gallery-ink text-gallery-white text-xs px-2 py-0.5 rounded-full">{cartCount}</span>}
            </Link>
            {role !== 'admin' && (
              <Link to="/track-order" className="text-gallery-ink hover:text-gallery-subtle transition-colors">Track Order</Link>
            )}
            <a href="#" onClick={handleAuthClick} className="text-gallery-subtle hover:text-gallery-ink transition-colors">{getAuthLabel()}</a>
          </nav>
        </div>
      </header>
      <main className="flex-grow mt-20">
        <Outlet />
      </main>
      <footer className="border-t border-gallery-stone py-12 text-center text-sm text-gallery-subtle mt-24">
        <p>&copy; {new Date().getFullYear()} AlAgance. All rights reserved.</p>
      </footer>
    </div>
  );
}