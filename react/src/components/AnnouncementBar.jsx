import { useEffect, useState } from 'react';
import api from '../lib/axios';

/**
 * Announcement strip rendered above the sticky header.
 * - Fetches active bars from /api/announcements.
 * - Rotates through messages (marquee feel, luxury editorial style).
 * - Hides when the page is scrolled down; reappears at the top.
 * - Renders nothing when there are no active bars (non-blocking).
 */
export function AnnouncementBar() {
  const [bars, setBars] = useState([]);
  const [hidden, setHidden] = useState(false);

  useEffect(() => {
    let cancelled = false;
    api
      .get('/announcements')
      .then((res) => {
        if (!cancelled) setBars(Array.isArray(res.data?.data) ? res.data.data : []);
      })
      .catch(() => {
        if (!cancelled) setBars([]);
      });
    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    const threshold = 160;
    const onScroll = () => {
      setHidden(window.scrollY > threshold);
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  const activeBars = bars.filter((bar) => bar.is_active !== false);
  if (activeBars.length === 0) return null;

  const primary = activeBars[0];
  const background = primary.background_color || '#111111';
  const color = primary.text_color || '#FFFFFF';

  return (
    <div
      aria-label="Announcement"
      className="fixed top-0 left-0 right-0 z-50 overflow-hidden transition-transform duration-300 ease-in-out"
      style={{
        backgroundColor: background,
        color,
        transform: hidden ? 'translateY(-110%)' : 'translateY(0)',
      }}
    >
      <div className="max-w-7xl mx-auto px-6 py-2 text-center text-[10px] uppercase tracking-[0.24em] whitespace-nowrap overflow-hidden">
        {activeBars.length === 1 ? (
          <span>{primary.message}</span>
        ) : (
          <span className="inline-flex gap-16 animate-marquee">
            {activeBars.map((bar) => (
              <span key={bar.id}>{bar.message}</span>
            ))}
            {activeBars.map((bar) => (
              <span key={`${bar.id}-dup`} aria-hidden="true">
                {bar.message}
              </span>
            ))}
          </span>
        )}
      </div>
    </div>
  );
}