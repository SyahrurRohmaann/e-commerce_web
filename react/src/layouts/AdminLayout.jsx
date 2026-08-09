import { Outlet, Navigate, Link, useLocation } from 'react-router-dom';

export function AdminLayout() {
  const token = localStorage.getItem('token');
  const role = localStorage.getItem('role');
  const location = useLocation();

  if (!token || role !== 'admin') {
    return <Navigate to="/login" replace />;
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

  return (
    <div className="min-h-screen bg-gallery-stone/30 font-sans flex">
      {/* Sidebar */}
      <aside className="w-64 bg-gallery-white border-r border-gallery-stone flex flex-col fixed h-full">
        <div className="h-20 flex items-center px-6 border-b border-gallery-stone">
          <h2 className="font-serif tracking-widest text-lg">BACKOFFICE</h2>
        </div>
        <nav className="flex-1 py-8 px-4 flex flex-col gap-2">
          <Link to="/admin" className={navItemClass('/admin')}>Dashboard</Link>
          <Link to="/admin/categories" className={navItemClass('/admin/categories')}>Categories</Link>
          <Link to="/admin/products" className={navItemClass('/admin/products')}>Products</Link>
          <Link to="/admin/transactions" className={navItemClass('/admin/transactions')}>Transactions</Link>
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