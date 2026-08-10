import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from '../lib/axios';

export const OrderStatus = () => {
    const { id } = useParams();
    const [transaction, setTransaction] = useState(null);

    useEffect(() => {
        const fetchOrder = async () => {
            try {
                // Determine if guest or logged in
        const token = localStorage.getItem('token');
        const isGuest = !token;
        
        let url = `/transactions/${id}`;
                let headers = { 'Accept': 'application/json' };
                
        if (isGuest) {
            const trackingToken = localStorage.getItem('last_guest_tracking_token');
            url = `/transactions/guest/${id}?token=${trackingToken}`;
        } else {
            headers['Authorization'] = `Bearer ${token}`;
        }

        const res = await api.get(url, { headers });
                setTransaction(res.data.data);
            } catch (err) {
                console.error(err);
            }
        };

        fetchOrder();
    }, [id]);

    if (!transaction) return <div className="p-8 text-center">Loading or order not found...</div>;

    const statusColors = {
        pending: 'bg-yellow-100 text-yellow-800',
        shipping: 'bg-blue-100 text-blue-800',
        arrive: 'bg-green-100 text-green-800'
    };

    return (
        <div className="max-w-2xl mx-auto p-4 mt-8">
            <h1 className="text-2xl font-bold mb-4">Order #{transaction.id}</h1>
            
            <div className="bg-white shadow p-6 rounded mb-6">
                <div className="flex justify-between items-center mb-4">
                    <h2 className="text-lg font-semibold">Payment Status</h2>
                    <span className="px-3 py-1 rounded-full font-bold bg-gray-100">{transaction.status}</span>
                </div>
                
                <div className="flex justify-between items-center">
                    <h2 className="text-lg font-semibold">Shipping Status</h2>
                    <span className={`px-3 py-1 rounded-full font-bold ${statusColors[transaction.shipping_status] || 'bg-gray-100'}`}>
                        {transaction.shipping_status.toUpperCase()}
                    </span>
                </div>
            </div>

            <div className="bg-white shadow p-6 rounded">
                <h3 className="font-bold mb-2">Shipping Details</h3>
                <p>{transaction.customer_name}</p>
                <p>{transaction.shipping_address}</p>
                <p>{transaction.shipping_city}, {transaction.shipping_postal_code}</p>
                <p>{transaction.customer_phone}</p>
            </div>
            
            <div className="mt-6 text-center">
                <Link to="/" className="text-blue-600 hover:underline">Return to Home</Link>
            </div>
        </div>
    );
};
