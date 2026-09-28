import { useGetTodosQuery } from '@/app/store/features/todos/todoQuery';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { CheckSquare, Circle, Clock } from 'lucide-react';

export const StaffTasks = () => {
  const { data, isLoading } = useGetTodosQuery();

  if (isLoading) {
    return (
      <Card>
        <CardHeader className='pb-3'>
          <Skeleton className='h-5 w-32' />
        </CardHeader>
        <CardContent className='space-y-3'>
          <Skeleton className='h-4 w-full' />
          <Skeleton className='h-4 w-3/4' />
        </CardContent>
      </Card>
    );
  }

  const todos = data?.data ?? [];
  const pending = todos.filter((t: any) => t.status === 'undone');
  const completed = todos.filter((t: any) => t.status === 'done');

  return (
    <Card>
      <CardHeader className='pb-3'>
        <CardTitle className='flex items-center gap-2 text-sm'>
          <CheckSquare className='h-4 w-4 text-blue-500' />
          My Tasks
          {pending.length > 0 && (
            <Badge variant='secondary' className='ml-auto text-xs'>{pending.length} pending</Badge>
          )}
        </CardTitle>
      </CardHeader>
      <CardContent className='space-y-1.5'>
        {todos.length === 0 && (
          <p className='text-xs text-muted-foreground'>No tasks assigned.</p>
        )}
        {pending.slice(0, 5).map((task: any) => (
          <div
            key={task.id}
            className='flex items-center gap-2.5 rounded-lg px-2 py-1.5 transition-colors hover:bg-muted/50'
          >
            <Circle className='h-3.5 w-3.5 shrink-0 text-muted-foreground' />
            <div className='min-w-0 flex-1'>
              <p className='truncate text-sm'>{task.title ?? task.description ?? 'Task'}</p>
              {task.due_date && (
                <p className='flex items-center gap-1 text-xs text-muted-foreground'>
                  <Clock className='h-3 w-3' />
                  Due {new Date(task.due_date).toLocaleDateString()}
                </p>
              )}
            </div>
          </div>
        ))}
        {completed.length > 0 && (
          <div className='mt-2 border-t pt-2'>
            <p className='text-xs font-medium text-muted-foreground'>
              {completed.length} completed
            </p>
          </div>
        )}
      </CardContent>
    </Card>
  );
};
