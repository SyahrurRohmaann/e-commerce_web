import { useState, useEffect, useRef } from 'react';
import { Outlet, Navigate, Link, useLocation } from 'react-router-dom';
import { useCurrencyStore } from '../store/currency';
import { CURRENCIES } from '../lib/currency';

export function AdminLayout() {
  const token = localStorage.getItem('token');
  const role = localStorage.getItem('role');
  const location = useLocation();

  const currentCurrency = useCurrencyStore((state) => state.currentCurrency);
  const setCurrency = useCurrencyStore((state) => state.setCurrency);
  const initCurrency = useCurrencyStore((state) => state.initCurrency);

  const [dropdownOpen, setDropdownOpen] = useState(false);
  const dropdownRef = useRef(null);

  useEffect(() => {
    initCurrency();
  }, [initCurrency]);

  useEffect(() => {
    function handleClickOutside(e) {
      if (dropdownRef.current && !dropdownRef.current.contains(e.target)) {
        setDropdownOpen(false);
      }
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  if (!token) {
    return <Navigate to="/login" replace />;
  }
  
  if (role !== 'admin') {
    return <Navigate to="/forbidden" replace />;
  }

  const handleLogout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('role');
    window.location.href = '/login';
  };

  const navItemClass = (path) => {
    const isActive = location.pathname === path || (path !== '/admin' && location.pathname.startsWith(path));
    return `px-4 py-3 text-sm uppercase tracking-widest transition-colors ${isActive ? 'bg-gallery-stone/50 font-medium' : 'hover:bg-gallery-stone/50'}`;
  };

  const activeCurrencyObj = CURRENCIES[currentCurrency] || CURRENCIES.IDR;

  return (
    <div className="min-h-screen bg-gallery-stone/30 font-sans flex">
      {/* Sidebar */}
      <aside className="w-64 bg-gallery-white border-r border-gallery-stone flex flex-col fixed h-full">
        <div className="h-20 flex items-center justify-between px-6 border-b border-gallery-stone">
          <h2 className="font-serif tracking-widest text-lg">BACKOFFICE</h2>
          
          {/* Admin Currency Selector */}
          <div className="relative" ref={dropdownRef}>
            <button
              type="button"
              onClick={() => setDropdownOpen(!dropdownOpen)}
              className="flex items-center gap-1 text-[11px] font-bold tracking-widest text-gallery-ink hover:text-gallery-subtle transition-colors focus:outline-none bg-gallery-stone/30 px-2 py-1 rounded"
            >
              <span>{activeCurrencyObj.code}</span>
              <span className={`text-[8px] transition-transform duration-300 ${dropdownOpen ? 'rotate-180' : ''}`}>▾</span>
            </button>

            {dropdownOpen && (
              <div className="absolute right-0 mt-2 w-36 bg-gallery-white border border-gallery-stone shadow-xl py-1 z-50 animate-in fade-in duration-150">
                {Object.values(CURRENCIES).map((c) => (
                  <button
                    key={c.code}
                    type="button"
                    onClick={() => {
                      setCurrency(c.code);
                      setDropdownOpen(false);
                    }}
                    className={`w-full text-left px-3 py-1.5 text-xs tracking-widest flex items-center justify-between hover:bg-gallery-stone/30 transition-colors ${
                      currentCurrency === c.code ? 'font-bold text-gallery-ink bg-gallery-stone/20' : 'text-gallery-subtle hover:text-gallery-ink'
                    }`}
                  >
                    <span>{c.code}</span>
                    <span className="text-gallery-subtle text-[10px]">{c.symbol}</span>
                  </button>
                ))}
              </div>
            )}
          </div>
        </div>
        <nav className="flex-1 py-8 px-4 flex flex-col gap-2">
          <Link to="/admin" className={navItemClass('/admin')}>Dashboard</Link>
          <Link to="/admin/categories" className={navItemClass('/admin/categories')}>Categories</Link>
          <Link to="/admin/products" className={navItemClass('/admin/products')}>Products</Link>
          <Link to="/admin/announcements" className={navItemClass('/admin/announcements')}>Announcements</Link>
          <Link to="/admin/transactions" className={navItemClass('/admin/transactions')}>Transactions</Link>
          <Link to="/admin/hero-banners" className={navItemClass('/admin/hero-banners')}>Hero Banners</Link>
          <Link to="/" className="px-4 py-3 text-sm uppercase tracking-widest hover:bg-gallery-stone/50 transition-colors mt-8 border-t border-gallery-stone pt-6">Storefront</Link>
        </nav>
        <div className="p-4 border-t border-gallery-stone">
          <button 
            onClick={handleLogout}
            className="w-full px-4 py-3 text-sm uppercase tracking-widest text-left hover:text-red-600 transition-colors"
          >
            Sign Out
          </button>
        </div>
      </aside>

      {/* Main Content */}
      <main className="flex-1 ml-64 p-12">
        <Outlet />
      </main>
    </div>
  );
}