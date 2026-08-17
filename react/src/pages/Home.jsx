import { useCallback, useEffect, useRef, useState } from 'react';
import api from '../lib/axios';
import { useCartStore } from '../store/cart';
import { useCurrencyStore } from '../store/currency';
import { HeroSlider } from '../components/HeroSlider';
import { ProductDiscovery } from '../components/ProductDiscovery';

function CollectionState({ children }) {
  return (
    <section id="collection" className="max-w-7xl mx-auto px-6 py-24 sm:py-32 text-center">
      {children}
    </section>
  );
}

export function Home() {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);
  const requestController = useRef(null);
  const mounted = useRef(true);
  const addItem = useCartStore(state => state.addItem);
  const format = useCurrencyStore(state => state.format);

  const loadProducts = useCallback(async () => {
    requestController.current?.abort();
    const controller = new AbortController();
    requestController.current = controller;
    setLoading(true);
    setError(false);

    try {
      const response = await api.get('/catalog', { signal: controller.signal });
      if (!Array.isArray(response.data?.data)) throw new Error('Invalid catalog response');
      if (mounted.current && !controller.signal.aborted) setProducts(response.data.data);
    } catch {
      if (mounted.current && !controller.signal.aborted) {
        setProducts([]);
        setError(true);
      }
    } finally {
      if (mounted.current && !controller.signal.aborted) setLoading(false);
    }
  }, []);

  useEffect(() => {
    mounted.current = true;
    loadProducts();
    return () => {
      mounted.current = false;
      requestController.current?.abort();
    };
  }, [loadProducts]);

  return (
    <div className="animate-in fade-in duration-1000">
      <HeroSlider />
      {loading ? (
        <CollectionState>
          <p role="status" className="text-sm uppercase tracking-[0.22em] text-gallery-subtle">
            Curating the collection…
          </p>
        </CollectionState>
      ) : error ? (
        <CollectionState>
          <p className="mb-4 text-[10px] font-semibold uppercase tracking-[0.3em] text-gallery-subtle">The seasonal edit</p>
          <h2 className="text-4xl sm:text-5xl font-serif tracking-[-0.025em]">The collection is temporarily unavailable</h2>
          <p className="mx-auto mt-5 max-w-xl text-sm leading-7 text-gallery-subtle">
            We could not prepare the edit right now. Try again when you are ready.
          </p>
          <button type="button" onClick={loadProducts} className="mt-8 border border-gallery-ink px-6 py-3 text-xs font-semibold uppercase tracking-[0.18em] hover:bg-gallery-ink hover:text-white transition-colors">
            Retry collection
          </button>
        </CollectionState>
      ) : products.length === 0 ? (
        <CollectionState>
          <p className="mb-4 text-[10px] font-semibold uppercase tracking-[0.3em] text-gallery-subtle">The seasonal edit</p>
          <h2 className="text-4xl sm:text-5xl font-serif tracking-[-0.025em]">The next edit is on its way</h2>
          <p className="mx-auto mt-5 max-w-xl text-sm leading-7 text-gallery-subtle">
            New pieces are being considered. Return soon to discover the collection.
          </p>
          <button type="button" onClick={loadProducts} className="mt-8 border border-gallery-ink px-6 py-3 text-xs font-semibold uppercase tracking-[0.18em] hover:bg-gallery-ink hover:text-white transition-colors">
            Retry collection
          </button>
        </CollectionState>
      ) : (
        <ProductDiscovery products={products} addItem={addItem} format={format} />
      )}
    </div>
  );
}
