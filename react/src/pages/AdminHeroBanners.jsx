import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import api from '../lib/axios';

const emptyForm = {
  title: '',
  caption: '',
  subtitle: '',
  image_url: '',
  layout_direction: 'text-left',
  panel_theme: 'ivory',
  image_position: '50% 50%',
  text_alignment: 'left',
  button_text: '',
  button_url: '',
  sort_order: 0,
  is_active: true,
  duration_ms: 6000,
};

const themes = {
  ivory: 'bg-gallery-white text-gallery-ink',
  stone: 'bg-gallery-stone text-gallery-ink',
  ink: 'bg-gallery-ink text-white',
};

function OptionButtons({ label, value, options, onChange }) {
  return (
    <div>
      <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-3">{label}</label>
      <div className="flex flex-wrap gap-2">
        {options.map((option) => (
          <button
            key={option.value}
            type="button"
            onClick={() => onChange(option.value)}
            className={`px-4 py-2 border text-xs uppercase tracking-widest transition-colors ${
              value === option.value
                ? 'border-gallery-ink bg-gallery-ink text-white'
                : 'border-gallery-stone bg-white text-gallery-subtle hover:text-gallery-ink'
            }`}
          >
            {option.label}
          </button>
        ))}
      </div>
    </div>
  );
}

function BannerPreview({ banner }) {
  const textFirst = banner.layout_direction === 'text-left';
  const centered = banner.text_alignment === 'center';

  return (
    <div className={`grid min-h-80 border border-gallery-stone overflow-hidden ${textFirst ? 'grid-cols-[43%_57%]' : 'grid-cols-[57%_43%]'}`}>
      <div className={`${themes[banner.panel_theme] || themes.ivory} ${textFirst ? 'order-1' : 'order-2'} p-8 flex items-center`}>
        <div className={centered ? 'text-center w-full' : ''}>
          <p className="text-[9px] uppercase tracking-[0.24em] opacity-60 mb-4">{banner.caption || 'The new collection'}</p>
          <h3 className="text-4xl leading-[0.92] tracking-tight font-serif whitespace-pre-line">{banner.title || 'Form, without noise.'}</h3>
          <p className="text-xs leading-5 opacity-60 mt-4 line-clamp-3">{banner.subtitle || 'Clean lines, honest materials, lasting character.'}</p>
          <span className="inline-block text-[9px] uppercase tracking-widest border-b border-current pb-1 mt-5">{banner.button_text || 'View the edit'} →</span>
        </div>
      </div>
      <div className={`${textFirst ? 'order-2' : 'order-1'} bg-gallery-stone min-h-80`}>
        {banner.image_url ? (
          <img src={banner.image_url} alt="Hero preview" className="w-full h-full object-cover" style={{ objectPosition: banner.image_position }} />
        ) : (
          <div className="w-full h-full flex items-center justify-center text-xs uppercase tracking-widest text-gallery-subtle">Image preview</div>
        )}
      </div>
    </div>
  );
}

