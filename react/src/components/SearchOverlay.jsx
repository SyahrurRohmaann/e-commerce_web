import { useEffect, useRef, useState, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../lib/axios';
import { useCurrencyStore } from '../store/currency';

function safeImageUrl(value) {
  if (!value) return '';
  try {
    const url = new URL(value, window.location.origin);
    return url.protocol === 'https:' || url.protocol === 'http:' ? url.href : '';
  } catch {
    return '';
  }
}

/**
 * Live search input with an inline results container.
 * Debounced queries hit /api/catalog?search=... and render a dropdown
 * with product thumbnails + a "view all" action. Fully non-blocking.
 */
export function SearchOverlay() {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState([]);
  const [open, setOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  const [failed, setFailed] = useState(false);
  const wrapperRef = useRef(null);
  const abortRef = useRef(null);
  const inputRef = useRef(null);
  const navigate = useNavigate();
  const format = useCurrencyStore((state) => state.format);

  const close = useCallback(() => {
    setOpen(false);
  }, []);

  // Close on outside click / Escape / route change.
  useEffect(() => {
    function handleClickOutside(event) {
      if (wrapperRef.current && !wrapperRef.current.contains(event.target)) close();
    }
    function handleKeyDown(event) {
      if (event.key === 'Escape') close();
    }
    document.addEventListener('mousedown', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
      document.removeEventListener('keydown', handleKeyDown);
    };
  }, [close]);

  useEffect(() => () => abortRef.current?.abort(), []);

  const runSearch = useCallback(async (term) => {
    abortRef.current?.abort();
    if (!term.trim()) {
      setResults([]);
      setLoading(false);
      setFailed(false);
      setOpen(false);
      return;
    }

    const controller = new AbortController();
    abortRef.current = controller;
    setLoading(true);
    setFailed(false);
    setOpen(true);

    try {
      const response = await api.get('/catalog', {
        params: { search: term.trim(), limit: 6 },
        signal: controller.signal,
      });
      if (controller.signal.aborted) return;
      setResults(Array.isArray(response.data?.data) ? response.data.data.slice(0, 6) : []);
      setLoading(false);
    } catch {
      if (controller.signal.aborted) return;
      setFailed(true);
      setLoading(false);
      setResults([]);
    }
  }, []);

  // Debounced search.
  useEffect(() => {
    const timer = setTimeout(() => runSearch(query), 250);
    return () => clearTimeout(timer);
  }, [query, runSearch]);

  const selectProduct = (id) => {
    navigate(`/catalog/${id}`);
    setQuery('');
    setOpen(false);
  };

  const submitSearch = (event) => {
    event.preventDefault();
    if (!query.trim()) return;
    navigate(`/catalog?q=${encodeURIComponent(query.trim())}`);
    setOpen(false);
    setQuery('');
  };

  return (
    <div ref={wrapperRef} className="relative hidden md:block">
      <form onSubmit={submitSearch} role="search" aria-label="Search products">
        <input
          ref={inputRef}
          type="search"
          value={query}
          onChange={(event) => setQuery(event.target.value)}
          onFocus={() => query.trim() && setOpen(true)}
          placeholder="Search..."
          aria-label="Search products"
          className="w-44 lg:w-56 border-b border-gallery-stone bg-transparent py-1.5 text-xs uppercase tracking-widest placeholder:text-gallery-subtle focus:outline-none focus:border-gallery-ink transition-colors"
        />
        {loading && (
          <span className="absolute -right-1 top-1/2 -translate-y-1/2 text-[9px] text-gallery-subtle animate-pulse">…</span>
        )}
      </form>

      {open && (
        <div
          className="absolute right-0 top-full mt-2 w-80 bg-gallery-white border border-gallery-stone shadow-xl z-50"
          role="listbox"
          aria-label="Search results"
        >
          <div className="px-4 py-2 text-[9px] uppercase tracking-[0.2em] text-gallery-subtle border-b border-gallery-stone/40">
            {failed ? 'Search unavailable' : loading ? 'Searching…' : 'Results'}
          </div>

          {!failed && !loading && results.length === 0 && (
            <div className="px-4 py-6 text-center text-xs uppercase tracking-widest text-gallery-subtle">
              No products found
            </div>
          )}

          {!failed && !loading && results.length > 0 && (
            <ul className="max-h-80 overflow-y-auto">
              {results.map((product) => (
                <li key={product.id}>
                  <button
                    type="button"
                    onClick={() => selectProduct(product.id)}
                    className="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-gallery-stone/30 transition-colors"
                  >
                    {safeImageUrl(product.image_url) ? (
                      <img src={safeImageUrl(product.image_url)} alt="" className="w-12 h-14 object-cover bg-gallery-stone shrink-0" />
                    ) : (
                      <span className="w-12 h-14 bg-gallery-stone shrink-0" />
                    )}
                    <span className="min-w-0 flex-1">
                      <span className="block text-sm text-gallery-ink truncate">{product.name}</span>
                      <span className="block text-[11px] text-gallery-subtle mt-0.5">
                        {typeof product.price === 'number' ? format(product.price) : ''}
                      </span>
                    </span>
                  </button>
                </li>
              ))}
            </ul>
          )}

          {!failed && !loading && results.length > 0 && (
            <button
              type="button"
              onClick={submitSearch}
              className="w-full px-4 py-3 text-[10px] uppercase tracking-[0.2em] text-gallery-ink border-t border-gallery-stone/40 hover:bg-gallery-stone/30 transition-colors text-center"
            >
              View all results
            </button>
          )}
        </div>
      )}
    </div>
  );
}