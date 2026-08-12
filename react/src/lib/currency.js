// Currency definitions with symbols and flags
export const CURRENCIES = {
  IDR: { code: 'IDR', symbol: 'Rp', name: 'Indonesian Rupiah', flag: '🇮🇩', locale: 'id-ID', decimals: 0 },
  USD: { code: 'USD', symbol: '$', name: 'US Dollar', flag: '🇺🇸', locale: 'en-US', decimals: 2 },
  SGD: { code: 'SGD', symbol: 'S$', name: 'Singapore Dollar', flag: '🇸🇬', locale: 'en-SG', decimals: 2 },
  EUR: { code: 'EUR', symbol: '€', name: 'Euro', flag: '🇪🇺', locale: 'de-DE', decimals: 2 },
  JPY: { code: 'JPY', symbol: '¥', name: 'Japanese Yen', flag: '🇯🇵', locale: 'ja-JP', decimals: 0 },
  MYR: { code: 'MYR', symbol: 'RM', name: 'Malaysian Ringgit', flag: '🇲🇾', locale: 'ms-MY', decimals: 2 },
  AUD: { code: 'AUD', symbol: 'A$', name: 'Australian Dollar', flag: '🇦🇺', locale: 'en-AU', decimals: 2 },
};

// Fallback rates if API fetch fails (Base: IDR)
const DEFAULT_RATES = {
  IDR: 1,
  USD: 0.000063,
  SGD: 0.000085,
  EUR: 0.000058,
  JPY: 0.0097,
  MYR: 0.00028,
  AUD: 0.000096,
};

const RATES_CACHE_KEY = 'currency_exchange_rates';
const RATES_CACHE_TIME_KEY = 'currency_exchange_rates_time';
const CACHE_DURATION_MS = 60 * 60 * 1000; // 1 hour

/**
 * Fetch live exchange rates against IDR base
 */
export async function fetchExchangeRates() {
  try {
    const cachedRates = localStorage.getItem(RATES_CACHE_KEY);
    const cachedTime = localStorage.getItem(RATES_CACHE_TIME_KEY);

    if (cachedRates && cachedTime && (Date.now() - Number(cachedTime) < CACHE_DURATION_MS)) {
      return JSON.parse(cachedRates);
    }

    const response = await fetch('https://open.er-api.com/v6/latest/IDR');
    if (!response.ok) throw new Error('Failed to fetch rates');
    
    const data = await response.json();
    if (data && data.rates) {
      localStorage.setItem(RATES_CACHE_KEY, JSON.stringify(data.rates));
      localStorage.setItem(RATES_CACHE_TIME_KEY, String(Date.now()));
      return data.rates;
    }
    return DEFAULT_RATES;
  } catch (error) {
    console.warn('Using default exchange rates due to fetch error:', error);
    return DEFAULT_RATES;
  }
}

/**
 * Auto-detect user currency based on IP Geolocation
 */
export async function detectUserCurrency() {
  try {
    const savedCurrency = localStorage.getItem('user_currency');
    if (savedCurrency && CURRENCIES[savedCurrency]) {
      return savedCurrency;
    }

    const response = await fetch('https://ipapi.co/json/');
    if (response.ok) {
      const data = await response.json();
      const detectedCurrency = data.currency;
      if (detectedCurrency && CURRENCIES[detectedCurrency]) {
        localStorage.setItem('user_currency', detectedCurrency);
        return detectedCurrency;
      }
    }
  } catch (error) {
    console.warn('IP currency detection failed:', error);
  }
  return 'IDR';
}

/**
 * Convert price from IDR to target currency and format
 * @param {number} amountInIDR - Amount in IDR base
 * @param {string} targetCurrencyCode - e.g. 'USD', 'IDR'
 * @param {object} rates - Exchange rates mapping
 * @returns {string} Formatted string e.g. "$ 12.35" or "Rp 150.000"
 */
export function formatCurrency(amountInIDR, targetCurrencyCode = 'IDR', rates = DEFAULT_RATES) {
  const num = Number(amountInIDR) || 0;
  const currencyInfo = CURRENCIES[targetCurrencyCode] || CURRENCIES.IDR;
  const rate = rates[targetCurrencyCode] || DEFAULT_RATES[targetCurrencyCode] || 1;
  
  const convertedAmount = num * rate;

  if (currencyInfo.decimals === 0) {
    const rounded = Math.round(convertedAmount);
    return `${currencyInfo.symbol} ${rounded.toLocaleString(currencyInfo.locale)}`;
  }

  // Standard 2-decimal formatting with round up / standard rounding
  const formattedNumber = convertedAmount.toLocaleString(currencyInfo.locale, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });

  return `${currencyInfo.symbol} ${formattedNumber}`;
}
