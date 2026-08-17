import { useMemo, useState } from 'react';

const fallbackImage = 'https://images.unsplash.com/photo-1584916201218-f4242ceb4809?auto=format&fit=crop&q=80&w=800';

function safeImageUrl(value) {
  if (!value) return fallbackImage;

  try {
    const url = new URL(value, window.location.origin);
    return url.protocol === 'https:' ? url.href : fallbackImage;
  } catch {
    return fallbackImage;
  }
}

function numericPrice(value) {
  if (typeof value !== 'number' && typeof value !== 'string') return null;
  if (typeof value === 'string' && value.trim() === '') return null;
  const price = Number(value);
  return Number.isFinite(price) && price >= 0 ? price : null;
}

function handlePrimaryImageError(event) {
  event.currentTarget.onerror = null;
  event.currentTarget.src = fallbackImage;
}

function ProductCard({ product, addItem, format }) {
  const stock = Number(product.stock);
  const price = numericPrice(product.price);
  const unavailable = !Number.isFinite(stock) || stock < 1 || price === null;
  const hoverImageUrl = safeImageUrl(product.hover_image_url);
  const [hoverImageAvailable, setHoverImageAvailable] = useState(
    Boolean(product.hover_image_url) && hoverImageUrl !== fallbackImage,
  );

  return (
    <article data-testid="product-card" className="group flex flex-col">
      <div className="aspect-[4/5] bg-gallery-stone mb-6 overflow-hidden relative">
        <img
          src={safeImageUrl(product.image_url)}
          alt={product.name}
          loading="lazy"
          className={`w-full h-full object-cover transition-all duration-700 group-hover:scale-[1.025] ${hoverImageAvailable ? 'group-hover:opacity-0 absolute inset-0 z-10' : ''}`}
          onError={handlePrimaryImageError}
        />
        {hoverImageAvailable && (
          <img
            src={hoverImageUrl}
            alt=""
            aria-hidden="true"
            loading="lazy"
            className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-[1.025] absolute inset-0 z-0"
            onError={() => setHoverImageAvailable(false)}
          />
        )}
        <button
          type="button"
          onClick={() => addItem(product)}
          disabled={unavailable}
          aria-label={unavailable ? `${product.name} unavailable` : `Add ${product.name} to cart`}
          className="absolute bottom-0 left-0 z-20 w-full bg-gallery-ink text-white py-4 text-xs tracking-[0.2em] uppercase translate-y-0 sm:translate-y-full sm:group-hover:translate-y-0 sm:group-focus-within:translate-y-0 transition-transform duration-300 disabled:bg-gallery-subtle disabled:cursor-not-allowed"
        >
          {unavailable ? 'Unavailable' : 'Add to Cart'}
        </button>
      </div>
      <div className="flex justify-between items-start gap-6">
        <div>
          <p className="mb-2 text-[10px] text-gallery-subtle uppercase tracking-[0.22em]">
            {product.category?.name || 'Uncategorized'}
          </p>
          <h3 className="text-xl leading-tight font-serif">{product.name}</h3>
        </div>
        <p className="text-sm font-medium whitespace-nowrap">
          {price === null ? 'Price unavailable' : format(price)}
        </p>
      </div>
    </article>
  );
}

export function ProductDiscovery({ products, addItem, format }) {
  const [category, setCategory] = useState('all');
  const [sort, setSort] = useState('featured');

  const categories = useMemo(() => (
    [...new Set(products.map((product) => product.category?.name).filter(Boolean))]
      .sort((a, b) => a.localeCompare(b))
  ), [products]);

  const visibleProducts = useMemo(() => {
    const filtered = category === 'all'
      ? products
      : products.filter((product) => product.category?.name === category);
    const result = [...filtered];

    if (sort === 'price-asc' || sort === 'price-desc') {
      result.sort((a, b) => {
        const aPrice = numericPrice(a.price);
        const bPrice = numericPrice(b.price);
        if (aPrice === null) return bPrice === null ? 0 : 1;
        if (bPrice === null) return -1;
        return sort === 'price-asc' ? aPrice - bPrice : bPrice - aPrice;
      });
    }
    if (sort === 'name') result.sort((a, b) => a.name.localeCompare(b.name));

    return result;
  }, [category, products, sort]);

  return (
    <section id="collection" aria-labelledby="collection-heading" className="max-w-7xl mx-auto px-6 py-24 sm:py-32">
      <div className="grid gap-8 lg:grid-cols-[1fr_auto] lg:items-end border-b border-gallery-stone pb-8 mb-10">
        <div className="max-w-2xl">
          <p className="mb-4 text-[10px] font-semibold uppercase tracking-[0.3em] text-gallery-subtle">The seasonal edit</p>
          <h2 id="collection-heading" className="text-4xl sm:text-5xl font-serif tracking-[-0.025em]">Discover the collection</h2>
          <p className="mt-5 max-w-xl text-sm leading-7 text-gallery-subtle">
            Considered essentials selected for their form, material, and ability to move beyond a single season.
          </p>
        </div>
        <label className="flex items-center gap-4 text-[10px] font-semibold uppercase tracking-[0.2em] text-gallery-subtle">
          <span>Sort collection</span>
          <select
            value={sort}
            onChange={(event) => setSort(event.target.value)}
            className="min-w-40 bg-transparent border-b border-gallery-ink py-2 text-xs tracking-normal normal-case text-gallery-ink focus:outline-none focus:ring-2 focus:ring-gallery-ink focus:ring-offset-4"
          >
            <option value="featured">Featured</option>
            <option value="price-asc">Price: low to high</option>
            <option value="price-desc">Price: high to low</option>
            <option value="name">Name</option>
          </select>
        </label>
      </div>

      <div className="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between mb-14">
        <div className="flex gap-2 overflow-x-auto pb-2" aria-label="Filter collection by category">
          {['all', ...categories].map((item) => {
            const selected = category === item;
            const label = item === 'all' ? 'All pieces' : item;
            return (
              <button
                type="button"
                key={item}
                onClick={() => setCategory(item)}
                aria-pressed={selected}
                className={`shrink-0 border px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.18em] transition-colors ${selected ? 'border-gallery-ink bg-gallery-ink text-gallery-white' : 'border-gallery-stone hover:border-gallery-ink'}`}
              >
                {label}
              </button>
            );
          })}
        </div>
        <p aria-live="polite" className="text-[10px] uppercase tracking-[0.2em] text-gallery-subtle">
          {visibleProducts.length} {visibleProducts.length === 1 ? 'piece' : 'pieces'} shown
        </p>
      </div>

      {products.length === 0 ? (
        <div className="py-24 text-center text-gallery-subtle uppercase tracking-widest text-sm">Curating collection...</div>
      ) : visibleProducts.length === 0 ? (
        <div className="py-24 text-center text-gallery-subtle uppercase tracking-widest text-sm">No pieces in this edit.</div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 lg:gap-x-12 gap-y-20 lg:gap-y-24">
          {visibleProducts.map((product) => (
            <ProductCard key={product.id} product={product} addItem={addItem} format={format} />
          ))}
        </div>
      )}
    </section>
  );
}
