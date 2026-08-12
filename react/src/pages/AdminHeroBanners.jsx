import { useState, useEffect } from 'react';
import { toast } from 'sonner';
import api from '../lib/axios';

function PositionPicker({ value, onChange, label }) {
  const positions = ['tl', 'tc', 'tr', 'ml', 'mc', 'mr', 'bl', 'bc', 'br'];
  const labels = {
    tl: 'Top Left', tc: 'Top Center', tr: 'Top Right',
    ml: 'Mid Left', mc: 'Center', mr: 'Mid Right',
    bl: 'Bot Left', bc: 'Bot Center', br: 'Bot Right',
  };

  return (
    <div>
      <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">
        {label}: <span className="font-bold text-gallery-ink">{labels[value] || value}</span>
      </label>
      <div className="grid grid-cols-3 gap-1 w-36 bg-gallery-stone/30 p-1 border border-gallery-stone">
        {positions.map((pos) => (
          <button
            key={pos}
            type="button"
            onClick={() => onChange(pos)}
            className={`w-10 h-10 text-[10px] uppercase font-bold tracking-tighter transition-colors ${
              value === pos
                ? 'bg-gallery-ink text-white shadow-sm'
                : 'bg-white/80 hover:bg-white text-gallery-subtle hover:text-gallery-ink'
            }`}
            title={labels[pos]}
          >
            {pos.toUpperCase()}
          </button>
        ))}
      </div>
    </div>
  );
}

