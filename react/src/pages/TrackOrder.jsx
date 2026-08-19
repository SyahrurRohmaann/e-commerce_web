import { useState, useEffect } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { toast } from 'sonner';
import { useCurrencyStore } from '../store/currency';
import api from '../lib/axios';
import LoadingState from '../components/ui/LoadingState';
import ErrorState from '../components/ui/ErrorState';

export const TrackOrder = () => {
  const [orders, setOrders] = useState([]);
  const [userOrders, setUserOrders] = useState([]);
  const [searchToken, setSearchToken] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [userOrdersLoading, setUserOrdersLoading] = useState(false);
  const navigate = useNavigate();
  const currentCurrency = useCurrencyStore((state) => state.currentCurrency);
  const format = useCurrencyStore((state) => state.format);

  const isGuest = !localStorage.getItem('token');

  useEffect(() => {
    // Check if token is provided in query params e.g. /track-order?token=xxx
    const searchParams = new URLSearchParams(window.location.search);
    const tokenQuery = searchParams.get('token');

    if (tokenQuery) {
      const autoTrack = async () => {
        setLoading(true);
        try {
          const res = await api.get(`/transactions/track?token=${tokenQuery}`);
          const tx = res.data.data;
          
          const newOrder = {
            id: tx.id,
            token: tx.tracking_token,
            date: tx.created_at
          };
          
          const existing = JSON.parse(localStorage.getItem('guest_orders') || '[]');
          // eslint-disable-next-line eqeqeq
          if (!existing.find(o => o.id == newOrder.id)) {
            const updated = [newOrder, ...existing];
            localStorage.setItem('guest_orders', JSON.stringify(updated));
          }
          
          navigate(`/order/${tx.id}?token=${tokenQuery}`);
        } catch {
          toast.error('Order not found or invalid tracking token.');
        } finally {
          setLoading(false);
        }
      };

      autoTrack();
      return;
    }

    if (isGuest) {
      const saved = JSON.parse(localStorage.getItem('guest_orders') || '[]');
      setOrders(saved);
    } else {
      fetchUserOrders();
    }
  }, [isGuest, navigate]);

  const fetchUserOrders = async () => {
    setUserOrdersLoading(true);
    setError('');
    try {
      const res = await api.get('/profile/transactions');
      setUserOrders(res.data.data);
    } catch {
      setError('Unable to load your orders.');
    } finally {
      setUserOrdersLoading(false);
    }
  };

  const handleSearch = async (e) => {
    e.preventDefault();
    if (loading || !searchToken.trim()) return;
    setLoading(true);

    try {
      // Validate order exists by token
      const res = await api.get(`/transactions/track?token=${searchToken}`);
      const tx = res.data.data;
      
      const newOrder = {
        id: tx.id,
        token: tx.tracking_token,
        date: tx.created_at
      };
      
      const existing = JSON.parse(localStorage.getItem('guest_orders') || '[]');
      
      // Avoid duplicates
      // eslint-disable-next-line eqeqeq
      if (!existing.find(o => o.id == newOrder.id)) {
        const updated = [newOrder, ...existing];
        localStorage.setItem('guest_orders', JSON.stringify(updated));
        setOrders(updated);
      }
      
      setSearchToken('');
      navigate(`/order/${tx.id}?token=${tx.tracking_token}`);

    } catch {
      toast.error('Order not found or invalid token.');
    } finally {
      setLoading(false);
    }
  };

  if (!isGuest) {
    return (
      <div className="max-w-3xl mx-auto p-6 mt-8 animate-in fade-in duration-500">
        <h1 className="text-3xl font-serif mb-8 text-center">My Orders</h1>
        
        {userOrdersLoading ? <LoadingState label="Loading your orders" /> : error ? <ErrorState message={error} onRetry={fetchUserOrders} /> : userOrders.length === 0 ? (
          <p className="text-gallery-subtle text-center py-8">No orders found.</p>
        ) : (
          <div className="space-y-6">
            {userOrders.map(tx => (
              <div key={tx.id} className="border border-gallery-stone p-6 bg-gray-50 hover:border-gallery-ink transition-colors group">
                <div className="flex justify-between items-start mb-4">
                  <div>
                    <p className="text-sm font-bold">Order #{tx.id}</p>
                    <p className="text-xs text-gallery-subtle">{new Date(tx.created_at).toLocaleDateString()}</p>
                  </div>
                  <div className="text-right">
                    <p className="text-sm font-bold">{format(tx.total_amount)}</p>
                    <div className="flex gap-2 mt-1 justify-end">
                      <span className={`text-[10px] px-2 py-0.5 rounded-full uppercase tracking-widest font-bold ${tx.status === 'PAID' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}`}>
                        {tx.status}
                      </span>
                      <span className="text-[10px] px-2 py-0.5 rounded-full uppercase tracking-widest font-bold bg-blue-100 text-blue-800">
                        {tx.shipping_status}
                      </span>
                    </div>
                  </div>
                </div>
                
                <div className="border-t border-gallery-stone pt-4 mb-4">
                  <p className="text-[10px] text-gallery-subtle uppercase tracking-widest mb-3">Items</p>
                  {tx.items?.map(item => (
                    <div key={item.id} className="flex justify-between text-sm mb-2">
                      <span className="font-serif">{item.product_name} <span className="text-gallery-subtle font-sans text-xs">x{item.quantity}</span></span>
                      <span>{format(item.price * item.quantity)}</span>
                    </div>
                  ))}
                </div>

                <div className="flex justify-end pt-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <Link 
                    to={`/order/${tx.id}?token=${tx.tracking_token || ''}`} 
                    className="text-xs uppercase tracking-widest font-bold text-gallery-ink hover:underline flex items-center gap-2"
                  >
                    View Details <span>&rarr;</span>
                  </Link>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    );
  }

  return (
    <div className="max-w-3xl mx-auto p-6 mt-8 animate-in fade-in duration-500">
      <h1 className="text-3xl font-serif mb-8 text-center">Track Order</h1>
      
      <div className="bg-gray-50 p-6 mb-12 border border-gallery-stone">
        <h2 className="text-sm font-bold uppercase tracking-widest mb-4">Find an Order</h2>
        <p className="text-xs text-gallery-subtle mb-6">Enter your Tracking Token to view order details.</p>
        
        <form onSubmit={handleSearch} className="flex flex-col sm:flex-row gap-4">
          <input 
            type="text" 
            placeholder="Tracking Token" 
            value={searchToken}
            onChange={e => setSearchToken(e.target.value)}
            required
            className="flex-1 border border-gallery-stone p-3 text-sm focus:outline-none focus:border-gallery-ink min-w-[300px]"
          />
          <button 
            type="submit" 
            disabled={loading}
            className="bg-gallery-ink text-white px-8 py-3 text-sm font-bold uppercase tracking-widest hover:bg-black transition-colors disabled:opacity-50"
          >
            {loading ? 'Searching...' : 'Track'}
          </button>
        </form>
      </div>

      <div>
        <h2 className="text-sm font-bold uppercase tracking-widest mb-6 border-b border-gallery-stone pb-2">Recent Orders on this Device</h2>
        
        {orders.length === 0 ? (
          <p className="text-sm text-gallery-subtle text-center py-8">No recent orders found.</p>
        ) : (
          <div className="space-y-4">
            {orders.map((order, i) => (
              <div key={i} className="flex items-center justify-between border border-gallery-stone p-4 bg-white hover:bg-gray-50 transition-colors">
                <div>
                  <p className="font-bold">Order #{order.id}</p>
                  <p className="text-xs text-gallery-subtle">{new Date(order.date).toLocaleDateString()}</p>
                </div>
                <button 
                  onClick={() => {
                    localStorage.setItem('last_guest_tracking_token', order.token); // compat for OrderStatus
                    navigate(`/order/${order.id}?token=${order.token}`);
                  }}
                  className="text-xs font-bold uppercase tracking-widest text-gallery-ink hover:underline"
                >
                  View Details &rarr;
                </button>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};
