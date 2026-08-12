import { useCartStore } from '../store/cart';
import { useCurrencyStore } from '../store/currency';
import { Link, useNavigate } from 'react-router-dom';

function QtyControl({ qty, onChange }) {
  return (
    <div className="flex items-center border border-gallery-stone">
      <button type="button" onClick={() => onChange(qty - 1)} className="w-8 h-8 hover:bg-gallery-stone/40 transition-colors">-</button>
      <span className="w-10 text-center text-sm">{qty}</span>
      <button type="button" onClick={() => onChange(qty + 1)} className="w-8 h-8 hover:bg-gallery-stone/40 transition-colors">+</button>
    </div>
  );
}

export function Cart() {
  const { items, removeItem, updateQuantity, clearCart } = useCartStore();
  const currentCurrency = useCurrencyStore((state) => state.currentCurrency);
  const format = useCurrencyStore((state) => state.format);
  const navigate = useNavigate();

  const total = items.reduce((sum, item) => sum + (item.price * item.quantity), 0);

  const handleCheckout = () => {
    navigate('/checkout');
  };

  return (
    <div className="max-w-4xl mx-auto px-6 py-32 animate-in fade-in duration-700">
      <h2 className="text-4xl font-serif mb-16 pb-8 border-b border-gallery-stone text-center">Your Selection</h2>

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
                  <QtyControl qty={item.quantity} onChange={(q) => updateQuantity(item.product_id, q)} />
                </div>
                <div className="text-right flex items-center gap-8">
                  <span className="text-lg">{format(item.price * item.quantity)}</span>
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
            <div className="flex justify-between w-full md:w-1/2 mb-4 text-sm text-gallery-subtle">
              <span>Subtotal</span>
              <span>{format(total)}</span>
            </div>
            <div className="flex justify-between w-full md:w-1/2 mb-4 text-sm text-gallery-subtle">
              <span>Shipping</span>
              <span>{format(25000)}</span>
            </div>
            <div className="flex justify-between w-full md:w-1/2 mb-8 text-2xl font-serif">
              <span>Total</span>
              <span>{format(total + 25000)}</span>
            </div>
            <button 
              onClick={handleCheckout} 
              className="w-full md:w-1/2 bg-gallery-ink text-white py-5 text-sm tracking-widest uppercase hover:bg-black transition-colors"
            >
              Proceed to Checkout
            </button>
          </div>
        </div>
      )}
    </div>
  );
}