export function AdminHeroBanners() {
  const [banners, setBanners] = useState([]);
  const [loading, setLoading] = useState(true);
  const [form, setForm] = useState({
    title: '',
    caption: '',
    subtitle: '',
    image_url: '',
    title_position: 'tc',
    caption_position: 'tc',
    button_position: 'bc',
    button_text: '',
    button_url: '',
    sort_order: 0,
    is_active: true,
    duration_ms: 5000,
  });
  const [editing, setEditing] = useState(null);
  const [saving, setSaving] = useState(false);

  useEffect(() => { fetchBanners(); }, []);

  const fetchBanners = async () => {
    try {
      const res = await api.get('/admin/hero-banners');
      setBanners(res.data.data);
    } catch {
      toast.error('Failed to load hero banners');
    } finally {
      setLoading(false);
    }
  };

  const resetForm = () => {
    setForm({
      title: '',
      caption: '',
      subtitle: '',
      image_url: '',
      title_position: 'tc',
      caption_position: 'tc',
      button_position: 'bc',
      button_text: '',
      button_url: '',
      sort_order: 0,
      is_active: true,
      duration_ms: 5000,
    });
    setEditing(null);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    try {
      if (editing) {
        await api.put(`/admin/hero-banners/${editing}`, form);
        toast.success('Banner updated successfully');
      } else {
        await api.post('/admin/hero-banners', form);
        toast.success('Banner created successfully');
      }
      resetForm();
      fetchBanners();
    } catch (err) {
      toast.error(err.response?.data?.message || 'Save failed');
    } finally {
      setSaving(false);
    }
  };

  const handleEdit = (banner) => {
    setForm({
      title: banner.title || '',
      caption: banner.caption || '',
      subtitle: banner.subtitle || '',
      image_url: banner.image_url,
      title_position: banner.title_position || 'tc',
      caption_position: banner.caption_position || 'tc',
      button_position: banner.button_position || 'bc',
      button_text: banner.button_text || '',
      button_url: banner.button_url || '',
      sort_order: banner.sort_order,
      is_active: banner.is_active,
      duration_ms: banner.duration_ms,
    });
    setEditing(banner.id);
  };

  const handleDelete = async (id) => {
    toast.custom((t) => (
      <div className="bg-gallery-white border border-gallery-stone p-4 flex flex-col gap-4">
        <p className="text-sm">Delete this banner?</p>
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
                await api.delete(`/admin/hero-banners/${id}`);
                toast.success('Banner deleted successfully');
                fetchBanners();
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

  const handleToggleActive = async (banner) => {
    try {
      await api.put(`/admin/hero-banners/${banner.id}`, {
        ...banner,
        is_active: !banner.is_active,
      });
      toast.success('Banner status updated');
      fetchBanners();
    } catch {
      toast.error('Failed to update status');
    }
  };

  if (loading) return <div className="text-sm tracking-widest uppercase text-gallery-subtle">Loading...</div>;

  return (
    <div className="animate-in fade-in duration-500 max-w-6xl">
      <h1 className="text-3xl font-serif mb-12">Hero Banners</h1>

      <form onSubmit={handleSubmit} className="bg-gallery-white border border-gallery-stone p-8 mb-12">
        <h2 className="text-xl font-serif mb-6">{editing ? 'Edit Banner' : 'New Banner'}</h2>
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <div>
            <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Title</label>
            <input
              type="text"
              value={form.title}
              onChange={e => setForm({ ...form, title: e.target.value })}
              className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink transition-colors"
              placeholder="e.g. NEW COLLECTION"
            />
          </div>
          <div>
            <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Caption (Tagline)</label>
            <input
              type="text"
              value={form.caption}
              onChange={e => setForm({ ...form, caption: e.target.value })}
              className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink transition-colors"
              placeholder="e.g. Spring / Summer 2026"
            />
          </div>
          <div className="md:col-span-2">
            <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Subtitle (Description)</label>
            <input
              type="text"
              value={form.subtitle}
              onChange={e => setForm({ ...form, subtitle: e.target.value })}
              className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink transition-colors"
              placeholder="e.g. Discover our latest arrivals designed for movement"
            />
          </div>
          <div className="md:col-span-2">
            <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Image URL</label>
            <input
              type="url"
              value={form.image_url}
              onChange={e => setForm({ ...form, image_url: e.target.value })}
              className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink transition-colors"
              placeholder="https://example.com/image.jpg"
              required
            />
            {form.image_url && (
              <div className="mt-4 w-48 h-28 bg-gallery-stone overflow-hidden border border-gallery-stone">
                <img src={form.image_url} alt="Preview" className="w-full h-full object-cover" onError={(e) => e.target.style.display = 'none'} />
              </div>
            )}
          </div>

          {/* Position Pickers (9-Point Grid) */}
          <div className="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-6 p-4 bg-gallery-stone/20 border border-gallery-stone/50">
            <PositionPicker
              label="Title Position"
              value={form.title_position}
              onChange={(pos) => setForm({ ...form, title_position: pos })}
            />
            <PositionPicker
              label="Caption Position"
              value={form.caption_position}
              onChange={(pos) => setForm({ ...form, caption_position: pos })}
            />
            <PositionPicker
              label="Button Position"
              value={form.button_position}
              onChange={(pos) => setForm({ ...form, button_position: pos })}
            />
          </div>

          <div>
            <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Button Text</label>
            <input
              type="text"
              value={form.button_text}
              onChange={e => setForm({ ...form, button_text: e.target.value })}
              className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink transition-colors"
              placeholder="e.g. Shop Now"
            />
          </div>
          <div>
            <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Button URL</label>
            <input
              type="text"
              value={form.button_url}
              onChange={e => setForm({ ...form, button_url: e.target.value })}
              className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink transition-colors"
              placeholder="e.g. /catalog"
            />
          </div>
          <div>
            <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Sort Order</label>
            <input
              type="number"
              value={form.sort_order}
              onChange={e => setForm({ ...form, sort_order: parseInt(e.target.value) || 0 })}
              className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink transition-colors"
            />
          </div>
          <div>
            <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Duration (ms)</label>
            <input
              type="number"
              value={form.duration_ms}
              onChange={e => setForm({ ...form, duration_ms: parseInt(e.target.value) || 5000 })}
              min={1000}
              step={500}
              className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink transition-colors"
            />
          </div>
          <div className="md:col-span-2">
            <label className="flex items-center gap-3 cursor-pointer">
              <input
                type="checkbox"
                checked={form.is_active}
                onChange={e => setForm({ ...form, is_active: e.target.checked })}
                className="w-4 h-4 accent-gallery-ink"
              />
              <span className="text-sm tracking-widest uppercase text-gallery-subtle">Active</span>
            </label>
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
              <th className="px-6 py-4 font-normal">Preview</th>
              <th className="px-6 py-4 font-normal">Title</th>
              <th className="px-6 py-4 font-normal">Positions (T/C/B)</th>
              <th className="px-6 py-4 font-normal">Order</th>
              <th className="px-6 py-4 font-normal">Duration</th>
              <th className="px-6 py-4 font-normal">Active</th>
              <th className="px-6 py-4 font-normal text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gallery-stone">
            {banners.map(banner => (
              <tr key={banner.id} className="hover:bg-gallery-stone/10 transition-colors">
                <td className="px-6 py-4">
                  <div className="w-16 h-10 bg-gallery-stone overflow-hidden">
                    <img src={banner.image_url} alt="" className="w-full h-full object-cover" onError={(e) => e.target.style.display = 'none'} />
                  </div>
                </td>
                <td className="px-6 py-4 font-medium">
                  {banner.title || '-'}
                  {banner.caption && <span className="block text-xs text-gallery-subtle">{banner.caption}</span>}
                </td>
                <td className="px-6 py-4 font-mono text-xs">
                  <span className="px-1.5 py-0.5 bg-gallery-stone/50 rounded mr-1">{banner.title_position || 'tc'}</span>
                  <span className="px-1.5 py-0.5 bg-gallery-stone/50 rounded mr-1">{banner.caption_position || 'tc'}</span>
                  <span className="px-1.5 py-0.5 bg-gallery-stone/50 rounded">{banner.button_position || 'bc'}</span>
                </td>
                <td className="px-6 py-4 font-mono text-xs">{banner.sort_order}</td>
                <td className="px-6 py-4 text-xs">{(banner.duration_ms / 1000).toFixed(1)}s</td>
                <td className="px-6 py-4">
                  <button
                    onClick={() => handleToggleActive(banner)}
                    className={`text-xs uppercase tracking-widest px-3 py-1 rounded ${
                      banner.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'
                    }`}
                  >
                    {banner.is_active ? 'Yes' : 'No'}
                  </button>
                </td>
                <td className="px-6 py-4 text-right space-x-4">
                  <button onClick={() => handleEdit(banner)} className="text-xs uppercase tracking-widest text-blue-600 hover:underline">Edit</button>
                  <button onClick={() => handleDelete(banner.id)} className="text-xs uppercase tracking-widest text-red-600 hover:underline">Delete</button>
                </td>
              </tr>
            ))}
            {banners.length === 0 && (
              <tr>
                <td colSpan="7" className="px-6 py-8 text-center text-gallery-subtle text-xs uppercase tracking-widest">No hero banners yet</td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}
