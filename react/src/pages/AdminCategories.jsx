import { useState, useEffect } from 'react';
import { toast } from 'sonner';
import api from '../lib/axios';

export function AdminCategories() {
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [form, setForm] = useState({ name: '', description: '' });
  const [editing, setEditing] = useState(null);
  const [saving, setSaving] = useState(false);

  useEffect(() => { fetchCategories(); }, []);

  const fetchCategories = async () => {
    try {
      const res = await api.get('/admin/categories');
      setCategories(res.data.data);
    } catch {
      toast.error('Failed to load categories');
    } finally {
      setLoading(false);
    }
  };

  const resetForm = () => {
    setForm({ name: '', description: '' });
    setEditing(null);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    try {
      if (editing) {
        await api.put(`/admin/categories/${editing}`, form);
        toast.success('Category updated successfully');
      } else {
        await api.post('/admin/categories', form);
        toast.success('Category created successfully');
      }
      resetForm();
      fetchCategories();
    } catch (err) {
      toast.error(err.response?.data?.message || 'Save failed');
    } finally {
      setSaving(false);
    }
  };

  const handleEdit = (cat) => {
    setForm({ name: cat.name, description: cat.description || '' });
    setEditing(cat.id);
  };

  const handleDelete = async (id) => {
    toast.custom((t) => (
      <div className="bg-gallery-white border border-gallery-stone p-4 flex flex-col gap-4">
        <p className="text-sm">Delete this category?</p>
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
                await api.delete(`/admin/categories/${id}`);
                toast.success('Category deleted successfully');
                fetchCategories();
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
    <div className="animate-in fade-in duration-500 max-w-4xl">
      <h1 className="text-3xl font-serif mb-12">Categories</h1>

      <form onSubmit={handleSubmit} className="bg-gallery-white border border-gallery-stone p-8 mb-12">
        <h2 className="text-xl font-serif mb-6">{editing ? 'Edit Category' : 'New Category'}</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
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
            <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Description</label>
            <input
              type="text"
              value={form.description}
              onChange={e => setForm({ ...form, description: e.target.value })}
              className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink transition-colors"
            />
          </div>
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
              <th className="px-6 py-4 font-normal">ID</th>
              <th className="px-6 py-4 font-normal">Name</th>
              <th className="px-6 py-4 font-normal">Description</th>
              <th className="px-6 py-4 font-normal text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gallery-stone">
            {categories.map(cat => (
              <tr key={cat.id} className="hover:bg-gallery-stone/10 transition-colors">
                <td className="px-6 py-4 font-mono text-xs">{cat.id}</td>
                <td className="px-6 py-4">{cat.name}</td>
                <td className="px-6 py-4 text-gallery-subtle">{cat.description || '-'}</td>
                <td className="px-6 py-4 text-right space-x-4">
                  <button onClick={() => handleEdit(cat)} className="text-xs uppercase tracking-widest text-blue-600 hover:underline">Edit</button>
                  <button onClick={() => handleDelete(cat.id)} className="text-xs uppercase tracking-widest text-red-600 hover:underline">Delete</button>
                </td>
              </tr>
            ))}
            {categories.length === 0 && (
              <tr><td colSpan="4" className="px-6 py-8 text-center text-gallery-subtle text-xs uppercase tracking-widest">No categories yet</td></tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}
