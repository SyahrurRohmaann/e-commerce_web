import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useCartStore } from '../store/cart';
import api from '../lib/axios';

const SHIPPING_COST = 25000;
const formatIDR = (n) => `Rp ${(n ?? 0).toLocaleString('id-ID')}`;

export const Checkout = () => {
  const navigate = useNavigate();
  const { items, clearCart } = useCartStore();
  const [phase, setPhase] = useState('loading'); // loading | choose | register | login | form | confirm
  const [isGuest, setIsGuest] = useState(false);
  const [error, setError] = useState('');

  // Register
  const [regData, setRegData] = useState({ name: '', email: '', password: '' });
  const [regLoading, setRegLoading] = useState(false);

  // Login
  const [loginData, setLoginData] = useState({ email: '', password: '' });
  const [loginLoading, setLoginLoading] = useState(false);

  // Shipping form
  const [formData, setFormData] = useState({
    customer_name: '',
    guest_email: '',
    customer_phone: '',
    shipping_address: '',
    shipping_city: '',
    shipping_postal_code: '',
  });

  useEffect(() => {
    if (items.length === 0) { navigate('/cart'); return; }

    const token = localStorage.getItem('token');
    if (token) {
      api.get('/profile')
        .then(res => {
          const u = res.data.user;
          setFormData(prev => ({ ...prev,
            customer_name: u.name || '',
            guest_email: u.email || '',
            customer_phone: u.phone || '',
            shipping_address: u.address || '',
            shipping_city: u.city || '',
            shipping_postal_code: u.postal_code || '',
          }));
          setPhase('form');
        })
        .catch(() => {
          localStorage.removeItem('token');
          setPhase('choose');
        });
    } else {
      const saved = localStorage.getItem('guest_checkout_data');
      if (saved) {
        try { setFormData(prev => ({ ...prev, ...JSON.parse(saved) })); } catch { /* ignore */ }
      }
      setPhase('choose');
    }
  }, []);

  const handleChange = e => setFormData(prev => ({ ...prev, [e.target.name]: e.target.value }));
  const handleRegChange = e => setRegData(prev => ({ ...prev, [e.target.name]: e.target.value }));
  const handleLoginChange = e => setLoginData(prev => ({ ...prev, [e.target.name]: e.target.value }));

  const doRegister = async e => {
    e.preventDefault(); setRegLoading(true); setError('');
    try {
      const res = await api.post('/register', regData);
      localStorage.setItem('token', res.data.access_token);
      const u = res.data.user;
      setFormData(prev => ({ ...prev,
        customer_name: u.name || '',
        guest_email: u.email || '',
      }));
      setPhase('form');
    } catch (err) {
      setError(err.response?.data?.message || err.response?.data?.errors?.email?.[0] || 'Registration failed');
    } finally { setRegLoading(false); }
  };

  const doLogin = async e => {
    e.preventDefault(); setLoginLoading(true); setError('');
    try {
      const res = await api.post('/login', loginData);
      localStorage.setItem('token', res.data.access_token);
      const u = res.data.user;
      setFormData(prev => ({ ...prev,
        customer_name: u.name || '',
        guest_email: u.email || '',
        customer_phone: u.phone || '',
        shipping_address: u.address || '',
        shipping_city: u.city || '',
        shipping_postal_code: u.postal_code || '',
      }));
      setPhase('form');
    } catch (err) {
      setError(err.response?.data?.message || err.response?.data?.errors?.email?.[0] || 'Login failed');
    } finally { setLoginLoading(false); }
  };

  const [isSubmitting, setIsSubmitting] = useState(false);

  const submitShipping = e => {
    e.preventDefault();
    setPhase('confirm');
  };

  const doCheckout = async () => {
    if (isSubmitting) return;
    setIsSubmitting(true);
    setError('');
    const payload = { items, ...formData };
    // don't send guest_email for logged-in users
    if (!isGuest) delete payload.guest_email;

    try {
      const res = await api.post('/checkout', payload);
      if (res.data.invoice_url) {
        localStorage.removeItem('last_guest_transaction');
        localStorage.removeItem('last_guest_tracking_token');
        if (isGuest && res.data.tracking_token) {
          localStorage.setItem('last_guest_transaction', res.data.transaction_id);
          localStorage.setItem('last_guest_tracking_token', res.data.tracking_token);
        }
        window.location.href = res.data.invoice_url;
      }
    } catch (err) {
      setError(err.response?.data?.message || err.response?.data?.errors ? Object.values(err.response.data.errors).flat().join(', ') : 'Checkout failed');
      setIsSubmitting(false);
    }
  };

  const subtotal = items.reduce((s, i) => s + (i.price * i.quantity), 0);

  // ── LOADING ──
  if (phase === 'loading') return <div className="max-w-2xl mx-auto p-8 mt-8 text-center animate-in fade-in duration-500"><p className="text-gallery-subtle">Loading...</p></div>;

  // ── AUTH CHOOSER ──
  if (phase === 'choose') return (
    <div className="max-w-2xl mx-auto p-6 mt-8 animate-in fade-in duration-500">
      <h1 className="text-3xl font-serif mb-4 text-center">Checkout</h1>
      <div className="bg-gallery-stone/20 p-6 mb-10 rounded-sm">
        <p className="text-sm text-center mb-6">How would you like to continue?</p>
        <div className="flex flex-col sm:flex-row gap-4 justify-center">
          <button onClick={() => { setIsGuest(false); setPhase('register'); }} className="bg-gallery-ink text-white px-6 py-3 text-sm tracking-widest uppercase font-bold hover:bg-black transition-colors">Register</button>
          <button onClick={() => { setIsGuest(false); setPhase('login'); }} className="border border-gallery-ink px-6 py-3 text-sm tracking-widest uppercase font-bold hover:bg-gallery-ink hover:text-white transition-colors">Login</button>
          <button onClick={() => { setIsGuest(true); setPhase('form'); }} className="bg-gallery-stone/60 text-gallery-ink px-6 py-3 text-sm tracking-widest uppercase font-bold hover:bg-gallery-stone transition-colors">Guest</button>
        </div>
      </div>
    </div>
  );

  // ── REGISTER ──
  if (phase === 'register') return (
    <div className="max-w-md mx-auto p-6 mt-8 animate-in fade-in duration-500">
      <button onClick={() => setPhase('choose')} className="text-sm text-gallery-subtle mb-6 hover:text-gallery-ink">&larr; Back</button>
      <h1 className="text-2xl font-serif mb-6">Create Account</h1>
      {error && <div className="bg-red-50 text-red-800 p-3 mb-4 text-sm border border-red-200">{error}</div>}
      <form onSubmit={doRegister} className="space-y-4">
        <div>
          <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Name</label>
          <input name="name" value={regData.name} onChange={handleRegChange} required className="w-full border-b border-gallery-stone py-2 focus:outline-none focus:border-gallery-ink" />
        </div>
        <div>
          <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Email</label>
          <input name="email" type="email" value={regData.email} onChange={handleRegChange} required className="w-full border-b border-gallery-stone py-2 focus:outline-none focus:border-gallery-ink" />
        </div>
        <div>
          <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Password (min 8)</label>
          <input name="password" type="password" value={regData.password} onChange={handleRegChange} required minLength={8} className="w-full border-b border-gallery-stone py-2 focus:outline-none focus:border-gallery-ink" />
        </div>
        <button type="submit" disabled={regLoading} className="w-full bg-gallery-ink text-white py-3 font-bold uppercase tracking-widest text-sm disabled:opacity-70">{regLoading ? 'Creating...' : 'Register & Continue'}</button>
      </form>
    </div>
  );

  // ── LOGIN ──
  if (phase === 'login') return (
    <div className="max-w-md mx-auto p-6 mt-8 animate-in fade-in duration-500">
      <button onClick={() => setPhase('choose')} className="text-sm text-gallery-subtle mb-6 hover:text-gallery-ink">&larr; Back</button>
      <h1 className="text-2xl font-serif mb-6">Login</h1>
      {error && <div className="bg-red-50 text-red-800 p-3 mb-4 text-sm border border-red-200">{error}</div>}
      <form onSubmit={doLogin} className="space-y-4">
        <div>
          <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Email</label>
          <input name="email" type="email" value={loginData.email} onChange={handleLoginChange} required className="w-full border-b border-gallery-stone py-2 focus:outline-none focus:border-gallery-ink" />
        </div>
        <div>
          <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Password</label>
          <input name="password" type="password" value={loginData.password} onChange={handleLoginChange} required className="w-full border-b border-gallery-stone py-2 focus:outline-none focus:border-gallery-ink" />
        </div>
        <button type="submit" disabled={loginLoading} className="w-full bg-gallery-ink text-white py-3 font-bold uppercase tracking-widest text-sm disabled:opacity-70">{loginLoading ? 'Logging in...' : 'Login & Continue'}</button>
      </form>
    </div>
  );

  // ── SHIPPING FORM ──
  if (phase === 'form') return (
    <div className="max-w-5xl mx-auto p-6 flex flex-col md:flex-row gap-12 animate-in fade-in duration-500">
      <div className="flex-1">
        <h1 className="text-3xl font-serif mb-8">Shipping Details</h1>
        {isGuest && (
          <div className="bg-gallery-stone/30 p-4 mb-8 text-sm flex justify-between items-center">
            <span className="text-gallery-subtle">Checking out as guest.</span>
            <button onClick={() => setPhase('choose')} className="underline font-bold hover:text-gallery-ink">Change</button>
          </div>
        )}
        {!isGuest && (
          <div className="bg-green-50 p-4 mb-8 text-sm text-green-800 flex justify-between items-center border border-green-100">
            <span>Logged in</span>
            <button onClick={() => { localStorage.removeItem('token'); setPhase('choose'); }} className="underline font-bold">Logout</button>
          </div>
        )}
        {error && <div className="bg-red-50 text-red-800 p-4 mb-8 border border-red-200 text-sm">{error}</div>}
        <form onSubmit={submitShipping} className="space-y-6">
          <div className="space-y-4">
            <h2 className="text-sm tracking-widest uppercase font-bold border-b pb-2">Contact</h2>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="block text-xs text-gallery-subtle mb-1">Full Name *</label>
                <input name="customer_name" value={formData.customer_name} onChange={handleChange} required className="w-full border-b border-gallery-stone py-2 focus:outline-none focus:border-gallery-ink" />
              </div>
              <div>
                <label className="block text-xs text-gallery-subtle mb-1">Email {isGuest ? '*' : ''}</label>
                <input name="guest_email" type="email" value={formData.guest_email} onChange={handleChange} required={isGuest} disabled={!isGuest} className="w-full border-b border-gallery-stone py-2 focus:outline-none focus:border-gallery-ink disabled:opacity-60" />
              </div>
              <div className="sm:col-span-2">
                <label className="block text-xs text-gallery-subtle mb-1">Phone *</label>
                <input name="customer_phone" value={formData.customer_phone} onChange={handleChange} required className="w-full border-b border-gallery-stone py-2 focus:outline-none focus:border-gallery-ink" />
              </div>
            </div>
          </div>
          <div className="space-y-4 pt-4">
            <h2 className="text-sm tracking-widest uppercase font-bold border-b pb-2">Shipping Address</h2>
            <div>
              <label className="block text-xs text-gallery-subtle mb-1">Address *</label>
              <textarea name="shipping_address" value={formData.shipping_address} onChange={handleChange} required className="w-full border-b border-gallery-stone py-2 focus:outline-none focus:border-gallery-ink resize-none" rows="2" />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-xs text-gallery-subtle mb-1">City *</label>
                <input name="shipping_city" value={formData.shipping_city} onChange={handleChange} required className="w-full border-b border-gallery-stone py-2 focus:outline-none focus:border-gallery-ink" />
              </div>
              <div>
                <label className="block text-xs text-gallery-subtle mb-1">Postal Code *</label>
                <input name="shipping_postal_code" value={formData.shipping_postal_code} onChange={handleChange} required className="w-full border-b border-gallery-stone py-2 focus:outline-none focus:border-gallery-ink" />
              </div>
            </div>
          </div>
          <button type="submit" className="bg-gallery-ink text-white px-8 py-4 w-full font-bold uppercase tracking-widest text-sm hover:bg-black transition-colors mt-8">
            Review Order
          </button>
        </form>
      </div>
      <div className="w-full md:w-1/3 bg-gray-50 p-6 h-fit sticky top-24">
        <h2 className="text-sm tracking-widest uppercase font-bold border-b pb-4 mb-4">Order Summary</h2>
        <div className="space-y-4 mb-6">
          {items.map((item, i) => (
            <div key={i} className="flex justify-between text-sm">
              <span className="text-gallery-subtle">{item.name} x{item.quantity}</span>
              <span>{formatIDR(item.price * item.quantity)}</span>
            </div>
          ))}
        </div>
        <div className="border-t border-gallery-stone pt-4 space-y-2 text-sm">
          <div className="flex justify-between text-gallery-subtle"><span>Subtotal</span><span>{formatIDR(subtotal)}</span></div>
          <div className="flex justify-between text-gallery-subtle"><span>Shipping</span><span>{formatIDR(SHIPPING_COST)}</span></div>
        </div>
        <div className="flex justify-between font-serif text-xl mt-6 pt-4 border-t border-black">
          <span>Total</span>
          <span>{formatIDR(subtotal + SHIPPING_COST)}</span>
        </div>
      </div>
    </div>
  );

  // ── CONFIRMATION ──
  return (
    <div className="max-w-3xl mx-auto p-6 mt-8 animate-in fade-in duration-500">
      <h1 className="text-3xl font-serif mb-8 text-center">Confirm Your Order</h1>
      {error && <div className="bg-red-50 text-red-800 p-4 mb-8 border border-red-200 text-sm">{error}</div>}

      <div className="bg-gray-50 p-6 mb-8 space-y-2 text-sm">
        <h2 className="font-bold border-b pb-2 mb-3">Contact & Shipping</h2>
        <p><span className="text-gallery-subtle">Name:</span> {formData.customer_name}</p>
        <p><span className="text-gallery-subtle">Email:</span> {isGuest ? formData.guest_email : formData.guest_email}</p>
        <p><span className="text-gallery-subtle">Phone:</span> {formData.customer_phone}</p>
        <p><span className="text-gallery-subtle">Address:</span> {formData.shipping_address}</p>
        <p><span className="text-gallery-subtle">City:</span> {formData.shipping_city} &mdash; {formData.shipping_postal_code}</p>
      </div>

      <div className="bg-gray-50 p-6 mb-8 space-y-2 text-sm">
        <h2 className="font-bold border-b pb-2 mb-3">Items</h2>
        {items.map((item, i) => (
          <div key={i} className="flex justify-between">
            <span>{item.name} x{item.quantity}</span>
            <span>{formatIDR(item.price * item.quantity)}</span>
          </div>
        ))}
      </div>

      <div className="bg-gray-50 p-6 mb-8 space-y-2 text-sm">
        <div className="flex justify-between"><span>Subtotal</span><span>{formatIDR(subtotal)}</span></div>
        <div className="flex justify-between"><span>Shipping</span><span>{formatIDR(SHIPPING_COST)}</span></div>
        <div className="flex justify-between font-bold text-lg border-t pt-2 mt-2"><span>Total</span><span>{formatIDR(subtotal + SHIPPING_COST)}</span></div>
      </div>

      <div className="flex gap-4 justify-center">
        <button onClick={() => setPhase('form')} disabled={isSubmitting} className="border border-gallery-ink px-8 py-3 text-sm tracking-widest uppercase hover:bg-gallery-stone/30 transition-colors disabled:opacity-50">Edit</button>
        <button onClick={doCheckout} disabled={isSubmitting} className="bg-gallery-ink text-white px-8 py-3 text-sm tracking-widest uppercase font-bold hover:bg-black transition-colors disabled:opacity-70">
          {isSubmitting ? 'Processing...' : 'Pay Now'}
        </button>
      </div>
    </div>
  );
};