/**
 * Lightweight polyfills and defensive shims so that the app can boot even on
 * constrained browsers such as Opera Mini. The goal is to gracefully fall back
 * instead of crashing when certain Web APIs are unavailable.
 */

const hasWindow = typeof window !== "undefined";

const createMemoryStorage = () => {
  let store = {};

  return {
    getItem(key) {
      return Object.prototype.hasOwnProperty.call(store, key)
        ? store[key]
        : null;
    },
    setItem(key, value) {
      store[key] = value?.toString?.() ?? String(value);
    },
    removeItem(key) {
      delete store[key];
    },
    clear() {
      store = {};
    },
    key(index) {
      const keys = Object.keys(store);
      return keys[index] ?? null;
    },
    get length() {
      return Object.keys(store).length;
    },
  };
};

const ensureStorage = (name) => {
  if (!hasWindow) {
    return createMemoryStorage();
  }

  try {
    const storage = window[name];
    if (storage) {
      const testKey = `__storage_test__${name}`;
      storage.setItem(testKey, testKey);
      storage.removeItem(testKey);
      return storage;
    }
  } catch (error) {
    console.warn(`${name} is not available, falling back to memory storage.`, error);
  }

  const fallback = createMemoryStorage();
  try {
    Object.defineProperty(window, name, {
      value: fallback,
      configurable: true,
    });
  } catch (error) {
    window[name] = fallback;
  }
  return fallback;
};

// Bootstrapping storages early keeps the rest of the codebase unchanged.
const local = ensureStorage("localStorage");
const session = ensureStorage("sessionStorage");

export const safeLocalStorage = local;
export const safeSessionStorage = session;

if (hasWindow && typeof window.matchMedia !== "function") {
  window.matchMedia = (query) => ({
    media: query,
    matches: false,
    onchange: null,
    addListener: () => {},
    removeListener: () => {},
    addEventListener: () => {},
    removeEventListener: () => {},
    dispatchEvent: () => false,
  });
}

export const isHistorySupported = (() => {
  if (!hasWindow || !window.history) {
    return false;
  }
  try {
    return typeof window.history.pushState === "function";
  } catch {
    return false;
  }
})();
