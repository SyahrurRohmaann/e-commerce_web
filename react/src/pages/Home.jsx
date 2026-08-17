import { useState, useEffect } from 'react';
import api from '../lib/axios';
import { useCartStore } from '../store/cart';
import { useCurrencyStore } from '../store/currency';
import { HeroSlider } from '../components/HeroSlider';
import { ProductDiscovery } from '../components/ProductDiscovery';

export function Home() {
  const [products, setProducts] = useState([]);
  const addItem = useCartStore(state => state.addItem);
  const format = useCurrencyStore(state => state.format);
  
  useEffect(() => {
    api.get('/catalog').then(res => setProducts(res.data.data));
  }, []);

  return (
    <div className="animate-in fade-in duration-1000">
      <HeroSlider />
      <ProductDiscovery products={products} addItem={addItem} format={format} />
    </div>
  );
}
