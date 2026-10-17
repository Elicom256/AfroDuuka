import { format } from 'date-fns';

/**
 * Format a date for display without ever throwing.
 *
 * Records created locally while offline (pending sync) have no server timestamp
 * yet, and a missing or malformed `created_at` makes `format(new Date(value))`
 * throw a RangeError. A throw inside a table row unmounts the page, which is one
 * of the ways a list can render as blank. Returning a placeholder keeps the row
 * — and the page — alive.
 */
export function formatDate(value: string | number | Date | null | undefined, pattern = 'PPP'): string {
  if (value === null || value === undefined || value === '') return '-';
  const date = value instanceof Date ? value : new Date(value);
  if (Number.isNaN(date.getTime())) return '-';
  return format(date, pattern);
}
