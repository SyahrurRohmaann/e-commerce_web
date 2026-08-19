import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'sonner';
import api from '../lib/axios';
import LoadingState from '../components/ui/LoadingState';
import ErrorState from '../components/ui/ErrorState';

export function Profile() {
  const [user, setUser] = useState(null);
  const [formData, setFormData] = useState({
    name: '',
    phone: '',
    address: '',
    city: '',
    postal_code: ''
  });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const navigate = useNavigate();

  useEffect(() => {
    fetchProfile();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const fetchProfile = async () => {
    setError('');
    try {
      const res = await api.get('/profile');
      setUser(res.data.user);
      setFormData({
        name: res.data.user.name || '',
        phone: res.data.user.phone || '',
        address: res.data.user.address || '',
        city: res.data.user.city || '',
        postal_code: res.data.user.postal_code || ''
      });
    } catch (err) {
      if (err.response?.status === 401) {
        localStorage.removeItem('token');
        localStorage.removeItem('role');
        navigate('/login');
      } else {
        setError('Unable to load your profile.');
      }
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    
    try {
      const res = await api.put('/profile', formData);
      setUser(res.data.user);
      toast.success('Profile updated successfully.');
    } catch {
      toast.error('Failed to update profile.');
    } finally {
      setSaving(false);
    }
  };

  const handleLogout = async () => {
    try {
      await api.post('/logout');
      toast.success('Logged out successfully');
    } catch (err) {
      console.error('Logout error', err);
    } finally {
      localStorage.removeItem('token');
      localStorage.removeItem('role');
      navigate('/');
    }
  };

  if (loading) return <LoadingState label="Loading your profile" />;
  if (error) return <ErrorState message={error} onRetry={fetchProfile} />;
  if (!user) return <ErrorState message="Your profile is unavailable." onRetry={fetchProfile} />;

  return (
    <div className="max-w-2xl mx-auto p-6 animate-in fade-in duration-500">
      <div className="flex justify-between items-center mb-8 pb-4 border-b border-gallery-stone">
        <h1 className="text-3xl font-serif">Profile</h1>
        <button 
          onClick={handleLogout}
          className="text-sm tracking-widest uppercase text-red-600 hover:text-red-800 transition-colors"
        >
          Logout
        </button>
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        <div>
          <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Email</label>
          <input 
            type="email" 
            value={user.email} 
            disabled 
            className="w-full border-b border-gallery-stone bg-gray-50 py-3 text-gallery-subtle"
          />
        </div>
        
        <div>
          <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Name</label>
          <input 
            type="text" 
            value={formData.name} 
            onChange={e => setFormData({...formData, name: e.target.value})} 
            className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink transition-colors"
            required 
          />
        </div>

        <div>
          <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Phone</label>
          <input 
            type="text" 
            value={formData.phone} 
            onChange={e => setFormData({...formData, phone: e.target.value})} 
            className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink transition-colors"
          />
        </div>

        <div>
          <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Address</label>
          <textarea 
            value={formData.address} 
            onChange={e => setFormData({...formData, address: e.target.value})} 
            className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink transition-colors resize-none"
            rows="3"
          />
        </div>

        <div className="flex gap-4">
          <div className="flex-1">
            <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">City</label>
            <input 
              type="text" 
              value={formData.city} 
              onChange={e => setFormData({...formData, city: e.target.value})} 
              className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink transition-colors"
            />
          </div>
          <div className="flex-1">
            <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Postal Code</label>
            <input 
              type="text" 
              value={formData.postal_code} 
              onChange={e => setFormData({...formData, postal_code: e.target.value})} 
              className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink transition-colors"
            />
          </div>
        </div>

        <button 
          type="submit" 
          disabled={saving}
          className="w-full bg-gallery-ink text-white py-4 mt-8 text-sm tracking-widest uppercase hover:bg-black transition-colors disabled:opacity-70"
        >
          {saving ? 'Saving...' : 'Save Changes'}
        </button>
      </form>
    </div>
  );
}