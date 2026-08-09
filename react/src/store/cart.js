import { create } from 'zustand';

export const useCartStore = create((set) => ({
  items: [],
  addItem: (product) => set((state) => {
    const existing = state.items.find(i => i.product_id === product.id);
    if (existing) {
      return {
        items: state.items.map(i => 
          i.product_id === product.id ? { ...i, quantity: i.quantity + 1 } : i
        )
      };
    }
    return { items: [...state.items, { product_id: product.id, quantity: 1, name: product.name, price: product.price }] };
  }),
  removeItem: (id) => set((state) => ({
    items: state.items.filter(i => i.product_id !== id)
  })),
  clearCart: () => set({ items: [] })
}));