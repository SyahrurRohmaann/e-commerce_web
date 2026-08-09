import { Outlet, Navigate, Link } from 'react-router-dom';

export function AdminLayout() {
  const token = localStorage.getItem('token');
  const role = localStorage.getItem('role');

  if (!token || role !== 'admin') {
    return <Navigate to="/login" replace />;
  }

  const handleLogout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('role');
    window.location.href = '/login';
  };

  return (
    <div className="min-h-screen bg-gallery-stone/30 font-sans flex">
      {/* Sidebar */}
      <aside className="w-64 bg-gallery-white border-r border-gallery-stone flex flex-col fixed h-full">
        <div className="h-20 flex items-center px-6 border-b border-gallery-stone">
          <h2 className="font-serif tracking-widest text-lg">BACKOFFICE</h2>
        </div>
        <nav className="flex-1 py-8 px-4 flex flex-col gap-2">
          <Link to="/admin" className="px-4 py-3 text-sm uppercase tracking-widest hover:bg-gallery-stone/50 transition-colors">Dashboard</Link>
          <Link to="/" className="px-4 py-3 text-sm uppercase tracking-widest hover:bg-gallery-stone/50 transition-colors">Storefront</Link>
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