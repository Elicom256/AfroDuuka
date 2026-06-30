import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { AiChat } from '@/components/ai/AiChat';
import { useCurrency } from '@/app/hooks/useCurrency';
import { OverviewCards } from '@/app/pages/dashboards/admin/components/overview/OverviewCards';
import { SmartRestocking } from '@/app/pages/dashboards/admin/components/restocking/SmartRestocking';
import { TodoList } from '@/app/pages/dashboards/admin/components/todos/TodoList';

export const AdminDashboardPage = () => {
  const { flagEmoji } = useCurrency();
  return (
    <div className='grid gap-6 xl:grid-cols-[1fr_380px]'>
      <Card className='overflow-hidden'>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            Overview {flagEmoji && <span className='text-xl'>{flagEmoji}</span>}
          </CardTitle>
          <CardDescription>Key metrics across your business.</CardDescription>
        </CardHeader>
        <CardContent>
          <OverviewCards />
        </CardContent>
      </Card>

      <div className='flex flex-col gap-4'>
        <div className='h-[calc(100vh-20rem)] sticky top-6'>
          <AiChat />
        </div>
        <div className='grid grid-cols-2 gap-4'>
          <SmartRestocking />
          <TodoList />
        </div>
      </div>
    </div>
  );
};
