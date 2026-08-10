import { useState, useEffect } from 'react';
import api from '../lib/axios';
import { useCartStore } from '../store/cart';

export function Home() {
  const [products, setProducts] = useState([]);
  const addItem = useCartStore(state => state.addItem);
  
  // Parallax / Magnetic State
  const [mousePos, setMousePos] = useState({ x: 0, y: 0 });

  useEffect(() => {
    api.get('/catalog').then(res => setProducts(res.data.data));
  }, []);

  const handleMouseMove = (e) => {
    // Menghitung posisi relative dari tengah hero section (-1 sampai 1)
    const { clientX, clientY } = e;
    const x = (clientX / window.innerWidth) * 2 - 1;
    const y = (clientY / window.innerHeight) * 2 - 1;
    setMousePos({ x, y });
  };

  const handleMouseLeave = () => {
    // Kembali ke posisi 0 saat kursor keluar
    setMousePos({ x: 0, y: 0 });
  };

  if (products.length === 0) return <div className="h-screen flex items-center justify-center text-gallery-subtle uppercase tracking-widest text-sm">Curating collection...</div>;

  const heroProduct = products[0];
  const galleryProducts = products.slice(1);

  return (
    <div className="animate-in fade-in duration-1000">
      {/* Hero Section */}
      <section 
        className="relative min-h-[90vh] w-full bg-gallery-white pt-20 flex flex-col group overflow-hidden"
        onMouseMove={handleMouseMove}
        onMouseLeave={handleMouseLeave}
      >
        {/* Top Typography Bar */}
        <div className="w-full max-w-7xl mx-auto px-6 pt-10 flex items-center justify-between text-[10px] sm:text-xs font-bold tracking-widest text-gallery-ink uppercase relative z-20">
          <span className="hidden sm:inline">MOVE COMFORTABLY</span>
          <div className="hidden sm:block flex-1 h-px bg-gallery-stone mx-6"></div>
          <span>LIVE FREELY</span>
          <div className="hidden sm:block flex-1 h-px bg-gallery-stone mx-6"></div>
          <span className="hidden sm:inline">FEEL CONFIDENT</span>
        </div>

        <div className="flex-1 relative flex items-center justify-center w-full max-w-7xl mx-auto px-6 mt-4 pb-24">
          {/* Huge Background Text - Moves opposite to mouse */}
          <div 
            className="absolute inset-0 flex items-center justify-center z-0 pointer-events-none transition-transform duration-300 ease-out"
            style={{ transform: `translate(${mousePos.x * -30}px, ${mousePos.y * -20}px)` }}
          >
            <h1 className="text-[18vw] font-sans font-black tracking-tighter text-gallery-ink leading-[0.8] whitespace-nowrap opacity-95 mb-16">
              PURE COMFORT
            </h1>
          </div>

          {/* Center Image - Moves with mouse but subtly */}
          <div 
            className="absolute bottom-0 z-10 w-[400px] sm:w-[500px] max-w-full flex items-end justify-center transition-transform duration-200 ease-out"
            style={{ transform: `translate(${mousePos.x * 20}px, ${mousePos.y * 10}px)` }}
          >
            <img 
              src={heroProduct.image_url} 
              alt={heroProduct.name}
              className="w-full h-[150%] object-contain object-bottom drop-shadow-2xl transition-all duration-700 ease-out group-hover:scale-105 group-hover:drop-shadow-3xl"
              onError={(e) => { e.target.src = 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&q=80&w=800&h=1200'; }}
            />
          </div>

          {/* Left Description */}
          <div className="absolute left-6 bottom-32 max-w-[240px] z-20 hidden md:block">
            <p className="text-gallery-ink text-sm leading-relaxed font-medium">
              {heroProduct.description || "Designed for everyday movement. Soft fabrics, relaxed fits, and effortless comfort."}
            </p>
          </div>

          {/* Right Floating Card - Floating and moving with mouse */}
          <div 
            className="absolute right-6 top-1/4 z-20 hidden lg:flex flex-col bg-white/30 backdrop-blur-md border border-white/50 p-4 rounded-xl shadow-xl w-[260px] transform rotate-3 transition-all duration-500 ease-out group/card hover:rotate-0 hover:-translate-y-4 hover:shadow-2xl hover:border-white/80"
            style={{ transform: `translate(${mousePos.x * 40}px, ${mousePos.y * 30}px) rotate(3deg)` }}
          >
            <div className="flex justify-between items-center mb-3">
              <span className="text-xs font-bold tracking-widest uppercase text-gallery-ink">{heroProduct.name}</span>
              <span className="text-gallery-ink font-bold leading-none cursor-pointer">⋮</span>
            </div>
            <div className="bg-gallery-stone aspect-[3/4] mb-4 rounded-lg overflow-hidden shadow-inner">
              <img 
                src={heroProduct.image_url} 
                alt={heroProduct.name}
                className="w-full h-full object-cover transition-transform duration-700 ease-out group-hover/card:scale-110"
                onError={(e) => { e.target.src = 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&q=80&w=800'; }}
              />
            </div>
            <div className="flex justify-between items-center text-sm font-bold text-gallery-ink">
              <span className="uppercase tracking-widest text-[10px] text-gallery-subtle">FABRIC</span>
              <span>${Number(heroProduct.price).toLocaleString()}</span>
            </div>
          </div>

          {/* Bottom CTA Buttons */}
          <div className="absolute bottom-10 left-1/2 -translate-x-1/2 flex flex-col sm:flex-row gap-4 z-30 w-full sm:w-auto px-6">
            <button 
              onClick={() => addItem(heroProduct)}
              disabled={heroProduct.stock < 1}
              className="bg-[#E54825] text-white px-8 py-3.5 text-xs font-bold tracking-widest uppercase rounded-full hover:bg-[#c93d1e] hover:-translate-y-1 hover:shadow-xl hover:shadow-[#E54825]/30 transition-all duration-300 disabled:opacity-50 w-full sm:w-auto whitespace-nowrap"
            >
              {heroProduct.stock < 1 ? 'Out of Stock' : 'Shop the Collection'}
            </button>
            <button 
              onClick={() => document.getElementById('collection').scrollIntoView({ behavior: 'smooth' })}
              className="bg-white/20 border border-gallery-ink/20 backdrop-blur-sm text-gallery-ink px-8 py-3.5 text-xs font-bold tracking-widest uppercase rounded-full hover:bg-white/40 hover:border-gallery-ink/50 hover:-translate-y-1 transition-all duration-300 w-full sm:w-auto whitespace-nowrap"
            >
              Explore New Arrivals
            </button>
          </div>
        </div>
      </section>

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
