/**
 * Format USD value with 2 decimal places
 * @param {number} value - The value to format
 * @returns {string} Formatted USD string (e.g., "9432.50")
 */
export const formatUsd = (value) => {
  const n = Number(value ?? 0);
  if (Number.isNaN(n)) return '0.00';
  return n.toFixed(2);
};

/**
 * Format cryptocurrency value with up to 8 decimals, trimmed
 * @param {number} value - The value to format
 * @returns {string} Formatted crypto string (e.g., "0.5", "0.5001", "0.00000001")
 */
export const formatCrypto = (value) => {
  const n = Number(value ?? 0);
  if (Number.isNaN(n)) return '0';
  let s = n.toFixed(8);        // "1000.00000000"
  s = s.replace(/\.?0+$/, ''); // "1000"
  return s;
};