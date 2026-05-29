import { useCashFlowAnalyticsQuery } from '@/app/store/features/business/branches/branchesQuery';
import { useState } from 'react';

export const CashFlowAnalytics = () => {
  const [selectedPeriod, setSelectedPeriod] = useState('last_7_days');

  const { data, error } = useCashFlowAnalyticsQuery(selectedPeriod);
  // console.log('cash flow==>', data ?? error);
  return <div>CashFlowAnalytics</div>;
};