export function AdminHeroBanners() {
  const [banners, setBanners] = useState([]);
  const [loading, setLoading] = useState(true);
  const [form, setForm] = useState(emptyForm);
  const [editing, setEditing] = useState(null);
  const [saving, setSaving] = useState(false);

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

  useEffect(() => { fetchBanners(); }, []);

  const updateForm = (field, value) => setForm((current) => ({ ...current, [field]: value }));

  const resetForm = () => {
    setForm(emptyForm);
    setEditing(null);
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
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
    } catch (error) {
      const errors = error.response?.data?.errors;
      toast.error(errors ? Object.values(errors).flat()[0] : (error.response?.data?.message || 'Save failed'));
    } finally {
      setSaving(false);
    }
  };

  const handleEdit = (banner) => {
    setForm({
      ...emptyForm,
      ...banner,
      layout_direction: banner.layout_direction || 'text-left',
      panel_theme: banner.panel_theme || 'ivory',
      image_position: banner.image_position || '50% 50%',
      text_alignment: banner.text_alignment || 'left',
    });
    setEditing(banner.id);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleDelete = (id) => {
    toast.custom((toastId) => (
      <div className="bg-gallery-white border border-gallery-stone p-4 flex flex-col gap-4">
        <p className="text-sm">Delete this banner?</p>
        <div className="flex gap-4 justify-end text-xs uppercase tracking-widest">
          <button onClick={() => toast.dismiss(toastId)}>Cancel</button>
          <button className="text-red-600" onClick={async () => {
            toast.dismiss(toastId);
            try {
              await api.delete(`/admin/hero-banners/${id}`);
              toast.success('Banner deleted successfully');
              fetchBanners();
            } catch (error) {
              toast.error(error.response?.data?.message || 'Delete failed');
            }
          }}>Delete</button>
        </div>
      </div>
    ));
  };

  const handleToggleActive = async (banner) => {
    try {
      await api.put(`/admin/hero-banners/${banner.id}`, { ...banner, is_active: !banner.is_active });
      toast.success('Banner status updated');
      fetchBanners();
    } catch {
      toast.error('Failed to update status');
    }
  };

  if (loading) return <div className="text-sm tracking-widest uppercase text-gallery-subtle">Loading...</div>;

  const [focalX, focalY] = form.image_position.split(' ').map((value) => Number.parseInt(value, 10) || 50);

  return (
    <div className="animate-in fade-in duration-500 max-w-7xl">
      <div className="mb-12">
        <p className="text-xs uppercase tracking-[0.24em] text-gallery-subtle mb-3">Storefront art direction</p>
        <h1 className="text-4xl font-serif">Hero Editor</h1>
      </div>

      <form onSubmit={handleSubmit} className="bg-gallery-white border border-gallery-stone mb-14">
        <div className="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_minmax(440px,0.9fr)]">
          <div className="p-8 border-b xl:border-b-0 xl:border-r border-gallery-stone">
            <h2 className="text-xl font-serif mb-8">{editing ? 'Edit Banner' : 'New Banner'}</h2>
            <div className="space-y-7">
              <div>
                <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Eyebrow <span className="normal-case tracking-normal">({form.caption.length}/80)</span></label>
                <input maxLength={80} value={form.caption} onChange={(event) => updateForm('caption', event.target.value)} className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink" placeholder="The new collection" />
              </div>
              <div>
                <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Headline <span className="normal-case tracking-normal">({form.title.length}/55)</span></label>
                <textarea maxLength={55} rows={2} value={form.title} onChange={(event) => updateForm('title', event.target.value)} className="w-full resize-none border-b border-gallery-stone bg-transparent py-3 text-2xl font-serif focus:outline-none focus:border-gallery-ink" placeholder="Form, without noise." />
              </div>
              <div>
                <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Description <span className="normal-case tracking-normal">({form.subtitle.length}/160)</span></label>
                <textarea maxLength={160} rows={3} value={form.subtitle} onChange={(event) => updateForm('subtitle', event.target.value)} className="w-full resize-none border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink" placeholder="Clean lines, honest materials, lasting character." />
              </div>
              <div>
                <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Image URL</label>
                <input type="url" required value={form.image_url} onChange={(event) => updateForm('image_url', event.target.value)} className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink" placeholder="https://example.com/image.jpg" />
              </div>

              <OptionButtons label="Layout" value={form.layout_direction} onChange={(value) => updateForm('layout_direction', value)} options={[{ value: 'text-left', label: 'Text left' }, { value: 'text-right', label: 'Text right' }]} />
              <OptionButtons label="Panel theme" value={form.panel_theme} onChange={(value) => updateForm('panel_theme', value)} options={[{ value: 'ivory', label: 'Ivory' }, { value: 'stone', label: 'Stone' }, { value: 'ink', label: 'Ink' }]} />
              <OptionButtons label="Text alignment" value={form.text_alignment} onChange={(value) => updateForm('text_alignment', value)} options={[{ value: 'left', label: 'Left' }, { value: 'center', label: 'Center' }]} />

              <div>
                <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-3">Image focal point: {form.image_position}</label>
                <div className="grid grid-cols-[80px_1fr] items-center gap-4 text-xs text-gallery-subtle">
                  <span>Horizontal</span><input type="range" min="0" max="100" value={focalX} onChange={(event) => updateForm('image_position', `${event.target.value}% ${focalY}%`)} className="accent-gallery-ink" />
                  <span>Vertical</span><input type="range" min="0" max="100" value={focalY} onChange={(event) => updateForm('image_position', `${focalX}% ${event.target.value}%`)} className="accent-gallery-ink" />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div><label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">CTA text</label><input maxLength={40} value={form.button_text} onChange={(event) => updateForm('button_text', event.target.value)} className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink" placeholder="View the edit" /></div>
                <div><label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">CTA URL</label><input value={form.button_url} onChange={(event) => updateForm('button_url', event.target.value)} className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink" placeholder="/#collection" /></div>
                <div><label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Sort order</label><input type="number" value={form.sort_order} onChange={(event) => updateForm('sort_order', Number.parseInt(event.target.value, 10) || 0)} className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none" /></div>
                <div><label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Duration</label><input type="number" min={1000} max={30000} step={500} value={form.duration_ms} onChange={(event) => updateForm('duration_ms', Number.parseInt(event.target.value, 10) || 6000)} className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none" /></div>
              </div>
              <label className="flex items-center gap-3 cursor-pointer"><input type="checkbox" checked={form.is_active} onChange={(event) => updateForm('is_active', event.target.checked)} className="w-4 h-4 accent-gallery-ink" /><span className="text-xs tracking-widest uppercase text-gallery-subtle">Active</span></label>
            </div>
          </div>

          <aside className="p-8 bg-gallery-stone/10 xl:sticky xl:top-20 xl:self-start">
            <div className="flex justify-between mb-5"><h3 className="text-lg font-serif">Live preview</h3><span className="text-[10px] uppercase tracking-widest text-gallery-subtle">Desktop</span></div>
            <BannerPreview banner={form} />
            <p className="mt-4 text-xs leading-5 text-gallery-subtle">Typography, spacing, and panel ratio are locked to protect the storefront design.</p>
          </aside>
        </div>
        <div className="p-6 border-t border-gallery-stone flex gap-4">
          <button type="submit" disabled={saving} className="bg-gallery-ink text-white px-8 py-3 text-xs tracking-widest uppercase disabled:opacity-60">{saving ? 'Saving...' : editing ? 'Update banner' : 'Create banner'}</button>
          {editing && <button type="button" onClick={resetForm} className="px-8 py-3 text-xs tracking-widest uppercase border border-gallery-stone">Cancel</button>}
        </div>
      </form>

      <div className="space-y-4">
        <h2 className="text-2xl font-serif mb-6">Published banners</h2>
        {banners.map((banner) => (
          <div key={banner.id} className="grid grid-cols-[100px_1fr_auto] items-center gap-5 bg-gallery-white border border-gallery-stone p-4">
            <img src={banner.image_url} alt="" className="w-24 h-16 object-cover bg-gallery-stone" style={{ objectPosition: banner.image_position || '50% 50%' }} />
            <div><h3 className="font-serif text-lg">{banner.title || 'Untitled banner'}</h3><p className="text-xs text-gallery-subtle mt-1 uppercase tracking-widest">{banner.layout_direction || 'text-left'} · {banner.panel_theme || 'ivory'} · {(banner.duration_ms / 1000).toFixed(1)}s</p></div>
            <div className="flex items-center gap-4 text-xs uppercase tracking-widest">
              <button onClick={() => handleToggleActive(banner)} className={banner.is_active ? 'text-green-700' : 'text-gallery-subtle'}>{banner.is_active ? 'Active' : 'Hidden'}</button>
              <button onClick={() => handleEdit(banner)} className="text-blue-600">Edit</button>
              <button onClick={() => handleDelete(banner.id)} className="text-red-600">Delete</button>
            </div>
          </div>
        ))}
        {!banners.length && <div className="border border-gallery-stone p-8 text-center text-xs uppercase tracking-widest text-gallery-subtle">No hero banners yet</div>}
      </div>
    </div>
  );
}
