import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../lib/axios';

export function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const navigate = useNavigate();

  useEffect(() => {
    const token = localStorage.getItem('token');
    const role = localStorage.getItem('role');
    if (token) {
      if (role === 'admin') navigate('/admin');
      else navigate('/');
    }
  }, [navigate]);

  const handleLogin = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    
    try {
      const res = await api.post('/login', { email, password });
      localStorage.setItem('token', res.data.access_token);
      localStorage.setItem('role', res.data.user.role);
      
      if (res.data.user.role === 'admin') {
        navigate('/admin');
      } else {
        navigate(-1); // Go back to where they came from (usually Cart)
      }
    } catch (err) {
      if (err.response?.status === 422) {
        setError(err.response.data.message || 'Invalid credentials');
      } else {
        setError('Login failed. Please verify your credentials and try again.');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-[80vh] flex items-center justify-center animate-in fade-in duration-700 px-6">
      <div className="w-full max-w-md">
        <div className="text-center mb-12">
          <h1 className="text-3xl font-serif mb-4">Welcome Back</h1>
          <p className="text-gallery-subtle tracking-widest uppercase text-xs">Enter your credentials to continue</p>
        </div>

        {error && (
          <div className="bg-red-50 text-red-800 border border-red-200 p-4 mb-8 text-sm text-center">
            {error}
          </div>
        )}

        <form onSubmit={handleLogin} className="space-y-6">
          <div>
            <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Email Address</label>
            <input 
              type="email" 
              value={email} 
              onChange={e => setEmail(e.target.value)} 
              className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink transition-colors"
              required 
            />
          </div>
          
          <div>
            <label className="block text-xs uppercase tracking-widest text-gallery-subtle mb-2">Password</label>
            <input 
              type="password" 
              value={password} 
              onChange={e => setPassword(e.target.value)} 
              className="w-full border-b border-gallery-stone bg-transparent py-3 focus:outline-none focus:border-gallery-ink transition-colors"
              required 
            />
          </div>

          <button 
            type="submit" 
            disabled={loading}
            className="w-full bg-gallery-ink text-white py-4 mt-8 text-sm tracking-widest uppercase hover:bg-black transition-colors disabled:opacity-70"
          >
            {loading ? 'Authenticating...' : 'Sign In'}
          </button>
        </form>
        
        {/* Helper info for dev environment */}
        <div className="mt-12 p-4 border border-gallery-stone text-xs text-gallery-subtle flex justify-between">
          <span>Admin: admin@admin.com</span>
          <span>Cust: customer@customer.com</span>
        </div>
      </div>
    </div>
  );
}