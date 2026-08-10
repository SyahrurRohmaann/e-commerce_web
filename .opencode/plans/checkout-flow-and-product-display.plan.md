# Checkout Flow and Product Display Enhancement Plan

## Context
This plan addresses the implementation of the checkout-flow-and-product-display.prd.md.
The project is a fullstack application with a React/Vite frontend (in `react/`) and a Laravel 12 backend (in `laravel/`).

## Task Type
**Fullstack** (Requires both React frontend changes and Laravel backend logic/API changes).

## Session Tracking
- `SESSION_ID`: `checkout_enhancement_001`
- `CODEX_SESSION`: `checkout_backend_001`
- `GEMINI_SESSION`: `checkout_frontend_001`

## Key Files
| File Path | Description |
|-----------|-------------|
| `react/src/pages/Checkout.tsx` | New/updated checkout page for guest and authenticated users |
| `react/src/store/useCartStore.ts` | Zustand store for managing cart state and guest tracking |
| `react/src/pages/OrderTracking.tsx` | New/updated page for tracking orders via local storage |
| `react/src/components/ProductCard.tsx` | UI component for displaying dual-image (display/hover) |
| `react/src/pages/admin/ProductForm.tsx` | Admin form for uploading primary and hover images |
| `laravel/app/Http/Controllers/CheckoutController.php` | Backend logic for processing checkout and Xendit integration |
| `laravel/app/Http/Controllers/OrderController.php` | Order status management and retrieval |
| `laravel/app/Http/Controllers/Admin/ProductController.php` | Admin product management (handling hover image upload) |
| `laravel/database/migrations/*_add_hover_image_to_products_table.php` | Migration for adding hover image to products |
| `laravel/database/migrations/*_add_guest_id_to_orders_table.php` | Migration for associating guest orders |
| `laravel/routes/api.php` | API routes for checkout, orders, and products |

## Implementation Steps

### Backend (Laravel - Codex)
1.  **Database Migrations**:
    *   Add `hover_image` column to the `products` table.
    *   Add `guest_id` or `tracking_token` to the `orders` table to allow tracking without a user ID.
2.  **Product Management**:
    *   Update `ProductController` (Admin) to handle uploading and saving the `hover_image`.
    *   Update `ProductResource` to expose `hover_image` to the frontend.
3.  **Checkout & Payment (Xendit)**:
    *   Implement `CheckoutController@process` to handle both guest and authenticated users.
    *   Integrate `xendit/xendit-php` to generate payment links/invoices.
    *   Implement Xendit webhook handler to update order status (e.g., pending -> paid).
4.  **Order Tracking & Admin Management**:
    *   Create endpoint to fetch order status by ID + `tracking_token` for guests.
    *   Create admin endpoints to update order status (e.g., shipping -> arrive).

### Frontend (React - Gemini)
1.  **Product Display**:
    *   Update `ProductCard.tsx` to accept and render a `hoverImage` prop, switching images on mouse enter/leave.
2.  **Admin Product Form**:
    *   Add file input for "Hover Image" in the admin product creation/edit form (`ProductForm.tsx`).
3.  **Checkout Flow**:
    *   Update `Checkout.tsx` to offer "Checkout as Guest" or "Login".
    *   If logged in, auto-fill the form using user profile data.
    *   Submit checkout data to the new backend API and redirect to Xendit payment URL.
4.  **Guest Order Tracking**:
    *   Update `useCartStore` (or a dedicated tracking store) to save order IDs/tokens to `LocalStorage` upon successful checkout.
    *   Create `OrderTracking.tsx` to read `LocalStorage`, fetch status from the backend, and display the progress (pending -> paid -> shipping -> arrive).
