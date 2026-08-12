import { useState, useEffect } from 'react';
import api from '../lib/axios';
import { useCartStore } from '../store/cart';
import { HeroSlider } from '../components/HeroSlider';

export function Home() {
  const [products, setProducts] = useState([]);
  const addItem = useCartStore(state => state.addItem);
  
  useEffect(() => {
    api.get('/catalog').then(res => setProducts(res.data.data));
  }, []);

  if (products.length === 0) return <div className="h-screen flex items-center justify-center text-gallery-subtle uppercase tracking-widest text-sm">Curating collection...</div>;

  const galleryProducts = products;

  return (
    <div className="animate-in fade-in duration-1000">
      {/* Hero Slider Section */}
      <HeroSlider />

      {/* Gallery Section */}
      <section id="collection" className="max-w-7xl mx-auto px-6 py-32">
        <div className="flex justify-between items-end mb-16 border-b border-gallery-stone pb-8">
          <h2 className="text-3xl font-serif">The Collection</h2>
          <span className="text-sm tracking-widest uppercase text-gallery-subtle">{galleryProducts.length} Pieces</span>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-12 gap-y-24">
          {galleryProducts.map(p => (
            <div key={p.id} className="group flex flex-col">
              <div className="aspect-[4/5] bg-gallery-stone mb-6 overflow-hidden relative">
                <img 
                  src={p.image_url} 
                  alt={p.name}
                  className={`w-full h-full object-cover transition-opacity duration-700 group-hover:scale-105 ${p.hover_image_url ? 'group-hover:opacity-0 absolute inset-0 z-10' : ''}`}
                  onError={(e) => { e.target.src = 'https://images.unsplash.com/photo-1584916201218-f4242ceb4809?auto=format&fit=crop&q=80&w=800'; }}
                />
                {p.hover_image_url && (
                    <img 
                      src={p.hover_image_url} 
                      alt={`${p.name} alternate view`}
                      className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 absolute inset-0 z-0"
                      onError={(e) => { e.target.style.display = 'none'; }}
                    />
                )}
                <button 
                  onClick={() => addItem(p)} 
                  disabled={p.stock < 1}
                  className="absolute bottom-0 left-0 z-20 w-full bg-gallery-ink text-white py-4 text-xs tracking-widest uppercase translate-y-full group-hover:translate-y-0 transition-transform duration-300 disabled:bg-gallery-subtle"
                >
                  {p.stock < 1 ? 'Unavailable' : 'Add to Cart'}
                </button>
              </div>
              <div className="flex justify-between items-start">
                <div>
                  <h3 className="text-lg font-serif mb-1">{p.name}</h3>
                  <p className="text-xs text-gallery-subtle uppercase tracking-widest">{p.category?.name}</p>
                </div>
                <p className="text-sm">${Number(p.price).toLocaleString()}</p>
              </div>
            </div>
          ))}
        </div>
      </section>
    </div>
  );
}
