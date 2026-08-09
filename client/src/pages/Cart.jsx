import { useCartStore } from '../store/cart';
import api from '../lib/axios';
import { useState } from 'react';
import { Link } from 'react-router-dom';

export function Cart() {
  const { items, removeItem, clearCart } = useCartStore();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const total = items.reduce((sum, item) => sum + (item.price * item.quantity), 0);

  const handleCheckout = async () => {
    setLoading(true);
    setError('');
    try {
      const res = await api.post('/checkout', { items });
      if (res.data.invoice_url) {
        clearCart();
        window.location.href = res.data.invoice_url;
      }
    } catch (err) {
      if (err.response?.status === 401) {
        setError('Authentication required. Please log in to complete purchase.');
      } else {
        setError(err.response?.data?.message || 'Checkout failed');
      }
    }
    setLoading(false);
  };

  return (
    <div className="max-w-4xl mx-auto px-6 py-32 animate-in fade-in duration-700">
      <h2 className="text-4xl font-serif mb-16 pb-8 border-b border-gallery-stone text-center">Your Selection</h2>
      
      {error && (
        <div className="bg-red-50 text-red-800 border border-red-200 p-4 mb-8 text-sm text-center">
          {error}
          {error.includes('log in') && (
             <Link to="/login" className="ml-4 underline font-medium">Log in here</Link>
          )}
        </div>
      )}

      {items.length === 0 ? (
        <div className="text-center py-24">
          <p className="text-gallery-subtle uppercase tracking-widest text-sm mb-8">Your cart is empty</p>
          <Link to="/" className="border border-gallery-ink px-8 py-4 text-xs tracking-widest uppercase hover:bg-gallery-ink hover:text-gallery-white transition-colors">
            Return to Gallery
          </Link>
        </div>
      ) : (
        <div>
          <div className="space-y-8 mb-16">
            {items.map(item => (
              <div key={item.product_id} className="flex items-center justify-between py-6 border-b border-gallery-stone/50">
                <div className="flex-1">
                  <h3 className="text-lg font-serif mb-2">{item.name}</h3>
                  <p className="text-sm text-gallery-subtle">Qty: {item.quantity}</p>
                </div>
                <div className="text-right flex items-center gap-8">
                  <span className="text-lg">${(item.price * item.quantity).toLocaleString()}</span>
                  <button 
                    onClick={() => removeItem(item.product_id)}
                    className="text-xs uppercase tracking-widest text-gallery-subtle hover:text-red-600 transition-colors"
                  >
                    Remove
                  </button>
                </div>
              </div>
            ))}
          </div>
          
          <div className="flex flex-col items-end border-t border-gallery-stone pt-8">
            <div className="flex justify-between w-full md:w-1/2 mb-8 text-2xl font-serif">
              <span>Total</span>
              <span>${total.toLocaleString()}</span>
            </div>
            <button 
              onClick={handleCheckout} 
              disabled={loading}
              className="w-full md:w-1/2 bg-gallery-ink text-white py-5 text-sm tracking-widest uppercase hover:bg-black transition-colors disabled:opacity-70"
            >
              {loading ? 'Processing...' : 'Proceed to Checkout'}
            </button>
          </div>
        </div>
      )}
    </div>
  );
}