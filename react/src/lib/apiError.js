const GENERIC_MESSAGE = 'Something went wrong. Please try again.';

export function normalizeApiError(error) {
  if (error?.code === 'ECONNABORTED' || error?.code === 'ETIMEDOUT') {
    return { kind: 'timeout', message: 'The request timed out. Please try again.' };
  }
  if (!error?.response) {
    return { kind: 'network', message: 'Unable to reach the service. Please try again.' };
  }
  const status = error.response.status;
  if (status === 401) return { kind: 'auth', message: 'Your session has expired. Please sign in again.' };
  if (status === 404) return { kind: 'not-found', message: 'The requested information was not found.' };
  if (status >= 400 && status < 500) return { kind: 'validation', message: 'Please check the submitted information.' };
  return { kind: 'server', message: GENERIC_MESSAGE };
}

export function invoiceRedirectUrl(value, allowedOrigins = []) {
  if (typeof value !== 'string' || value.length === 0) return null;
  let url;
  try { url = new URL(value); } catch { return null; }
  if (url.protocol !== 'https:') return null;
  if (!allowedOrigins.length || !allowedOrigins.includes(url.origin)) return null;
  return url.href;
}

export function configuredInvoiceOrigins(value = '') {
  return Object.freeze(value.split(',').map(item => item.trim()).filter(item => {
    try { return new URL(item).protocol === 'https:' && new URL(item).pathname === '/' && !new URL(item).search && !new URL(item).hash; } catch { return false; }
  }).map(item => new URL(item).origin));
}