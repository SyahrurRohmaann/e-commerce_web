import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import api from '../lib/axios';
import LoadingState from '../components/ui/LoadingState';
import ErrorState from '../components/ui/ErrorState';

const emptyForm = {
  message: '',
  background_color: '#111111',
  text_color: '#FFFFFF',
  is_active: true,
  sort_order: 0,
};

export function AdminAnnouncements() {
  const [bars, setBars] = useState([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [editing, setEditing] = useState(null);
  const [error, setError] = useState('');
  const [form, setForm] = useState(emptyForm);

  useEffect(() => { fetchBars(); }, []);

  const fetchBars = async () => {
    setError('');
    try {
      const res = await api.get('/admin/announcements');
      setBars(res.data.data);
    } catch {
      setError('Unable to load announcement bars.');
    } finally {
      setLoading(false);
    }
  };

  const resetForm = () => {
    setForm(emptyForm);
    setEditing(null);
  };

  const updateForm = (field, value) => setForm((current) => ({ ...current, [field]: value }));

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    try {
      if (editing) {
        await api.put(`/admin/announcements/${editing}`, form);
        toast.success('Announcement updated successfully');
      } else {
        await api.post('/admin/announcements', form);
        toast.success('Announcement created successfully');
      }
      resetForm();
      fetchBars();
    } catch (err) {
      toast.error(err.response?.data?.message || 'Save failed');
    } finally {
      setSaving(false);
    }
  };

  const handleEdit = (bar) => {
    setForm({
      message: bar.message,
      background_color: bar.background_color || '#111111',
      text_color: bar.text_color || '#FFFFFF',
      is_active: bar.is_active !== false,
      sort_order: bar.sort_order || 0,
    });
    setEditing(bar.id);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleToggleActive = async (bar) => {
    try {
      await api.put(`/admin/announcements/${bar.id}`, {
        message: bar.message,
        background_color: bar.background_color,
        text_color: bar.text_color,
        is_active: !bar.is_active,
        sort_order: bar.sort_order,
      });
      toast.success('Announcement status updated');
      fetchBars();
    } catch {
      toast.error('Failed to update status');
    }
  };

  const handleDelete = (id) => {
    toast.custom((t) => (
      <div className="bg-gallery-white border border-gallery-stone p-4 flex flex-col gap-4">
        <p className="text-sm">Delete this announcement?</p>
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
                await api.delete(`/admin/announcements/${id}`);
                toast.success('Announcement deleted successfully');
                fetchBars();
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

  if (loading) return <LoadingState label="Loading announcements" />;
  if (error) return <ErrorState message={error} onRetry={fetchBars} />;

  return (
    <div className="animate-in fade-in duration-500 max-w-4xl">
      <h1 className="text-3xl font-serif mb-12">Announcements</h1>

      <form onSubmit={handleSubmit} className="bg-gallery-white border border-gallery-stone p-8 mb-12">
        <h2 className="text-xl font-serif mb-6">{editing ? 'Edit Announcement' : 'New Announcement'}</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <div className="md:col-span-2">
            <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Message</label>
            <input
              type="text"
              maxLength={255}
              value={form.message}
              onChange={(e) => updateForm('message', e.target.value)}
              className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink transition-colors"
              placeholder="Free shipping on orders over IDR 500K"
              required
            />
          </div>
          <div>
            <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Background color</label>
            <div className="flex items-center gap-3">
              <input
                type="color"
                value={form.background_color}
                onChange={(e) => updateForm('background_color', e.target.value)}
                className="w-10 h-10 border border-gallery-stone cursor-pointer"
              />
              <input
                type="text"
                value={form.background_color}
                onChange={(e) => updateForm('background_color', e.target.value)}
                className="flex-1 border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink transition-colors"
              />
            </div>
          </div>
          <div>
            <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Text color</label>
            <div className="flex items-center gap-3">
              <input
                type="color"
                value={form.text_color}
                onChange={(e) => updateForm('text_color', e.target.value)}
                className="w-10 h-10 border border-gallery-stone cursor-pointer"
              />
              <input
                type="text"
                value={form.text_color}
                onChange={(e) => updateForm('text_color', e.target.value)}
                className="flex-1 border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink transition-colors"
              />
            </div>
          </div>
          <div>
            <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Sort order</label>
            <input
              type="number"
              value={form.sort_order}
              onChange={(e) => updateForm('sort_order', Number.parseInt(e.target.value, 10) || 0)}
              className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink transition-colors"
            />
          </div>
          <label className="flex items-center gap-3 cursor-pointer self-end pb-3">
            <input
              type="checkbox"
              checked={form.is_active}
              onChange={(e) => updateForm('is_active', e.target.checked)}
              className="w-4 h-4 accent-gallery-ink"
            />
            <span className="text-xs uppercase tracking-widest text-gallery-subtle">Active</span>
          </label>
        </div>

        <div className="border border-gallery-stone p-4 mb-6">
          <p className="text-[10px] uppercase tracking-[0.2em] text-gallery-subtle mb-3">Storefront preview</p>
          <div
            className="w-full px-4 py-2 text-center text-[10px] uppercase tracking-[0.24em] whitespace-nowrap overflow-hidden"
            style={{ backgroundColor: form.background_color, color: form.text_color }}
          >
            {form.message || 'Announcement message preview'}
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
              <th className="px-6 py-4 font-normal">Message</th>
              <th className="px-6 py-4 font-normal">Colors</th>
              <th className="px-6 py-4 font-normal">Sort</th>
              <th className="px-6 py-4 font-normal">Status</th>
              <th className="px-6 py-4 font-normal text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gallery-stone">
            {bars.map((bar) => (
              <tr key={bar.id} className="hover:bg-gallery-stone/10 transition-colors">
                <td className="px-6 py-4 max-w-xs">
                  <p className="truncate">{bar.message}</p>
                </td>
                <td className="px-6 py-4">
                  <span className="inline-flex items-center gap-2">
                    <span className="w-4 h-4 border border-gallery-stone" style={{ backgroundColor: bar.background_color }} />
                    <span className="w-4 h-4 border border-gallery-stone" style={{ backgroundColor: bar.text_color }} />
                    <span className="text-xs text-gallery-subtle">{bar.background_color} / {bar.text_color}</span>
                  </span>
                </td>
                <td className="px-6 py-4 text-xs">{bar.sort_order}</td>
                <td className="px-6 py-4">
                  <button onClick={() => handleToggleActive(bar)} className={`text-xs uppercase tracking-widest ${bar.is_active ? 'text-green-700' : 'text-gallery-subtle'}`}>
                    {bar.is_active ? 'Active' : 'Hidden'}
                  </button>
                </td>
                <td className="px-6 py-4 text-right space-x-4">
                  <button onClick={() => handleEdit(bar)} className="text-xs uppercase tracking-widest text-blue-600 hover:underline">Edit</button>
                  <button onClick={() => handleDelete(bar.id)} className="text-xs uppercase tracking-widest text-red-600 hover:underline">Delete</button>
                </td>
              </tr>
            ))}
            {bars.length === 0 && (
              <tr><td colSpan="5" className="px-6 py-8 text-center text-gallery-subtle text-xs uppercase tracking-widest">No announcements yet</td></tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}