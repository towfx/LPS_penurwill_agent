import { clsx } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs) {
  return twMerge(clsx(inputs));
}

export function valueUpdater(updaterOrValue, ref) {
  ref.value =
    typeof updaterOrValue === "function"
      ? updaterOrValue(ref.value)
      : updaterOrValue;
}

/**
 * Format currency with Malaysian Ringgit (RM) prefix
 * @param {string} prefix - Currency prefix (always 'RM')
 * @param {number|string} value - Amount to format
 * @returns {string} Formatted currency string (e.g., "RM 1,234.56")
 */
export function formatCurrency(prefix, value) {
  const numValue = parseFloat(value || 0);

  // Format with commas for thousands and 2 decimal places
  const formattedValue = numValue.toLocaleString('en-MY', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });

  return `${prefix} ${formattedValue}`;
}
