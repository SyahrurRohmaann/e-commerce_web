import axios from 'axios';
import { normalizeApiError } from './apiError';

const isProduction = import.meta.env.PROD;
const configuredBaseUrl = import.meta.env.VITE_API_BASE_URL;
const developmentDefault = 'http://localhost:8000/api';

function isLoopback(hostname) {
  const host = hostname.toLowerCase().replace(/^\[|\]$/g, '');
  return host === 'localhost' || host === '::1' || host === '0:0:0:0:0:0:0:1' ||
    host === '127.0.0.1' || /^127\./.test(host) || host === '0.0.0.0' ||
    host === '::' || host === '0:0:0:0:0:0:0:0' || /^::ffff:127\./.test(host);
}

function resolveBaseUrl() {
  const value = configuredBaseUrl || (!isProduction ? developmentDefault : '');
  if (!value) throw new Error('VITE_API_BASE_URL must be configured in production.');
  let url;
  try { url = new URL(value); } catch { throw new Error('VITE_API_BASE_URL is malformed.'); }
  if (isProduction && (url.protocol !== 'https:' || isLoopback(url.hostname))) {
    throw new Error('VITE_API_BASE_URL must be a non-loopback HTTPS URL in production.');
  }
  return url.href.replace(/\/$/, '');
}

const api = axios.create({
  baseURL: resolveBaseUrl(),
  timeout: 15000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers['Authorization'] = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject({ ...error, userMessage: normalizeApiError(error).message });
  }
);

api.interceptors.response.use(
  response => response,
  error => {
    if (error?.response?.status === 401 && localStorage.getItem('token')) {
      localStorage.removeItem('token');
      window.dispatchEvent(new CustomEvent('auth:expired'));
    }
    error.userMessage = normalizeApiError(error).message;
    return Promise.reject(error);
  }
);

export default api;