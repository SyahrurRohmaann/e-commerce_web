import { useState, useEffect } from 'react';
import api from '../lib/axios';

export function AdminTransactions() {
  const [transactions, setTransactions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [selectedTx, setSelectedTx] = useState(null);

  useEffect(() => { fetchTransactions(); }, []);

  const fetchTransactions = async () => {
    try {
      const res = await api.get('/admin/transactions');
      setTransactions(res.data.data);
    } catch {
      setError('Failed to load transactions');
    } finally {
      setLoading(false);
    }
  };

  const [updateError, setUpdateError] = useState('');
  
  const [paymentStatus, setPaymentStatus] = useState('PENDING');
  const [shippingStatus, setShippingStatus] = useState('pending');
  const [shippingMethod, setShippingMethod] = useState('');
  const [shippingCourier, setShippingCourier] = useState('');
  const [trackingNumber, setTrackingNumber] = useState('');

  const loadDetail = async (id) => {
    try {
      setUpdateError('');
      const res = await api.get(`/admin/transactions/${id}`);
      const tx = res.data.data;
      setSelectedTx(tx);
      setPaymentStatus(tx.status || 'PENDING');
      setShippingStatus(tx.shipping_status || 'pending');
      setShippingMethod(tx.shipping_method || '');
      setShippingCourier(tx.shipping_courier || '');
      setTrackingNumber(tx.tracking_number || '');
    } catch {
      alert('Failed to load transaction details');
    }
  };

  const updateStatus = async (e) => {
    e.preventDefault();
    setUpdateError('');
    try {
      const payload = { 
        status: paymentStatus,
        shipping_status: shippingStatus 
      };
      
      if (shippingStatus === 'shipping') {
        payload.shipping_method = shippingMethod;
        payload.shipping_courier = shippingCourier;
        payload.tracking_number = trackingNumber;
      }
      
      const res = await api.put(`/admin/transactions/${selectedTx.id}/status`, payload);
      alert('Status updated successfully');
      loadDetail(selectedTx.id);
      fetchTransactions();
    } catch (err) {
      setUpdateError(err.response?.data?.message || err.response?.data?.errors ? Object.values(err.response.data.errors).flat().join(', ') : 'Failed to update status');
    }
  };

  if (loading) return <div className="text-sm tracking-widest uppercase text-gallery-subtle">Loading...</div>;

  return (
    <div className="animate-in fade-in duration-500 max-w-6xl">
      <h1 className="text-3xl font-serif mb-12">Transactions</h1>

      {error && (
        <div className="bg-red-50 text-red-800 border border-red-200 p-4 mb-8 text-sm">{error}</div>
      )}

      {selectedTx ? (
        <div className="bg-gallery-white border border-gallery-stone p-8">
          <button onClick={() => setSelectedTx(null)} className="text-xs tracking-widest uppercase text-gallery-subtle hover:text-gallery-ink mb-6 flex items-center gap-2">
            ← Back to list
          </button>
          
          <div className="flex justify-between items-start mb-8 pb-8 border-b border-gallery-stone">
            <div>
              <h2 className="text-2xl font-serif mb-2">Order #{selectedTx.id.toString().padStart(4, '0')}</h2>
              <p className="text-gallery-subtle text-sm">{new Date(selectedTx.created_at).toLocaleString()}</p>
            </div>
            <div className="text-right">
              <span className={`px-3 py-1.5 text-xs tracking-widest uppercase rounded-sm inline-block mb-2 ${
                selectedTx.status === 'PAID' ? 'bg-green-100 text-green-800' :
                selectedTx.status === 'PENDING' ? 'bg-yellow-100 text-yellow-800' :
                'bg-red-100 text-red-800'
              }`}>
                {selectedTx.status}
              </span>
              <p className="text-xs uppercase tracking-widest text-gallery-subtle">Payment Method: {selectedTx.payment_method || 'N/A'}</p>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-12 mb-12">
            <div>
              <h3 className="text-xs uppercase tracking-widest text-gallery-subtle mb-4">Customer Details</h3>
              <p className="mb-1">{selectedTx.customer_name}</p>
              <p className="text-gallery-subtle">{selectedTx.guest_email || selectedTx.user?.email}</p>
              <p className="text-gallery-subtle">{selectedTx.customer_phone}</p>
            </div>
            <div>
              <h3 className="text-xs uppercase tracking-widest text-gallery-subtle mb-4">Shipping Address</h3>
              <p className="text-gallery-subtle leading-relaxed whitespace-pre-line">{selectedTx.shipping_address}</p>
            </div>
          </div>

          <h3 className="text-xs uppercase tracking-widest text-gallery-subtle mb-4">Order Items</h3>
          <table className="w-full text-left text-sm mb-8">
            <thead className="border-b border-gallery-stone">
              <tr>
                <th className="py-3 font-normal text-gallery-subtle">Item</th>
                <th className="py-3 font-normal text-gallery-subtle text-center">Qty</th>
                <th className="py-3 font-normal text-gallery-subtle text-right">Price</th>
                <th className="py-3 font-normal text-gallery-subtle text-right">Total</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gallery-stone">
              {selectedTx.items?.map(item => (
                <tr key={item.id}>
                  <td className="py-4">{item.product_name}</td>
                  <td className="py-4 text-center">{item.quantity}</td>
                  <td className="py-4 text-right">Rp {Number(item.price).toLocaleString('id-ID')}</td>
                  <td className="py-4 text-right">Rp {(Number(item.price) * item.quantity).toLocaleString('id-ID')}</td>
                </tr>
              ))}
            </tbody>
            <tfoot>
              <tr>
                <td colSpan="3" className="py-4 text-right text-xs uppercase tracking-widest text-gallery-subtle">Total Amount</td>
                <td className="py-4 text-right text-xl font-serif">Rp {Number(selectedTx.total_amount).toLocaleString('id-ID')}</td>
              </tr>
            </tfoot>
          </table>

          <div className="pt-8 border-t border-gallery-stone">
            <h3 className="text-xs uppercase tracking-widest text-gallery-subtle mb-4">Update Order Status</h3>
            {updateError && (
              <div className="bg-red-50 text-red-800 border border-red-200 p-3 mb-4 text-sm">{updateError}</div>
            )}
            <form onSubmit={updateStatus} className="bg-gallery-stone/10 p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
              
              {/* KOLOM 1: STATUS PEMBAYARAN */}
              <div>
                <label className="block text-xs font-bold uppercase tracking-widest text-gallery-subtle mb-2">Status Pembayaran</label>
                <select 
                  value={paymentStatus}
                  onChange={(e) => setPaymentStatus(e.target.value)}
                  className="w-full border border-gallery-stone p-2 bg-white mb-2"
                >
                  <option value="PENDING">Pending (Belum Bayar)</option>
                  <option value="PAID">Paid (Sudah Dibayar)</option>
                  <option value="EXPIRED">Expired</option>
                </select>
                <p className="text-[10px] text-gallery-subtle">
                  *Update manual ke PAID akan memotong stok otomatis. Normalnya ini terupdate otomatis lewat Webhook Xendit.
                </p>
              </div>

              {/* KOLOM 2: STATUS PENGIRIMAN */}
              <div>
                <label className="block text-xs font-bold uppercase tracking-widest text-gallery-subtle mb-2">Status Pengiriman</label>
                <select 
                  value={shippingStatus}
                  onChange={(e) => setShippingStatus(e.target.value)}
                  className="w-full border border-gallery-stone p-2 bg-white"
                >
                  <option value="pending">Belum Dikirim (Pending)</option>
                  <option value="shipping">Sedang Dikirim (Shipping)</option>
                  <option value="arrive">Sudah Sampai (Arrive)</option>
                </select>
              </div>

              {shippingStatus === 'shipping' && (
                <div className="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4 mt-2 bg-white p-4 border border-gallery-stone">
                  <div>
                    <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Tipe Pengiriman</label>
                    <input 
                      type="text" 
                      value={shippingMethod}
                      onChange={(e) => setShippingMethod(e.target.value)}
                      placeholder="Cth: Reguler / Kargo"
                      className="w-full border border-gallery-stone p-2"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Kurir</label>
                    <input 
                      type="text" 
                      value={shippingCourier}
                      onChange={(e) => setShippingCourier(e.target.value)}
                      placeholder="Cth: JNE / J&T"
                      className="w-full border border-gallery-stone p-2"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Nomor Resi</label>
                    <input 
                      type="text" 
                      value={trackingNumber}
                      onChange={(e) => setTrackingNumber(e.target.value)}
                      placeholder="Cth: JP12345678"
                      className="w-full border border-gallery-stone p-2"
                      required
                    />
                  </div>
                </div>
              )}
              
              <div className="md:col-span-2 text-right mt-2">
                <button type="submit" className="bg-gallery-ink text-white px-8 py-3 text-xs font-bold tracking-widest uppercase hover:bg-black transition-colors">
                  Simpan Perubahan Status
                </button>
              </div>
            </form>
          </div>

          {selectedTx.invoice_url && (
            <div className="pt-8 border-t border-gallery-stone text-right mt-8">
              <a href={selectedTx.invoice_url} target="_blank" rel="noreferrer" className="inline-block bg-gallery-ink text-white px-8 py-3 text-sm tracking-widest uppercase hover:bg-black transition-colors">
                View Invoice
              </a>
            </div>
          )}
        </div>
      ) : (
        <div className="bg-gallery-white border border-gallery-stone overflow-hidden">
          <table className="w-full text-left text-sm">
            <thead className="bg-gallery-stone/30 border-b border-gallery-stone text-xs uppercase tracking-widest text-gallery-subtle">
              <tr>
                <th className="px-6 py-4 font-normal">Order ID</th>
                <th className="px-6 py-4 font-normal">Date</th>
                <th className="px-6 py-4 font-normal">Customer</th>
                <th className="px-6 py-4 font-normal">Amount</th>
                <th className="px-6 py-4 font-normal">Status</th>
                <th className="px-6 py-4 font-normal text-right">Action</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gallery-stone">
              {transactions.map(t => (
                <tr key={t.id} className="hover:bg-gallery-stone/10 transition-colors">
                  <td className="px-6 py-4 font-mono text-xs">#{t.id.toString().padStart(4, '0')}</td>
                  <td className="px-6 py-4 text-gallery-subtle text-xs">{new Date(t.created_at).toLocaleDateString()}</td>
                  <td className="px-6 py-4">{t.customer_name}</td>
                  <td className="px-6 py-4">Rp {Number(t.total_amount).toLocaleString('id-ID')}</td>
                  <td className="px-6 py-4">
                    <span className={`px-2 py-1 text-[10px] tracking-widest uppercase rounded-sm ${
                      t.status === 'PAID' ? 'bg-green-100 text-green-800' :
                      t.status === 'PENDING' ? 'bg-yellow-100 text-yellow-800' :
                      'bg-red-100 text-red-800'
                    }`}>
                      {t.status}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-right">
                    <button onClick={() => loadDetail(t.id)} className="text-xs uppercase tracking-widest text-blue-600 hover:underline">
                      View Details
                    </button>
                  </td>
                </tr>
              ))}
              {transactions.length === 0 && (
                <tr><td colSpan="6" className="px-6 py-8 text-center text-gallery-subtle text-xs uppercase tracking-widest">No transactions yet</td></tr>
              )}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
