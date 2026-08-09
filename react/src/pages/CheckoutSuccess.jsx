import React, { useEffect } from 'react';
import { Link } from 'react-router-dom';

export const CheckoutSuccess = () => {
    const transactionId = localStorage.getItem('last_guest_transaction') || '';

    return (
        <div className="max-w-2xl mx-auto text-center p-12 mt-8">
            <h1 className="text-4xl font-bold text-green-600 mb-4">Terima Kasih Sudah Order!</h1>
            <p className="mb-8 text-gray-600">Pesanan Anda sedang diproses. Anda dapat memantau status pesanan Anda melalui link di bawah.</p>
            
            {transactionId && (
                <Link to={`/orders/${transactionId}`} className="bg-blue-600 text-white px-6 py-3 rounded font-bold shadow hover:bg-blue-700">
                    Lacak Pesanan #{transactionId}
                </Link>
            )}
        </div>
    );
};