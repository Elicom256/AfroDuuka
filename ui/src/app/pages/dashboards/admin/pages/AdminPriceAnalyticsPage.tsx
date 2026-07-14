/*-----------------------------------------------------------------------------------
 * Page: AdminPriceAnalyticsPage
 * -------------------------------
 * Standalone page for price change analytics.
 * Renders the PriceAnalytics widget.
 *---------------------------------------------------------------------------------*/

import { PriceAnalytics } from '../components/analytics/PriceAnalytics';

export const AdminPriceAnalyticsPage = () => {
  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight'>Price Analytics</h1>
        <p className='text-muted-foreground'>Track how product prices have changed over time</p>
      </div>
      <PriceAnalytics />
    </div>
  );
};
