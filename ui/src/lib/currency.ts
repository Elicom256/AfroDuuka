export const formatCurrency = (amount: number | string, currency?: string): string => {
  const sym = currency || 'UGX';
  const num = typeof amount === 'string' ? parseFloat(amount) : amount;
  if (isNaN(num)) return `${sym} 0`;
  return `${sym} ${num.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 })}`;
};

export const formatCurrencyShort = (amount: number | string, currency?: string): string => {
  const sym = currency || 'UGX';
  const num = typeof amount === 'string' ? parseFloat(amount) : amount;
  if (isNaN(num)) return `${sym} 0`;

  if (num >= 1_000_000_000) {
    return `${sym} ${(num / 1_000_000_000).toFixed(1)}B`;
  }
  if (num >= 1_000_000) {
    return `${sym} ${(num / 1_000_000).toFixed(1)}M`;
  }
  if (num >= 1_000) {
    return `${sym} ${(num / 1_000).toFixed(1)}K`;
  }
  return `${sym} ${num.toLocaleString()}`;
};
