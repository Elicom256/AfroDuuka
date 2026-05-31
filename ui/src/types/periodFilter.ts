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