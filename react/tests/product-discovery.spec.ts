import { test, expect } from '@playwright/test';

const products = [
  {
    id: 1,
    name: 'Obsidian Coat',
    price: 2500000,
    stock: 4,
    image_url: 'https://example.com/obsidian.jpg',
    hover_image_url: 'https://example.com/missing-hover.jpg',
    category: { id: 10, name: 'Outerwear' },
  },
  {
    id: 2,
    name: 'Ivory Blouse',
    price: 900000,
    stock: 0,
    image_url: 'https://example.com/ivory.jpg',
    category: { id: 11, name: 'Shirting' },
  },
  {
    id: 3,
    name: 'Slate Jacket',
    price: 1800000,
    stock: 2,
    image_url: 'https://example.com/slate.jpg',
    category: { id: 10, name: 'Outerwear' },
  },
  {
    id: 4,
    name: 'Unsafe Image Piece',
    price: 'invalid',
    stock: 3,
    image_url: 'javascript:alert(1)',
    category: { id: 12, name: 'Accessories' },
  },
];

test.describe('Product discovery', () => {
  test.beforeEach(async ({ page }) => {
    await page.route('**/api/catalog', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: products }),
      });
    });
    await page.route('**/api/hero-banners', async (route) => {
      await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ data: [] }) });
    });
    await page.route('https://example.com/**', async (route) => {
      await route.fulfill({ status: 404, body: '' });
    });
    await page.goto('http://localhost:5173');
  });

  test('filters products by category and reports the result count', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Discover the collection' })).toBeVisible();
    await expect(page.getByText('4 pieces shown')).toBeVisible();

    await page.getByRole('button', { name: 'Outerwear' }).click();

    await expect(page.getByText('2 pieces shown')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Obsidian Coat' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Slate Jacket' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Ivory Blouse' })).toBeHidden();
  });

  test('sorts by price and keeps unavailable products disabled', async ({ page }) => {
    await page.getByLabel('Sort collection').selectOption('price-asc');

    const productNames = page.locator('[data-testid="product-card"] h3');
    await expect(productNames).toHaveText(['Ivory Blouse', 'Slate Jacket', 'Obsidian Coat', 'Unsafe Image Piece']);
    await expect(page.getByRole('button', { name: 'Ivory Blouse unavailable' })).toBeDisabled();
  });

  test('keeps the primary image visible when a hover image fails', async ({ page }) => {
    const primaryImage = page.getByRole('img', { name: 'Obsidian Coat' });

    await expect(primaryImage).not.toHaveClass(/group-hover:opacity-0/);
  });

  test('fails closed for unsafe product data and exposes specific cart labels', async ({ page }) => {
    await expect(page.getByRole('button', { name: 'Add Obsidian Coat to cart' })).toBeEnabled();
    await expect(page.getByRole('button', { name: 'Unsafe Image Piece unavailable' })).toBeDisabled();
    await expect(page.getByRole('img', { name: 'Unsafe Image Piece' })).toHaveAttribute('src', /images\.unsplash\.com/);
    await expect(page.getByText('Price unavailable')).toBeVisible();
  });

  test('keeps malformed prices last for descending sort', async ({ page }) => {
    await page.getByLabel('Sort collection').selectOption('price-desc');

    await expect(page.locator('[data-testid="product-card"] h3')).toHaveText([
      'Obsidian Coat',
      'Slate Jacket',
      'Ivory Blouse',
      'Unsafe Image Piece',
    ]);
  });

  test('treats blank prices as unavailable instead of free', async ({ page }) => {
    const blankPriceProducts = products.map((product) => (
      product.id === 4 ? { ...product, price: '   ' } : product
    ));
    await page.route('**/api/catalog', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: blankPriceProducts }),
      });
    });
    await page.reload();

    await expect(page.getByRole('button', { name: 'Unsafe Image Piece unavailable' })).toBeDisabled();
    await expect(page.getByText('Price unavailable')).toBeVisible();
  });
});

test.describe('Product discovery request states', () => {
  test.beforeEach(async ({ page }) => {
    await page.route('**/api/hero-banners', async (route) => {
      await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ data: [] }) });
    });
  });

  test('announces loading while the catalog request is pending', async ({ page }) => {
    let releaseCatalog = () => {};
    await page.route('**/api/catalog', async (route) => {
      await new Promise((resolve) => { releaseCatalog = resolve; });
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: products }),
      });
    });

    await page.goto('http://localhost:5173');
    await expect(page.getByRole('status')).toHaveText('Curating the collection…');
    releaseCatalog();
    await expect(page.getByRole('heading', { name: 'Discover the collection' })).toBeVisible();
  });

  test('shows a safe error and retry action when catalog loading fails', async ({ page }) => {
    await page.route('**/api/catalog', async (route) => {
      await route.fulfill({ status: 500, body: 'internal database details' });
    });

    await page.goto('http://localhost:5173');

    await expect(page.getByRole('heading', { name: 'The collection is temporarily unavailable' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Retry collection' })).toBeVisible();
    await expect(page.getByText('internal database details')).toBeHidden();
  });

  test('loads the collection when a user retries once', async ({ page }) => {
    let attempts = 0;
    await page.route('**/api/catalog', async (route) => {
      attempts += 1;
      if (attempts === 1) {
        await route.fulfill({ status: 500 });
        return;
      }
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: products }),
      });
    });

    await page.goto('http://localhost:5173');
    await page.getByRole('button', { name: 'Retry collection' }).click();

    await expect(page.getByText('4 pieces shown')).toBeVisible();
    expect(attempts).toBe(2);
  });

  test('distinguishes a successful empty catalog from a request failure', async ({ page }) => {
    await page.route('**/api/catalog', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: [] }),
      });
    });

    await page.goto('http://localhost:5173');

    await expect(page.getByRole('heading', { name: 'The next edit is on its way' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Retry collection' })).toBeVisible();
    await expect(page.getByText('temporarily unavailable')).toBeHidden();
  });
});
