import { useState, useEffect, useRef } from 'react';
import { Outlet, Link, useNavigate } from 'react-router-dom';
import { useCartStore } from '../store/cart';
import { useCurrencyStore } from '../store/currency';
import { CURRENCIES } from '../lib/currency';

export function PublicLayout() {
  const items = useCartStore((state) => state.items);
  const cartCount = items.reduce((sum, item) => sum + item.quantity, 0);
  const navigate = useNavigate();

  const currentCurrency = useCurrencyStore((state) => state.currentCurrency);
  const setCurrency = useCurrencyStore((state) => state.setCurrency);
  const initCurrency = useCurrencyStore((state) => state.initCurrency);

  const [dropdownOpen, setDropdownOpen] = useState(false);
  const dropdownRef = useRef(null);

  useEffect(() => {
    initCurrency();
  }, [initCurrency]);

  // Close dropdown on click outside
  useEffect(() => {
    function handleClickOutside(e) {
      if (dropdownRef.current && !dropdownRef.current.contains(e.target)) {
        setDropdownOpen(false);
      }
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

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
  const activeCurrencyObj = CURRENCIES[currentCurrency] || CURRENCIES.IDR;

  return (
    <div className="min-h-screen flex flex-col font-sans">
      <header className="fixed w-full top-0 z-50 bg-gallery-white/90 backdrop-blur-md transition-all duration-300 border-b border-gallery-stone/30">
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
            
            {/* Minimalist Editorial Currency Selector */}
            <div className="relative" ref={dropdownRef}>
              <button
                type="button"
                onClick={() => setDropdownOpen(!dropdownOpen)}
                className="flex items-center gap-1.5 text-xs font-semibold tracking-widest text-gallery-ink hover:text-gallery-subtle transition-colors focus:outline-none"
              >
                <span>{activeCurrencyObj.code}</span>
                <span className="text-[10px] text-gallery-subtle">({activeCurrencyObj.symbol})</span>
                <span className={`text-[9px] transition-transform duration-300 ${dropdownOpen ? 'rotate-180' : ''}`}>▾</span>
              </button>

              {dropdownOpen && (
                <div className="absolute right-0 mt-3 w-40 bg-gallery-white border border-gallery-stone shadow-xl py-2 z-50 animate-in fade-in slide-in-from-top-2 duration-200">
                  <div className="px-4 py-1.5 text-[10px] uppercase tracking-widest text-gallery-subtle border-b border-gallery-stone/40 mb-1">
                    Select Currency
                  </div>
                  {Object.values(CURRENCIES).map((c) => (
                    <button
                      key={c.code}
                      type="button"
                      onClick={() => {
                        setCurrency(c.code);
                        setDropdownOpen(false);
                      }}
                      className={`w-full text-left px-4 py-2 text-xs tracking-widest flex items-center justify-between hover:bg-gallery-stone/30 transition-colors ${
                        currentCurrency === c.code ? 'font-bold text-gallery-ink bg-gallery-stone/20' : 'text-gallery-subtle hover:text-gallery-ink'
                      }`}
                    >
                      <span>{c.code}</span>
                      <span className="text-gallery-subtle text-[11px]">{c.symbol}</span>
                    </button>
                  ))}
                </div>
              )}
            </div>
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
