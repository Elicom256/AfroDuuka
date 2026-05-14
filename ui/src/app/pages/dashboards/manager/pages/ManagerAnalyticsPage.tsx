import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

export const ManagerAnalyticsPage = () => {
  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-2xl font-bold'>Analytics</h1>
        <p className='text-muted-foreground'>View performance metrics for your branch</p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Performance Overview</CardTitle>
          <CardDescription>Key metrics and trends</CardDescription>
        </CardHeader>
        <CardContent>
          <p>Charts and analytics components will be implemented here.</p>
        </CardContent>
      </Card>
    </div>
  );
};
