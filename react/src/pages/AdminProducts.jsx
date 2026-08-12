import { useState, useEffect } from 'react';
import { toast } from 'sonner';
import api from '../lib/axios';
import { useCurrencyStore } from '../store/currency';

export function AdminProducts() {
  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [editing, setEditing] = useState(null);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [form, setForm] = useState({
    category_id: '', name: '', description: '', price: '', stock: '', image_url: '', hover_image_url: ''
  });

  const currentCurrency = useCurrencyStore((state) => state.currentCurrency);
  const format = useCurrencyStore((state) => state.format);

  useEffect(() => { fetchData(page); }, [page]);

  const fetchData = async (page) => {
    try {
      const [prodRes, catRes] = await Promise.all([
        api.get(`/admin/products?page=${page}`),
        api.get('/admin/categories')
      ]);
      setProducts(prodRes.data.data);
      setPage(prodRes.data.current_page);
      setLastPage(prodRes.data.last_page);
      setTotal(prodRes.data.total);
      setCategories(catRes.data.data);
    } catch {
      toast.error('Failed to load data');
    } finally {
      setLoading(false);
    }
  };

  const emptyForm = { category_id: '', name: '', description: '', price: '', stock: '', image_url: '', hover_image_url: '' };

  const resetForm = () => {
    setForm(emptyForm);
    setEditing(null);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    const payload = { ...form, price: parseFloat(form.price), stock: parseInt(form.stock, 10) };
    try {
      if (editing) {
        await api.put(`/admin/products/${editing}`, payload);
        toast.success('Product updated successfully');
      } else {
        await api.post('/admin/products', payload);
        toast.success('Product created successfully');
      }
      resetForm();
      fetchData(editing ? page : 1);
    } catch (err) {
      toast.error(err.response?.data?.message || 'Save failed');
    } finally {
      setSaving(false);
    }
  };

  const handleEdit = (p) => {
    setForm({
      category_id: p.category_id, name: p.name, description: p.description || '',
      price: p.price, stock: p.stock, image_url: p.image_url || '', hover_image_url: p.hover_image_url || ''
    });
    setEditing(p.id);
  };

  const handleDelete = async (id) => {
    toast.custom((t) => (
      <div className="bg-gallery-white border border-gallery-stone p-4 flex flex-col gap-4">
        <p className="text-sm">Delete this product?</p>
        <div className="flex gap-2 justify-end">
          <button 
            className="text-xs uppercase tracking-widest text-gallery-subtle hover:text-gallery-ink transition-colors"
            onClick={() => toast.dismiss(t)}
          >
            Cancel
          </button>
          <button 
            className="text-xs uppercase tracking-widest text-red-600 hover:text-red-800 transition-colors"
            onClick={async () => {
              toast.dismiss(t);
              try {
                await api.delete(`/admin/products/${id}`);
                toast.success('Product deleted successfully');
                if (page > 1 && products.length === 1) fetchData(page - 1);
                else fetchData(page);
              } catch (err) {
                toast.error(err.response?.data?.message || 'Delete failed');
              }
            }}
          >
            Delete
          </button>
        </div>
      </div>
    ));
  };

  if (loading) return <div className="text-sm tracking-widest uppercase text-gallery-subtle">Loading...</div>;

  return (
    <div className="animate-in fade-in duration-500 max-w-6xl">
      <h1 className="text-3xl font-serif mb-12">Products</h1>

      <form onSubmit={handleSubmit} className="bg-gallery-white border border-gallery-stone p-8 mb-12">
        <h2 className="text-xl font-serif mb-6">{editing ? 'Edit Product' : 'New Product'}</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
          <div>
            <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Name</label>
            <input
              type="text"
              value={form.name}
              onChange={e => setForm({ ...form, name: e.target.value })}
              className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink transition-colors"
              required
            />
          </div>
          <div>
            <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Category</label>
            <select
              value={form.category_id}
              onChange={e => setForm({ ...form, category_id: e.target.value })}
              className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink transition-colors"
              required
            >
              <option value="">Select category</option>
              {categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
            </select>
          </div>
          <div>
            <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Price</label>
            <input
              type="number"
              step="0.01"
              min="0"
              value={form.price}
              onChange={e => setForm({ ...form, price: e.target.value })}
              className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink transition-colors"
              required
            />
          </div>
          <div>
            <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Stock</label>
            <input
              type="number"
              min="0"
              value={form.stock}
              onChange={e => setForm({ ...form, stock: e.target.value })}
              className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink transition-colors"
              required
            />
          </div>
          <div>
            <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Image URL</label>
            <input
              type="url"
              value={form.image_url}
              onChange={e => setForm({ ...form, image_url: e.target.value })}
              className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink transition-colors"
            />
          </div>
          <div>
            <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Hover Image URL</label>
            <input
              type="url"
              value={form.hover_image_url}
              onChange={e => setForm({ ...form, hover_image_url: e.target.value })}
              className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink transition-colors"
            />
          </div>
        </div>
        <div className="mb-6">
          <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Description</label>
          <textarea
            value={form.description}
            onChange={e => setForm({ ...form, description: e.target.value })}
            rows={3}
            className="w-full border border-gallery-stone bg-transparent p-3 focus:outline-none focus:border-gallery-ink transition-colors resize-none"
          />
        </div>
        <div className="flex gap-4">
          <button
            type="submit"
            disabled={saving}
            className="bg-gallery-ink text-white px-8 py-3 text-sm tracking-widest uppercase hover:bg-black transition-colors disabled:opacity-70"
          >
            {saving ? 'Saving...' : editing ? 'Update' : 'Create'}
          </button>
          {editing && (
            <button type="button" onClick={resetForm} className="px-8 py-3 text-sm tracking-widest uppercase border border-gallery-stone hover:bg-gallery-stone/50 transition-colors">
              Cancel
            </button>
          )}
        </div>
      </form>

      <div className="bg-gallery-white border border-gallery-stone overflow-hidden">
        <table className="w-full text-left text-sm">
          <thead className="bg-gallery-stone/30 border-b border-gallery-stone text-xs uppercase tracking-widest text-gallery-subtle">
            <tr>
              <th className="px-6 py-4 font-normal">Product</th>
              <th className="px-6 py-4 font-normal">Category</th>
              <th className="px-6 py-4 font-normal">Price</th>
              <th className="px-6 py-4 font-normal">Stock</th>
              <th className="px-6 py-4 font-normal text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gallery-stone">
            {products.map(p => (
              <tr key={p.id} className="hover:bg-gallery-stone/10 transition-colors">
                <td className="px-6 py-4">
                  <p>{p.name}</p>
                  {p.description && <p className="text-xs text-gallery-subtle mt-1 line-clamp-1">{p.description}</p>}
                </td>
                <td className="px-6 py-4 text-xs uppercase tracking-widest">{p.category?.name || '-'}</td>
                <td className="px-6 py-4 font-medium">{format(p.price)}</td>
                <td className="px-6 py-4">
                  <span className={p.stock < 5 ? 'text-red-600 font-medium' : ''}>{p.stock}</span>
                </td>
                <td className="px-6 py-4 text-right space-x-4">
                  <button onClick={() => handleEdit(p)} className="text-xs uppercase tracking-widest text-blue-600 hover:underline">Edit</button>
                  <button onClick={() => handleDelete(p.id)} className="text-xs uppercase tracking-widest text-red-600 hover:underline">Delete</button>
                </td>
              </tr>
            ))}
            {products.length === 0 && (
              <tr><td colSpan="5" className="px-6 py-8 text-center text-gallery-subtle text-xs uppercase tracking-widest">No products yet</td></tr>
            )}
          </tbody>
        </table>
        {lastPage > 1 && (
          <div className="flex items-center justify-between px-6 py-4 border-t border-gallery-stone">
            <span className="text-xs text-gallery-subtle">
              Showing {Number(products.length)} of {total} products
            </span>
            <div className="flex items-center gap-4">
              <button
                onClick={() => fetchData(page - 1)}
                disabled={page <= 1}
                className="text-xs uppercase tracking-widest text-gallery-ink hover:underline disabled:opacity-40 disabled:cursor-default"
              >
                ← Prev
              </button>
              <span className="text-xs text-gallery-subtle">Page {page} / {lastPage}</span>
              <button
                onClick={() => fetchData(page + 1)}
                disabled={page >= lastPage}
                className="text-xs uppercase tracking-widest text-gallery-ink hover:underline disabled:opacity-40 disabled:cursor-default"
              >
                Next →
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
