import { useBranchProductMetricsQuery } from '@/app/store/features/branch/products/branchProductsQuery';
import { useState } from 'react';

export const PerformanceMetrics = () => {
  const [selectedPeriod, setSelectedPeriod] = useState('last_7_days');

  const { data, error } = useBranchProductMetricsQuery({ id: '8', period: selectedPeriod }, { pollingInterval: 5000 });
  const analytics = data?.data;
  // console.log('performance metrics==>', analytics ?? error);
  return <div>PerformanceMetrics</div>;
};
