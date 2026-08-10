import { test, expect } from '@playwright/test';

test.describe('Checkout Flow', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to local dev server (assuming 5173 for Vite)
    await page.goto('http://localhost:5173');
    
    // Clear localStorage to start fresh
    await page.evaluate(() => localStorage.clear());
  });

  test('Guest checkout flow end-to-end', async ({ page }) => {
    // 1. Add item to cart from home
    const acquireBtn = page.getByRole('button', { name: /acquire/i }).first();
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

    // 5. Auth gate - Choose Guest
    await expect(page.getByText(/How would you like to continue/i)).toBeVisible();
    await page.getByRole('button', { name: /guest/i }).click();

    // 6. Fill shipping form
    await page.getByRole('textbox', { name: /full name/i }).fill('Test Guest');
    await page.getByRole('textbox', { name: /email/i }).fill('guest@example.com');
    await page.getByRole('textbox', { name: /phone/i }).fill('081234567890');
    await page.getByRole('textbox', { name: /address/i }).fill('Jl. Test No. 123');
    await page.getByRole('textbox', { name: /city/i }).fill('Jakarta');
    await page.getByRole('textbox', { name: /postal code/i }).fill('12345');

    // 7. Submit form -> go to Confirm phase
    await page.getByRole('button', { name: /review order/i }).click();

    // 8. Confirm page
    await expect(page.getByText(/confirm your order/i)).toBeVisible();
    await expect(page.getByText(/Test Guest/i)).toBeVisible();
    await expect(page.getByText(/guest@example.com/i)).toBeVisible();

    // Note: We don't click "Pay Now" because it hits Xendit API without real backend running / mocking.
    // The presence of "Pay Now" button is sufficient to prove frontend flow works until submission.
    await expect(page.getByRole('button', { name: /pay now/i })).toBeVisible();
  });
});
