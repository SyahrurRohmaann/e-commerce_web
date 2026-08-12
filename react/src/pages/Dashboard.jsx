import { useState, useEffect } from 'react';
import api from '../lib/axios';
import { useCurrencyStore } from '../store/currency';

export function Dashboard() {
  const [categories, setCategories] = useState([]);
  const [products, setProducts] = useState([]);
  const [transactions, setTransactions] = useState([]);
  const [totalOrders, setTotalOrders] = useState(0);
  const [totalProducts, setTotalProducts] = useState(0);
  const [loading, setLoading] = useState(true);

  const currentCurrency = useCurrencyStore((state) => state.currentCurrency);
  const format = useCurrencyStore((state) => state.format);

  useEffect(() => {
    fetchData();
  }, []);

  const fetchData = async () => {
    try {
      const [catRes, prodRes, trxRes] = await Promise.all([
        api.get('/admin/categories'),
        api.get('/admin/products?per_page=100'),
        api.get('/admin/transactions?per_page=100')
      ]);
      setCategories(catRes.data.data);
      setProducts(prodRes.data.data);
      setTotalProducts(prodRes.data.total);
      setTransactions(trxRes.data.data);
      setTotalOrders(trxRes.data.total);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  if (loading) return <div className="text-sm tracking-widest uppercase text-gallery-subtle">Loading data...</div>;

  const totalSales = transactions.filter(t => t.status === 'PAID').reduce((sum, t) => sum + parseFloat(t.total_amount), 0);

  return (
    <div className="animate-in fade-in duration-500 max-w-6xl">
      <h1 className="text-3xl font-serif mb-12">Overview</h1>
      
      {/* Stats Cards */}
      <div className="grid grid-cols-3 gap-6 mb-16">
        <div className="bg-gallery-white p-8 border border-gallery-stone">
          <p className="text-xs uppercase tracking-widest text-gallery-subtle mb-4">Total Revenue</p>
          <p className="text-3xl font-serif">{format(totalSales)}</p>
        </div>
        <div className="bg-gallery-white p-8 border border-gallery-stone">
          <p className="text-xs uppercase tracking-widest text-gallery-subtle mb-4">Total Orders</p>
          <p className="text-3xl font-serif">{totalOrders}</p>
        </div>
        <div className="bg-gallery-white p-8 border border-gallery-stone">
          <p className="text-xs uppercase tracking-widest text-gallery-subtle mb-4">Active Catalog</p>
          <p className="text-3xl font-serif">{totalProducts}</p>
        </div>
      </div>

      <div className="grid grid-cols-1 xl:grid-cols-2 gap-12">
        {/* Transactions Table */}
        <div>
          <div className="flex justify-between items-end mb-6">
            <h2 className="text-xl font-serif">Recent Orders</h2>
          </div>
          <div className="bg-gallery-white border border-gallery-stone overflow-hidden">
            <table className="w-full text-left text-sm">
              <thead className="bg-gallery-stone/30 border-b border-gallery-stone text-xs uppercase tracking-widest text-gallery-subtle">
                <tr>
                  <th className="px-6 py-4 font-normal">Order</th>
                  <th className="px-6 py-4 font-normal">Amount</th>
                  <th className="px-6 py-4 font-normal">Status</th>
                  <th className="px-6 py-4 font-normal text-right">Invoice</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gallery-stone">
                {transactions.slice(0, 5).map(t => (
                  <tr key={t.id} className="hover:bg-gallery-stone/10 transition-colors">
                    <td className="px-6 py-4 font-mono text-xs">#{t.id.toString().padStart(4, '0')}</td>
                    <td className="px-6 py-4 font-medium">{format(t.total_amount)}</td>
                    <td className="px-6 py-4">
                      <span className={`px-2 py-1 text-[10px] tracking-widest uppercase rounded-sm ${
                        t.status === 'PAID' ? 'bg-green-100 text-green-800' :
                        t.status === 'PENDING' ? 'bg-yellow-100 text-yellow-800' :
                        'bg-red-100 text-red-800'
                      }`}>
                        {t.status}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-right">
                      {t.invoice_url ? (
                        <a href={t.invoice_url} target="_blank" rel="noreferrer" className="text-xs uppercase tracking-widest text-blue-600 hover:underline">View</a>
                      ) : '-'}
                    </td>
                  </tr>
                ))}
                {transactions.length === 0 && (
                  <tr><td colSpan="4" className="px-6 py-8 text-center text-gallery-subtle text-xs uppercase tracking-widest">No orders yet</td></tr>
                )}
              </tbody>
            </table>
          </div>
        </div>

        {/* Products Table */}
        <div>
          <div className="flex justify-between items-end mb-6">
            <h2 className="text-xl font-serif">Inventory</h2>
          </div>
          <div className="bg-gallery-white border border-gallery-stone overflow-hidden">
            <table className="w-full text-left text-sm">
              <thead className="bg-gallery-stone/30 border-b border-gallery-stone text-xs uppercase tracking-widest text-gallery-subtle">
                <tr>
                  <th className="px-6 py-4 font-normal">Product</th>
                  <th className="px-6 py-4 font-normal">Price</th>
                  <th className="px-6 py-4 font-normal">Stock</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gallery-stone">
                {products.map(p => (
                  <tr key={p.id} className="hover:bg-gallery-stone/10 transition-colors">
                    <td className="px-6 py-4">
                      <p>{p.name}</p>
                      <p className="text-xs text-gallery-subtle uppercase tracking-widest mt-1">{p.category?.name}</p>
                    </td>
                    <td className="px-6 py-4 font-medium">{format(p.price)}</td>
                    <td className="px-6 py-4">
                      <span className={p.stock < 5 ? 'text-red-600 font-medium' : ''}>{p.stock}</span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
}