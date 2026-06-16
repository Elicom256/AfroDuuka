// Shared types for the entire DuukaFlow app

export type { ReportFilter, ReportParams } from './periodFilter';
export { PERIODS, formatPeriodLabel, getDefaultPeriod } from './periodFilter';

// Generic API response wrapper
export interface ApiResponse<T> {
  message: string;
  data: T;
}

export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}

// Paginated response (when needed)
export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

// Currency (matches backend default)
export type Currency = 'UGX' | 'USD' | 'KES' | 'TZS' | 'EUR' | 'GBP';

// Generic settings toggle
export interface ToggleSetting {
  id: number | string;
  status: 'enabled' | 'disabled';
}
