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
    await expect(page.getByText('3 pieces shown')).toBeVisible();

    await page.getByRole('button', { name: 'Outerwear' }).click();

    await expect(page.getByText('2 pieces shown')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Obsidian Coat' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Slate Jacket' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Ivory Blouse' })).toBeHidden();
  });

  test('sorts by price and keeps unavailable products disabled', async ({ page }) => {
    await page.getByLabel('Sort collection').selectOption('price-asc');

    const productNames = page.locator('[data-testid="product-card"] h3');
    await expect(productNames).toHaveText(['Ivory Blouse', 'Slate Jacket', 'Obsidian Coat']);
    await expect(page.getByRole('button', { name: 'Unavailable' })).toBeDisabled();
  });

  test('keeps the primary image visible when a hover image fails', async ({ page }) => {
    const primaryImage = page.getByRole('img', { name: 'Obsidian Coat' });

    await expect(primaryImage).not.toHaveClass(/group-hover:opacity-0/);
  });
});
