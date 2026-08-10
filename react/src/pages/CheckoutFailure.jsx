import React from 'react';
import { Link } from 'react-router-dom';

export const CheckoutFailure = () => (
  <div className="max-w-2xl mx-auto text-center p-12 mt-8 animate-in fade-in duration-700">
    <h1 className="text-3xl font-serif text-red-700 mb-4">Pembayaran Gagal</h1>
    <p className="mb-8 text-gray-600">Silakan coba lagi atau hubungi kami jika masalah berlanjut.</p>
    <Link to="/checkout" className="inline-block bg-gallery-ink text-white px-8 py-4 text-sm tracking-widest uppercase font-bold hover:bg-black transition-colors">
      Coba Lagi
    </Link>
  </div>
);