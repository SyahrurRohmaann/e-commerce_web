import { create } from 'zustand';
import { CURRENCIES, fetchExchangeRates, detectUserCurrency, formatCurrency } from '../lib/currency';

export const useCurrencyStore = create((set, get) => ({
  currentCurrency: localStorage.getItem('user_currency') || 'IDR',
  rates: {},
  loading: true,

  initCurrency: async () => {
    const rates = await fetchExchangeRates();
    const currency = await detectUserCurrency();
    set({ rates, currentCurrency: currency, loading: false });
  },

  setCurrency: (code) => {
    if (CURRENCIES[code]) {
      localStorage.setItem('user_currency', code);
      set({ currentCurrency: code });
    }
  },

  format: (amountInIDR) => {
    const { currentCurrency, rates } = get();
    return formatCurrency(amountInIDR, currentCurrency, rates);
  },
}));
