import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';

export function Forbidden() {
  const [countdown, setCountdown] = useState(3);
  const navigate = useNavigate();

  useEffect(() => {
    if (countdown === 0) {
      navigate('/');
      return;
    }

    const timer = setInterval(() => {
      setCountdown(prev => prev - 1);
    }, 1000);

    return () => clearInterval(timer);
  }, [countdown, navigate]);

  return (
    <div className="min-h-[70vh] flex flex-col items-center justify-center animate-in fade-in duration-500 px-6 text-center">
      <div className="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mb-6">
        <svg className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>
      <h1 className="text-3xl font-serif mb-4">Access Denied</h1>
      <p className="text-gallery-subtle tracking-widest uppercase text-sm mb-8">
        You do not have permission to access this page.
      </p>
      
      <div className="bg-gallery-stone/30 px-6 py-3 border border-gallery-stone text-sm">
        Returning to storefront in <span className="font-bold text-gallery-ink">{countdown}</span>...
      </div>
    </div>
  );
}
