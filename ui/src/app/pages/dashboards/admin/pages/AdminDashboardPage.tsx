import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { AiChat } from '@/components/ai/AiChat';
import { OverviewCards } from '@/app/pages/dashboards/admin/components/overview/OverviewCards';

export const AdminDashboardPage = () => {
  return (
    <div className='grid gap-6 xl:grid-cols-[1fr_380px]'>
      {/* <div className='space-y-6 h-[calc(100vh-8rem)]'> */}
        <Card className='overflow-hidden'>
          <CardHeader>
            <CardTitle>Overview</CardTitle>
            <CardDescription>Key metrics across your business.</CardDescription>
          </CardHeader>
          <CardContent>
            <OverviewCards />
          </CardContent>
        </Card>
      {/* </div> */}

      <div className='h-[calc(100vh-8rem)] sticky top-6'>
        <AiChat />
      </div>
    </div>
  );
};
