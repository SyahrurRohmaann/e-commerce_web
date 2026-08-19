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
  const [subcategory, setSubcategory] = useState('all');
  const [sort, setSort] = useState('featured');

  // Build a two-level taxonomy from products: top-level categories and their subcategories.
  const taxonomy = useMemo(() => {
    const byParent = {};
    products.forEach((product) => {
      const cat = product.category;
      if (!cat) return;
      const parentName = cat.parent?.name || cat.name;
      const isChild = Boolean(cat.parent_id);
      if (!byParent[parentName]) byParent[parentName] = { children: new Set(), hasSubcategories: false };
      if (isChild) {
        byParent[parentName].children.add(cat.name);
        byParent[parentName].hasSubcategories = true;
      }
    });
    return Object.entries(byParent)
      .map(([name, info]) => ({ name, children: [...info.children].sort((a, b) => a.localeCompare(b)), hasSubcategories: info.hasSubcategories }))
      .sort((a, b) => a.name.localeCompare(b.name));
  }, [products]);

  const activeTaxon = taxonomy.find((item) => item.name === category);
  const subcategories = activeTaxon?.hasSubcategories ? activeTaxon.children : [];

  const visibleProducts = useMemo(() => {
    let filtered = products;

    if (category !== 'all') {
      filtered = filtered.filter((product) => {
        const cat = product.category;
        if (!cat) return false;
        const parentName = cat.parent?.name || cat.name;
        return parentName === category;
      });
    }

    if (subcategory !== 'all') {
      filtered = filtered.filter((product) => product.category?.name === subcategory);
    }

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
  }, [category, subcategory, products, sort]);

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
        <div>
          <div className="flex gap-2 overflow-x-auto pb-2" aria-label="Filter collection by category">
            <button
              type="button"
              onClick={() => { setCategory('all'); setSubcategory('all'); }}
              aria-pressed={category === 'all'}
              className={`shrink-0 border px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.18em] transition-colors ${category === 'all' ? 'border-gallery-ink bg-gallery-ink text-gallery-white' : 'border-gallery-stone hover:border-gallery-ink'}`}
            >
              All pieces
            </button>
            {taxonomy.map((item) => {
              const selected = category === item.name;
              return (
                <button
                  type="button"
                  key={item.name}
                  onClick={() => {
                    setCategory(item.name);
                    setSubcategory('all');
                  }}
                  aria-pressed={selected}
                  className={`shrink-0 border px-5 py-2.5 text-[10px] font-semibold uppercase tracking-[0.18em] transition-colors ${selected ? 'border-gallery-ink bg-gallery-ink text-gallery-white' : 'border-gallery-stone hover:border-gallery-ink'}`}
                >
                  {item.name}
                </button>
              );
            })}
          </div>

          {subcategories.length > 0 && (
            <div className="flex gap-2 overflow-x-auto mt-3 pb-1" aria-label="Filter by subcategory">
              <button
                type="button"
                onClick={() => setSubcategory('all')}
                aria-pressed={subcategory === 'all'}
                className={`shrink-0 border-t-0 border-x-0 border-b px-3 py-1.5 text-[10px] font-medium uppercase tracking-[0.18em] transition-colors ${subcategory === 'all' ? 'border-gallery-ink text-gallery-ink' : 'border-transparent text-gallery-subtle hover:text-gallery-ink'}`}
              >
                All {category}
              </button>
              {subcategories.map((child) => (
                <button
                  type="button"
                  key={child}
                  onClick={() => setSubcategory(child)}
                  aria-pressed={subcategory === child}
                  className={`shrink-0 border-t-0 border-x-0 border-b px-3 py-1.5 text-[10px] font-medium uppercase tracking-[0.18em] transition-colors ${subcategory === child ? 'border-gallery-ink text-gallery-ink' : 'border-transparent text-gallery-subtle hover:text-gallery-ink'}`}
                >
                  {child}
                </button>
              ))}
            </div>
          )}
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
