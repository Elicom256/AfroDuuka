// Centralised period filter types and helpers for the entire app

export type ReportFilter =
  | 'last_7_days'
  | 'last_30_days'
  | 'this_month'
  | 'last_month'
  | 'this_year'
  | 'last_year';

export interface ReportParams {
  filter?: ReportFilter;
}

export const PERIODS: { label: string; value: ReportFilter }[] = [
  { label: '7 Days', value: 'last_7_days' },
  { label: '30 Days', value: 'last_30_days' },
  { label: 'This Month', value: 'this_month' },
  { label: 'Last Month', value: 'last_month' },
  { label: 'This Year', value: 'this_year' },
  { label: 'Last Year', value: 'last_year' },
];

export function formatPeriodLabel(period: ReportFilter): string {
  return period.replace(/_/g, ' ');
}

export function getDefaultPeriod(): ReportFilter {
  return 'last_7_days';
}
