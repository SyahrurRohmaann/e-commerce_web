import { useCallback, useEffect, useRef, useState } from 'react';
import api from '../lib/axios';

function HeroLink({ href, className, children }) {
  if (href?.startsWith('#')) {
    return <a href={href} className={className}>{children}</a>;
  }

  return <a href={href || '/#collection'} className={className}>{children}</a>;
}

const fallbackBanners = [
  {
    id: 1,
    title: 'Form, without noise.',
    caption: 'The new collection',
    subtitle: 'Clean lines, honest materials, and pieces selected to outlive the season.',
    image_url: 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&q=88&w=1800&h=1200',
    layout_direction: 'text-left',
    panel_theme: 'stone',
    image_position: '50% 45%',
    text_alignment: 'left',
    button_text: 'View the edit',
    button_url: '/#collection',
    duration_ms: 6000,
  },
];

const panelThemes = {
  ivory: 'bg-gallery-white text-gallery-ink',
  stone: 'bg-gallery-stone text-gallery-ink',
  ink: 'bg-gallery-ink text-gallery-white',
};

export function HeroSlider() {
  const [banners, setBanners] = useState([]);
  const [currentIndex, setCurrentIndex] = useState(0);
  const [isPaused, setIsPaused] = useState(false);
  const transitionRef = useRef(false);

  useEffect(() => {
    api.get('/hero-banners')
      .then((res) => setBanners(res.data.data?.length ? res.data.data : fallbackBanners))
      .catch(() => setBanners(fallbackBanners));
  }, []);

  const goToSlide = useCallback((index) => {
    if (!banners.length || transitionRef.current) return;
    transitionRef.current = true;
    setCurrentIndex(index);
    window.setTimeout(() => { transitionRef.current = false; }, 700);
  }, [banners.length]);

  const nextSlide = useCallback(() => {
    goToSlide((currentIndex + 1) % banners.length);
  }, [banners.length, currentIndex, goToSlide]);

  const previousSlide = useCallback(() => {
    goToSlide((currentIndex - 1 + banners.length) % banners.length);
  }, [banners.length, currentIndex, goToSlide]);

  useEffect(() => {
    if (banners.length < 2 || isPaused) return undefined;
    const duration = banners[currentIndex]?.duration_ms || 6000;
    const timer = window.setTimeout(nextSlide, duration);
    return () => window.clearTimeout(timer);
  }, [banners, currentIndex, isPaused, nextSlide]);

  if (!banners.length) {
    return (
      <section className="h-[76vh] min-h-[620px] bg-gallery-stone flex items-center justify-center">
        <span className="text-xs uppercase tracking-[0.28em] text-gallery-subtle">Curating the edit...</span>
      </section>
    );
  }

  const banner = banners[currentIndex];
  const textFirst = (banner.layout_direction || 'text-left') === 'text-left';
  const centered = banner.text_alignment === 'center';
  const theme = panelThemes[banner.panel_theme] || panelThemes.ivory;
  const position = banner.image_position || '50% 50%';
  const counter = String(currentIndex + 1).padStart(2, '0');
  const total = String(banners.length).padStart(2, '0');

  return (
    <section
      className="relative h-[76vh] min-h-[620px] max-h-[880px] overflow-hidden"
      onMouseEnter={() => setIsPaused(true)}
      onMouseLeave={() => setIsPaused(false)}
    >
      <div className={`h-full grid grid-cols-1 lg:grid-cols-[43%_57%] ${textFirst ? '' : 'lg:grid-cols-[57%_43%]'}`}>
        <div
          key={`copy-${banner.id}`}
          className={`${theme} ${textFirst ? 'lg:order-1' : 'lg:order-2'} flex items-center px-8 sm:px-14 lg:px-[7vw] py-14 animate-in fade-in duration-700`}
        >
          <div className={`w-full max-w-xl ${centered ? 'text-center mx-auto' : 'text-left'}`}>
            {banner.caption && (
              <p className="mb-7 text-[11px] font-semibold uppercase tracking-[0.28em] opacity-60">
                {banner.caption}
              </p>
            )}
            {banner.title && (
              <h1 className="text-[clamp(3.4rem,6vw,7rem)] leading-[0.9] tracking-[-0.045em] font-serif whitespace-pre-line">
                {banner.title}
              </h1>
            )}
            {banner.subtitle && (
              <p className={`mt-8 text-sm sm:text-base leading-7 opacity-65 ${centered ? 'mx-auto' : ''} max-w-md`}>
                {banner.subtitle}
              </p>
            )}
            {banner.button_text && (
              <HeroLink
                href={banner.button_url || '/#collection'}
                className="inline-flex items-center gap-4 mt-10 pb-2 border-b border-current text-[11px] font-semibold uppercase tracking-[0.24em] hover:gap-7 transition-all duration-300"
              >
                {banner.button_text}
                <span aria-hidden="true">→</span>
              </HeroLink>
            )}
          </div>
        </div>

        <div className={`${textFirst ? 'lg:order-2' : 'lg:order-1'} relative min-h-[320px] overflow-hidden bg-gallery-stone`}>
          {banners.map((item, index) => (
            <img
              key={item.id}
              src={item.image_url}
              alt={item.title || item.caption || 'Alagance collection'}
              className={`absolute inset-0 w-full h-full object-cover transition-all duration-1000 ease-out ${
                index === currentIndex ? 'opacity-100 scale-100' : 'opacity-0 scale-[1.025]'
              }`}
              style={{ objectPosition: index === currentIndex ? position : (item.image_position || '50% 50%') }}
            />
          ))}
          {banners.length > 1 && (
            <div className="absolute top-7 right-7 bg-gallery-white text-gallery-ink px-4 py-3 text-[10px] tracking-[0.2em]">
              {counter} / {total}
            </div>
          )}
        </div>
      </div>

      {banners.length > 1 && (
        <div className={`absolute bottom-7 ${textFirst ? 'left-8 sm:left-14 lg:left-[7vw]' : 'right-8 sm:right-14 lg:right-[7vw]'} flex items-center gap-5 ${banner.panel_theme === 'ink' ? 'text-gallery-white' : 'text-gallery-ink'}`}>
          <button onClick={previousSlide} className="text-lg opacity-50 hover:opacity-100 transition-opacity" aria-label="Previous banner">←</button>
          <div className="flex gap-2">
            {banners.map((item, index) => (
              <button
                key={item.id}
                onClick={() => goToSlide(index)}
                className={`h-px transition-all duration-500 ${index === currentIndex ? 'w-12 bg-current' : 'w-5 bg-current opacity-25'}`}
                aria-label={`Go to banner ${index + 1}`}
              />
            ))}
          </div>
          <button onClick={nextSlide} className="text-lg opacity-50 hover:opacity-100 transition-opacity" aria-label="Next banner">→</button>
        </div>
      )}
    </section>
  );
}
