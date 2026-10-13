export const FEATURE_LABELS = {
  reports: 'Reports',
  customers: 'Customers',
  suppliers: 'Suppliers',
  promotions: 'Promotions',
  attendance: 'Attendance',
} as const;

export type FeatureKey = keyof typeof FEATURE_LABELS;
