import { useState, useEffect, useRef, useCallback } from 'react';
import api from '../lib/axios';

// Helper to calculate Tailwind positioning classes for 9-point grid
function getPositionClasses(pos = 'tc') {
  const map = {
    tl: { container: 'top-12 left-12 text-left items-start', align: 'text-left' },
    tc: { container: 'top-12 left-1/2 -translate-x-1/2 text-center items-center', align: 'text-center' },
    tr: { container: 'top-12 right-12 text-right items-end', align: 'text-right' },
    ml: { container: 'top-1/2 left-12 -translate-y-1/2 text-left items-start', align: 'text-left' },
    mc: { container: 'top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-center items-center', align: 'text-center' },
    mr: { container: 'top-1/2 right-12 -translate-y-1/2 text-right items-end', align: 'text-right' },
    bl: { container: 'bottom-28 left-12 text-left items-start', align: 'text-left' },
    bc: { container: 'bottom-28 left-1/2 -translate-x-1/2 text-center items-center', align: 'text-center' },
    br: { container: 'bottom-28 right-12 text-right items-end', align: 'text-right' },
  };
  return map[pos] || map.tc;
}

export function HeroSlider() {
  const [banners, setBanners] = useState([]);
  const [currentIndex, setCurrentIndex] = useState(0);
  const [isDragging, setIsDragging] = useState(false);
  const [dragStart, setDragStart] = useState(0);
  const [dragDelta, setDragDelta] = useState(0);
  const [duration, setDuration] = useState(5000);
  const [isPaused, setIsPaused] = useState(false);

  const containerRef = useRef(null);
  const autoPlayTimer = useRef(null);
  const transitionRef = useRef(false);

  useEffect(() => {
    api.get('/hero-banners').then(res => {
      const data = res.data.data || [];
      setBanners(data);
      if (data.length > 0) {
        const dur = data[0].duration_ms || 5000;
        setDuration(dur);
      }
    }).catch(() => {
      // Fallback banners if API fails
      setBanners([
        {
          id: 1,
          title: 'NEW COLLECTION',
          caption: 'Spring / Summer 2026',
          subtitle: 'Discover our latest arrivals designed for movement',
          image_url: 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&q=80&w=1600&h=900',
          title_position: 'tc',
          caption_position: 'tc',
          button_position: 'bc',
          button_text: 'Shop Now',
          button_url: '/catalog',
          duration_ms: 5000,
        },
        {
          id: 2,
          title: 'SUMMER SALE',
          caption: 'Limited Time Offer',
          subtitle: 'Up to 50% off selected items',
          image_url: 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&q=80&w=1600&h=900',
          title_position: 'tl',
          caption_position: 'tl',
          button_position: 'bl',
          button_text: 'View Deals',
          button_url: '/sale',
          duration_ms: 5000,
        },
        {
          id: 3,
          title: 'PREMIUM QUALITY',
          caption: 'Crafted with Precision',
          subtitle: 'Experience luxury in every detail',
          image_url: 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&q=80&w=1600&h=900',
          title_position: 'tr',
          caption_position: 'tr',
          button_position: 'br',
          button_text: 'Learn More',
          button_url: '/about',
          duration_ms: 5000,
        },
      ]);
    });
  }, []);

  const goToSlide = useCallback((index) => {
    if (banners.length === 0 || transitionRef.current) return;
    transitionRef.current = true;
    setCurrentIndex(index);
    setTimeout(() => { transitionRef.current = false; }, 600);
  }, [banners.length]);

  const nextSlide = useCallback(() => {
    const next = (currentIndex + 1) % banners.length;
    goToSlide(next);
  }, [currentIndex, banners.length, goToSlide]);

  const prevSlide = useCallback(() => {
    const prev = (currentIndex - 1 + banners.length) % banners.length;
    goToSlide(prev);
  }, [currentIndex, banners.length, goToSlide]);

  // Auto-play
  useEffect(() => {
    if (banners.length === 0 || isPaused) {
      if (autoPlayTimer.current) clearInterval(autoPlayTimer.current);
      return;
    }

    autoPlayTimer.current = setInterval(() => {
      nextSlide();
    }, duration);

    return () => {
      if (autoPlayTimer.current) clearInterval(autoPlayTimer.current);
    };
  }, [banners.length, duration, isPaused, nextSlide]);

  // Drag handlers
  const handlePointerDown = (e) => {
    setIsDragging(true);
    setDragStart(e.clientX);
    setDragDelta(0);
  };

  const handlePointerMove = (e) => {
    if (!isDragging) return;
    setDragDelta(e.clientX - dragStart);
  };

  const handlePointerUp = () => {
    if (!isDragging) return;
    setIsDragging(false);

    const threshold = 80;
    if (dragDelta < -threshold) {
      nextSlide();
    } else if (dragDelta > threshold) {
      prevSlide();
    }

    setDragStart(0);
    setDragDelta(0);
  };

  const handleTouchStart = (e) => {
    setIsDragging(true);
    setDragStart(e.touches[0].clientX);
    setDragDelta(0);
  };

  const handleTouchMove = (e) => {
    if (!isDragging) return;
    setDragDelta(e.touches[0].clientX - dragStart);
  };

  const handleTouchEnd = () => {
    if (!isDragging) return;
    setIsDragging(false);

    const threshold = 50;
    if (dragDelta < -threshold) {
      nextSlide();
    } else if (dragDelta > threshold) {
      prevSlide();
    }

    setDragStart(0);
    setDragDelta(0);
  };

  if (banners.length === 0) {
    return (
      <section className="relative h-screen w-full bg-gallery-white flex items-center justify-center">
        <div className="text-gallery-subtle uppercase tracking-widest text-sm">Loading collection...</div>
      </section>
    );
  }

  const banner = banners[currentIndex];
  const titlePos = getPositionClasses(banner.title_position || 'tc');
  const captionPos = getPositionClasses(banner.caption_position || 'tc');
  const buttonPos = getPositionClasses(banner.button_position || 'bc');

  return (
    <section
      className="relative h-[85vh] sm:h-[90vh] w-full overflow-hidden bg-gallery-white"
      onPointerMove={handlePointerMove}
      onPointerUp={handlePointerUp}
      onTouchMove={handleTouchMove}
      onTouchEnd={handleTouchEnd}
    >
      {/* Background Image Container */}
      <div
        ref={containerRef}
        className="absolute inset-0 cursor-grab active:cursor-grabbing"
        onPointerDown={handlePointerDown}
        onTouchStart={handleTouchStart}
      >
        {banners.map((item, index) => {
          const isActive = index === currentIndex;
          const isNext = index === (currentIndex + 1) % banners.length;
          const isPrev = index === (currentIndex - 1 + banners.length) % banners.length;

          return (
            <div
              key={item.id}
              className={`absolute inset-0 transition-all duration-700 ease-out ${
                isActive
                  ? 'opacity-100 scale-100'
                  : isNext || isPrev
                  ? 'opacity-0 scale-105'
                  : 'opacity-0 scale-105'
              }`}
              style={{
                transform: isActive
                  ? 'scale(1.0) translateX(0)'
                  : dragDelta !== 0 && index === currentIndex
                  ? `scale(1.0) translateX(${dragDelta * 0.3}px)`
                  : undefined,
                transition: isDragging ? 'none' : 'all 700ms cubic-bezier(0.25, 0.46, 0.45, 0.94)',
              }}
            >
              <div className="absolute inset-0">
                <img
                  src={item.image_url}
                  alt={item.title}
                  className="w-full h-full object-cover"
                />
                <div className="absolute inset-0 bg-black/25"></div>
              </div>
            </div>
          );
        })}
      </div>

      {/* Dynamic 9-Point Grid Layers */}
      
      {/* Title Layer */}
      {banner.title && (
        <div className={`absolute z-10 max-w-2xl px-6 flex flex-col pointer-events-none text-white ${titlePos.container}`}>
          <h1 className="text-4xl sm:text-6xl md:text-7xl font-serif font-bold tracking-wide animate-in fade-in slide-in-from-bottom-8 duration-700">
            {banner.title}
          </h1>
          {banner.subtitle && (
            <p className="text-base sm:text-lg md:text-xl font-light tracking-wide mt-3 opacity-90 animate-in fade-in slide-in-from-bottom-4 duration-700 delay-150">
              {banner.subtitle}
            </p>
          )}
        </div>
      )}

      {/* Caption Layer */}
      {banner.caption && (
        <div className={`absolute z-10 max-w-md px-6 pointer-events-none text-white ${captionPos.container}`}>
          <span className="inline-block text-xs sm:text-sm font-bold tracking-[0.25em] uppercase bg-white/20 backdrop-blur-md px-4 py-2 rounded-full border border-white/30 animate-in fade-in slide-in-from-bottom-4 duration-700 delay-200">
            {banner.caption}
          </span>
        </div>
      )}

      {/* Button Layer */}
      {banner.button_text && (
        <div className={`absolute z-10 px-6 ${buttonPos.container}`}>
          <a
            href={banner.button_url || '#'}
            onClick={(e) => {
              if (!banner.button_url || banner.button_url === '#') {
                e.preventDefault();
              }
            }}
            className="inline-block bg-white text-gallery-ink px-10 py-4 text-xs font-bold tracking-widest uppercase rounded-full hover:bg-gallery-stone/90 hover:-translate-y-1 hover:shadow-2xl transition-all duration-300 animate-in fade-in slide-in-from-bottom-4 duration-700 delay-300"
          >
            {banner.button_text}
          </a>
        </div>
      )}

      {/* Navigation Arrows (Fixed Center Vertically) */}
      {banners.length > 1 && (
        <>
          <button
            onClick={prevSlide}
            onMouseEnter={() => setIsPaused(true)}
            onMouseLeave={() => setIsPaused(false)}
            className="absolute left-4 sm:left-8 top-1/2 -translate-y-1/2 z-20 w-12 h-12 bg-white/20 backdrop-blur-sm border border-white/30 rounded-full flex items-center justify-center text-white hover:bg-white/40 transition-all duration-300 hover:-translate-x-1"
          >
            ←
          </button>
          <button
            onClick={nextSlide}
            onMouseEnter={() => setIsPaused(true)}
            onMouseLeave={() => setIsPaused(false)}
            className="absolute right-4 sm:right-8 top-1/2 -translate-y-1/2 z-20 w-12 h-12 bg-white/20 backdrop-blur-sm border border-white/30 rounded-full flex items-center justify-center text-white hover:bg-white/40 transition-all duration-300 hover:translate-x-1"
          >
            →
          </button>
        </>
      )}

      {/* Dot Indicators (Fixed Bottom-Center) */}
      <div className="absolute bottom-8 left-1/2 -translate-x-1/2 z-20 flex gap-3">
        {banners.map((_, index) => (
          <button
            key={index}
            onClick={() => {
              goToSlide(index);
              setIsPaused(true);
              setTimeout(() => setIsPaused(false), 3000);
            }}
            className={`transition-all duration-300 rounded-full ${
              index === currentIndex
                ? 'w-8 h-2 bg-white'
                : 'w-2 h-2 bg-white/50 hover:bg-white/80'
            }`}
            aria-label={`Go to slide ${index + 1}`}
          />
        ))}
      </div>
    </section>
  );
}
