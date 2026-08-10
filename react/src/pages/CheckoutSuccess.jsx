import React from 'react';
import { Link } from 'react-router-dom';
import { useCartStore } from '../store/cart';

export const CheckoutSuccess = () => {
  const transactionId = localStorage.getItem('last_guest_transaction') || '';
  const { clearCart } = useCartStore();

  // clear cart on mount
  React.useEffect(() => { clearCart(); }, []);

  return (
    <div className="max-w-2xl mx-auto text-center p-12 mt-8 animate-in fade-in duration-700">
      <h1 className="text-4xl font-serif text-green-700 mb-4">Terima Kasih!</h1>
      <p className="mb-4 text-gray-600">Pesanan Anda sedang diproses.</p>
      
      {transactionId && (
        <div>
          <p className="text-sm text-gray-500 mb-6">
            Simpan link ini untuk melacak pesanan Anda. Link juga akan dikirim ke email Anda.
          </p>
          <Link to={`/orders/${transactionId}`} className="inline-block bg-gallery-ink text-white px-8 py-4 text-sm tracking-widest uppercase font-bold hover:bg-black transition-colors">
            Lacak Pesanan #{transactionId}
          </Link>
        </div>
      )}
      
      <div className="mt-8">
        <Link to="/" className="text-sm text-gallery-subtle hover:text-gallery-ink underline">Kembali ke Beranda</Link>
      </div>
    </div>
  );
};