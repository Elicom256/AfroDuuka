export type LandingPeriod = 'today' | 'last_7_days' | 'last_30_days' | 'this_month' | 'last_month';

export const landingPeriods: { label: string; value: LandingPeriod }[] = [
  { label: 'Today', value: 'today' },
  { label: 'Last 7 days', value: 'last_7_days' },
  { label: 'Last 30 days', value: 'last_30_days' },
  { label: 'This month', value: 'this_month' },
  { label: 'Last month', value: 'last_month' },
];

export function landingPeriodLabel(period: LandingPeriod): string {
  return landingPeriods.find((p) => p.value === period)?.label ?? '';
}
