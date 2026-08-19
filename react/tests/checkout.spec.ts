import { test, expect } from '@playwright/test';

test.describe('Checkout Flow', () => {
  test.beforeEach(async ({ page }) => {
    // Mock API catalog response so front-end renders without backend server
    await page.route('**/api/catalog', async route => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: [
            {
              id: 1,
              name: 'Signature Hoodie',
              description: 'Designed for everyday movement.',
              price: 150000,
              stock: 10,
              image_url: 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f'
            }
          ]
        })
      });
    });

    await page.goto('http://localhost:5173');
    await page.evaluate(() => localStorage.clear());
  });

  test('Guest checkout flow end-to-end', async ({ page }) => {
    // 1. Add item to cart from home
    const acquireBtn = page.getByRole('button', { name: /add signature hoodie to cart/i });
    await expect(acquireBtn).toBeVisible({ timeout: 10000 });
    await acquireBtn.click();

    // 2. Go to cart
    await page.getByRole('link', { name: /cart/i }).click();
    await expect(page).toHaveURL(/.*\/cart/);
    
    // 3. Ensure item is in cart
    await expect(page.getByText(/Subtotal/i)).toBeVisible();
    
    // 4. Click Proceed to Checkout
    await page.getByRole('button', { name: /proceed to checkout/i }).click();
    await expect(page).toHaveURL(/.*\/checkout/);

    // 5. Fill the guest shipping form using CSS selectors by input name
    await page.locator('input[name="customer_name"]').fill('Test Guest');
    await page.locator('input[name="guest_email"]').fill('guest@example.com');
    await page.locator('input[name="customer_phone"]').fill('081234567890');
    await page.locator('textarea[name="shipping_address"]').fill('Jl. Test No. 123');
    const stateSelect = page.locator('select').nth(1);
    await expect(stateSelect).toBeVisible();
    await stateSelect.selectOption({ index: 1 });
    const citySelect = page.locator('select').nth(2);
    await expect(citySelect).toBeVisible();
    await citySelect.selectOption({ index: 1 });
    await page.locator('input[name="shipping_sub_district"]').fill('Gambir');
    await page.locator('input[name="shipping_postal_code"]').fill('12345');

    // 6. Submit form -> go to Confirm phase
    await page.getByRole('button', { name: /review order/i }).click();

    // 7. Confirm page
    await expect(page.getByText(/confirm your order/i)).toBeVisible();
    await expect(page.getByText(/Test Guest/i)).toBeVisible();
    await expect(page.getByText(/guest@example.com/i)).toBeVisible();

    await expect(page.getByRole('button', { name: /pay now/i })).toBeVisible();
  });
});
