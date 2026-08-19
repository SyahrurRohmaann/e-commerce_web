import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import api from '../lib/axios';
import { useCurrencyStore } from '../store/currency';
import LoadingState from '../components/ui/LoadingState';
import ErrorState from '../components/ui/ErrorState';

export const OrderStatus = () => {
    const { id } = useParams();
    const [transaction, setTransaction] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const currentCurrency = useCurrencyStore((state) => state.currentCurrency);
    const format = useCurrencyStore((state) => state.format);

    useEffect(() => {
        const fetchOrder = async () => {
            setLoading(true);
            setError('');
            try {
                // Determine if guest or logged in
        const token = localStorage.getItem('token');
        const isGuest = !token;
        
        let url = `/transactions/${id}`;
                let headers = { 'Accept': 'application/json' };
                
        if (isGuest) {
            // Find token in URL params first, then localStorage guest array, then fallback to last_guest_tracking_token
            const searchParams = new URLSearchParams(window.location.search);
            let trackingToken = searchParams.get('token');
            
            if (!trackingToken) {
              const guestOrders = JSON.parse(localStorage.getItem('guest_orders') || '[]');
              // Use == to compare string to number
              // eslint-disable-next-line eqeqeq
              const foundOrder = guestOrders.find(o => o.id == id);
              if (foundOrder) {
                trackingToken = foundOrder.token;
              } else {
                trackingToken = localStorage.getItem('last_guest_tracking_token');
              }
            }
            url = `/transactions/guest/${id}?token=${trackingToken}`;
        } else {
            headers['Authorization'] = `Bearer ${token}`;
        }

        const res = await api.get(url, { headers });
                setTransaction(res.data.data);
            } catch (err) {
                setError(err.response?.status === 404 ? 'Order not found.' : 'Unable to load this order.');
            } finally {
                setLoading(false);
            }
        };

        fetchOrder();
    }, [id]);

    if (loading) return <LoadingState label="Loading order" />;
    if (error) return <ErrorState message={error} />;
    if (!transaction) return <ErrorState message="Order is unavailable." />;

    const statusColors = {
        pending: 'bg-yellow-100 text-yellow-800',
        shipping: 'bg-blue-100 text-blue-800',
        arrive: 'bg-green-100 text-green-800'
    };

    const formatIDR = (n) => `Rp ${(n ?? 0).toLocaleString('id-ID')}`;

    return (
        <div className="max-w-3xl mx-auto p-6 mt-8 animate-in fade-in duration-500">
            <h1 className="text-3xl font-serif mb-8 text-center">Order #{transaction.id}</h1>
            
            <div className="grid md:grid-cols-2 gap-8 mb-8">
                <div className="bg-gray-50 border border-gallery-stone p-6">
                    <h2 className="text-sm font-bold uppercase tracking-widest border-b border-gallery-stone pb-2 mb-4">Status</h2>
                    
                    <div className="space-y-4">
                        <div className="flex justify-between items-center">
                            <span className="text-xs text-gallery-subtle uppercase tracking-widest">Payment</span>
                            <span className={`text-[10px] px-2 py-0.5 rounded-full uppercase tracking-widest font-bold ${transaction.status === 'PAID' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}`}>
                                {transaction.status}
                            </span>
                        </div>
                        
                        <div className="flex justify-between items-center">
                            <span className="text-xs text-gallery-subtle uppercase tracking-widest">Shipping</span>
                            <span className={`text-[10px] px-2 py-0.5 rounded-full uppercase tracking-widest font-bold ${statusColors[transaction.shipping_status] || 'bg-gray-100'}`}>
                                {transaction.shipping_status}
                            </span>
                        </div>

                        {transaction.shipping_status === 'shipping' && (
                            <div className="pt-4 mt-4 border-t border-gallery-stone">
                                <p className="text-xs text-gallery-subtle mb-1">Courier: <span className="font-bold text-gallery-ink">{transaction.shipping_courier}</span> ({transaction.shipping_method})</p>
                                <p className="text-xs text-gallery-subtle flex items-center gap-2">
                                    Tracking: 
                                    <span className="font-mono bg-white border border-gallery-stone px-2 py-1 select-all font-bold text-gallery-ink">
                                        {transaction.tracking_number}
                                    </span>
                                </p>
                            </div>
                        )}
                    </div>
                </div>

                <div className="bg-gray-50 border border-gallery-stone p-6">
                    <h2 className="text-sm font-bold uppercase tracking-widest border-b border-gallery-stone pb-2 mb-4">Shipping Info</h2>
                    <div className="space-y-1 text-sm text-gallery-subtle">
                        <p className="font-bold text-gallery-ink">{transaction.customer_name}</p>
                        <p>{transaction.customer_phone}</p>
                        <p className="mt-2">{transaction.shipping_address}</p>
                        <p>{transaction.shipping_city}, {transaction.shipping_postal_code}</p>
                        {transaction.guest_email && <p className="mt-2 text-xs">Email: {transaction.guest_email}</p>}
                    </div>
                </div>
            </div>
            
            <div className="bg-gray-50 border border-gallery-stone p-6 mb-8">
                <h2 className="text-sm font-bold uppercase tracking-widest border-b border-gallery-stone pb-2 mb-4">Items</h2>
                <div className="space-y-4 mb-6">
                    {transaction.items?.map(item => (
                        <div key={item.id} className="flex justify-between text-sm">
                            <span className="font-serif text-lg">{item.product_name} <span className="font-sans text-xs text-gallery-subtle ml-2">x{item.quantity}</span></span>
                            <span className="font-bold">{format(item.price * item.quantity)}</span>
                        </div>
                    ))}
                </div>
                
                <div className="border-t border-gallery-stone pt-4 space-y-2 text-sm">
                    <div className="flex justify-between text-gallery-subtle">
                        <span>Subtotal</span>
                        <span>{format(transaction.total_amount - transaction.shipping_cost)}</span>
                    </div>
                    <div className="flex justify-between text-gallery-subtle">
                        <span>Shipping</span>
                        <span>{format(transaction.shipping_cost)}</span>
                    </div>
                    <div className="flex justify-between font-serif text-xl mt-4 pt-4 border-t border-gallery-stone text-gallery-ink">
                        <span>Total</span>
                        <span>{format(transaction.total_amount)}</span>
                    </div>
                </div>
            </div>

            <div className="text-center">
                <Link to="/" className="text-xs font-bold uppercase tracking-widest text-gallery-subtle hover:text-gallery-ink transition-colors pb-1 border-b border-transparent hover:border-gallery-ink">
                    &larr; Back to Store
                </Link>
            </div>
        </div>
    );
};
