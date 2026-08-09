import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';
import api from '../lib/axios';

export const Checkout = () => {
    const navigate = useNavigate();
    const [cart, setCart] = useState([]);
    const [authMode, setAuthMode] = useState(null); // 'login', 'guest', or null (decision pending)
    const [isGuest, setIsGuest] = useState(false);
    
    // Login form state
    const [loginData, setLoginData] = useState({ email: '', password: '' });
    const [loginError, setLoginError] = useState('');
    const [loginLoading, setLoginLoading] = useState(false);

    const [formData, setFormData] = useState({
        customer_name: '',
        customer_phone: '',
        customer_email: '',
        shipping_address: '',
        shipping_city: '',
        shipping_postal_code: '',
        save_address_to_profile: false
    });
    const [error, setError] = useState(null);

    useEffect(() => {
        // Load mock cart data
        setCart([{ product_id: 1, quantity: 1, name: 'Product 1', price: 92000 }]);
        
        const token = localStorage.getItem('token');
        if (token) {
            setAuthMode('logged_in');
            setIsGuest(false);
            fetchUserProfile();
        } else {
            // Check if there's saved guest data
            const savedGuest = localStorage.getItem('guest_checkout_data');
            if (savedGuest) {
                try {
                    setFormData(JSON.parse(savedGuest));
                } catch (e) {
                    console.error(e);
                }
            }
        }
    }, []);

    const fetchUserProfile = async () => {
        try {
            const res = await api.get('/profile');
            const user = res.data.user;
            setFormData(prev => ({
                ...prev,
                customer_name: user.name || '',
                customer_email: user.email || '',
                customer_phone: user.phone || '',
                shipping_address: user.address || '',
                shipping_city: user.city || '',
                shipping_postal_code: user.postal_code || ''
            }));
        } catch (err) {
            console.error('Failed to fetch profile', err);
        }
    };

    const handleLoginSubmit = async (e) => {
        e.preventDefault();
        setLoginLoading(true);
        setLoginError('');
        
        try {
            const res = await api.post('/login', loginData);
            localStorage.setItem('token', res.data.access_token);
            localStorage.setItem('role', res.data.user.role);
            setAuthMode('logged_in');
            setIsGuest(false);
            fetchUserProfile();
        } catch (err) {
            if (err.response?.status === 422) {
                setLoginError(err.response.data.message || 'Invalid credentials');
            } else {
                setLoginError('Login failed.');
            }
        } finally {
            setLoginLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError(null);
        
        if (isGuest) {
            localStorage.setItem('guest_checkout_data', JSON.stringify(formData));
        }

        try {
            const token = localStorage.getItem('token');
            const headers = { 'Accept': 'application/json' };
            if (token && !isGuest) {
                headers['Authorization'] = `Bearer ${token}`;
            }

            const res = await axios.post('http://127.0.0.1:8000/api/checkout', {
                items: cart,
                ...formData
            }, { headers });

            if (res.data.invoice_url) {
                // Save transaction ID and token for guest tracking
                if (isGuest && res.data.tracking_token) {
                    localStorage.setItem('last_guest_transaction', res.data.transaction_id);
                    localStorage.setItem('last_guest_tracking_token', res.data.tracking_token);
                }
                window.location.href = res.data.invoice_url;
            }
        } catch (err) {
            console.error('Checkout failed', err);
            if (err.response?.data?.message) {
                setError(err.response.data.message);
            } else if (err.response?.data?.errors) {
                setError(Object.values(err.response.data.errors).flat().join(', '));
            } else {
                setError('Checkout failed. Please check your connection and try again.');
            }
        }
    };

    const handleChange = (e) => {
        setFormData({ ...formData, [e.target.name]: e.target.type === 'checkbox' ? e.target.checked : e.target.value });
    };

    // View: Initial Selection (Not logged in and hasn't chosen guest/login)
    if (authMode === null) {
        return (
            <div className="max-w-2xl mx-auto p-4 mt-8 text-center animate-in fade-in duration-500">
                <h1 className="text-3xl font-serif mb-8">How would you like to checkout?</h1>
                <div className="flex flex-col sm:flex-row gap-6 justify-center">
                    <button 
                        onClick={() => { setAuthMode('login'); setIsGuest(false); }}
                        className="bg-gallery-ink text-white px-8 py-4 font-bold tracking-widest uppercase text-sm hover:bg-black transition-colors"
                    >
                        Login to Checkout
                    </button>
                    <button 
                        onClick={() => { setAuthMode('guest'); setIsGuest(true); }}
                        className="bg-gallery-stone text-gallery-ink px-8 py-4 font-bold tracking-widest uppercase text-sm hover:bg-gray-300 transition-colors"
                    >
                        Checkout as Guest
                    </button>
                </div>
                <div className="mt-8">
                    <button onClick={() => navigate('/login')} className="text-sm text-gallery-subtle hover:text-gallery-ink underline">
                        Don't have an account? Register here (Go to Login page)
                    </button>
                </div>
            </div>
        );
    }

    // View: Login Form (In Checkout flow)
    if (authMode === 'login') {
        return (
            <div className="max-w-md mx-auto p-4 mt-8 animate-in fade-in duration-500">
                <button onClick={() => setAuthMode(null)} className="text-sm text-gallery-subtle mb-6 hover:text-gallery-ink">? Back to selection</button>
                <h1 className="text-2xl font-bold mb-6">Login to Checkout</h1>
                
                {loginError && <div className="bg-red-50 text-red-800 p-3 mb-4 text-sm border border-red-200">{loginError}</div>}
                
                <form onSubmit={handleLoginSubmit} className="space-y-4">
                    <div>
                        <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Email</label>
                        <input 
                            type="email" 
                            value={loginData.email}
                            onChange={(e) => setLoginData({...loginData, email: e.target.value})}
                            className="w-full border p-3 focus:outline-none focus:border-gallery-ink"
                            required
                        />
                    </div>
                    <div>
                        <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Password</label>
                        <input 
                            type="password" 
                            value={loginData.password}
                            onChange={(e) => setLoginData({...loginData, password: e.target.value})}
                            className="w-full border p-3 focus:outline-none focus:border-gallery-ink"
                            required
                        />
                    </div>
                    <button 
                        type="submit" 
                        disabled={loginLoading}
                        className="w-full bg-gallery-ink text-white py-3 font-bold uppercase tracking-widest text-sm disabled:opacity-70"
                    >
                        {loginLoading ? 'Logging in...' : 'Login'}
                    </button>
                </form>
            </div>
        );
    }

    // View: Main Checkout Form (Guest or Logged In)
    return (
        <div className="max-w-5xl mx-auto p-6 flex flex-col md:flex-row gap-12 animate-in fade-in duration-500">
            <div className="flex-1">
                <h1 className="text-3xl font-serif mb-8">Checkout Details</h1>
                
                {isGuest && (
                    <div className="bg-gallery-stone/30 p-4 mb-8 text-sm flex justify-between items-center">
                        <span className="text-gallery-subtle">Checking out as a guest.</span>
                        <button onClick={() => setAuthMode(null)} className="font-bold hover:underline">Change</button>
                    </div>
                )}
                {!isGuest && (
                    <div className="bg-green-50 p-4 mb-8 text-sm text-green-800 flex justify-between items-center border border-green-100">
                        <span>Checking out as <strong>{formData.customer_email}</strong></span>
                        <button onClick={() => { localStorage.removeItem('token'); setAuthMode(null); }} className="font-bold hover:underline">Logout</button>
                    </div>
                )}
                
                {error && <div className="bg-red-50 text-red-800 p-4 mb-8 border border-red-200 text-sm">{error}</div>}

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="space-y-4">
                        <h2 className="text-sm tracking-widest uppercase font-bold border-b pb-2">Contact Information</h2>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs text-gallery-subtle mb-1">Full Name</label>
                                <input name="customer_name" value={formData.customer_name} onChange={handleChange} required className="w-full border-b border-gallery-stone py-2 focus:outline-none focus:border-gallery-ink" />
                            </div>
                            <div>
                                <label className="block text-xs text-gallery-subtle mb-1">Email</label>
                                <input name="customer_email" type="email" value={formData.customer_email} onChange={handleChange} required className="w-full border-b border-gallery-stone py-2 focus:outline-none focus:border-gallery-ink" />
                            </div>
                            <div className="sm:col-span-2">
                                <label className="block text-xs text-gallery-subtle mb-1">Phone Number</label>
                                <input name="customer_phone" value={formData.customer_phone} onChange={handleChange} required className="w-full border-b border-gallery-stone py-2 focus:outline-none focus:border-gallery-ink" />
                            </div>
                        </div>
                    </div>

                    <div className="space-y-4 pt-4">
                        <h2 className="text-sm tracking-widest uppercase font-bold border-b pb-2">Shipping Address</h2>
                        <div>
                            <label className="block text-xs text-gallery-subtle mb-1">Address</label>
                            <textarea name="shipping_address" value={formData.shipping_address} onChange={handleChange} required className="w-full border-b border-gallery-stone py-2 focus:outline-none focus:border-gallery-ink resize-none" rows="2"></textarea>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs text-gallery-subtle mb-1">City</label>
                                <input name="shipping_city" value={formData.shipping_city} onChange={handleChange} required className="w-full border-b border-gallery-stone py-2 focus:outline-none focus:border-gallery-ink" />
                            </div>
                            <div>
                                <label className="block text-xs text-gallery-subtle mb-1">Postal Code</label>
                                <input name="shipping_postal_code" value={formData.shipping_postal_code} onChange={handleChange} required className="w-full border-b border-gallery-stone py-2 focus:outline-none focus:border-gallery-ink" />
                            </div>
                        </div>
                    </div>

                    {!isGuest && (
                        <label className="flex items-center gap-2 text-sm mt-4">
                            <input type="checkbox" name="save_address_to_profile" checked={formData.save_address_to_profile} onChange={handleChange} className="accent-gallery-ink" />
                            Save this address to my profile for future orders
                        </label>
                    )}
                    
                    <button type="submit" className="bg-gallery-ink text-white px-8 py-4 w-full font-bold uppercase tracking-widest text-sm hover:bg-black transition-colors mt-8">
                        Lanjut Pembayaran
                    </button>
                </form>
            </div>
            
            <div className="w-full md:w-1/3 bg-gray-50 p-6 h-fit sticky top-24">
                <h2 className="text-sm tracking-widest uppercase font-bold border-b pb-4 mb-4">Order Summary</h2>
                <div className="space-y-4 mb-6">
                    {cart.map((item, i) => (
                        <div key={i} className="flex justify-between text-sm">
                            <span className="text-gallery-subtle">{item.name} x{item.quantity}</span>
                            <span>Rp {(item.price * item.quantity).toLocaleString('id-ID')}</span>
                        </div>
                    ))}
                </div>
                <div className="border-t border-gallery-stone pt-4 space-y-2 text-sm">
                    <div className="flex justify-between text-gallery-subtle">
                        <span>Subtotal</span>
                        <span>Rp {(cart.reduce((sum, item) => sum + (item.price * item.quantity), 0)).toLocaleString('id-ID')}</span>
                    </div>
                    <div className="flex justify-between text-gallery-subtle">
                        <span>Shipping</span>
                        <span>Rp 25.000</span>
                    </div>
                </div>
                <div className="flex justify-between font-serif text-xl mt-6 pt-4 border-t border-black">
                    <span>Total</span>
                    <span>Rp {(cart.reduce((sum, item) => sum + (item.price * item.quantity), 0) + 25000).toLocaleString('id-ID')}</span>
                </div>
            </div>
        </div>
    );
};
