// components/todos/TodoList.tsx
import { useGetTodosQuery, useUpdateTodoMutation, useDeleteTodoMutation } from '@/app/store/features/todos/todoQuery';
import { TodoForm } from './TodoForm';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Button } from '@/components/ui/button';
import { Trash2, CheckSquare } from 'lucide-react';
import { cn } from '@/lib/utils';

export const TodoList = () => {
  const { data: todosData, isLoading } = useGetTodosQuery();
  const [updateTodo] = useUpdateTodoMutation();
  const [deleteTodo] = useDeleteTodoMutation();

  const todos = todosData?.data ?? [];
  const doneCount = todos.filter((t: any) => t.status === 'completed').length;
  const pendingCount = todos.length - doneCount;

  const toggleTodo = async (todo: any) => {
    await updateTodo({
      id: todo.id,
      body: { status: todo.status === 'completed' ? 'undone' : 'completed' },
    }).unwrap();
  };

  const handleDelete = async (id: number) => {
    await deleteTodo(id).unwrap();
  };

  return (
    <div className='grid gap-6 llg:grid-cols-12'>
      <div className='lg:col-span-4'>
        <Card className='shadow-sm border'>
          <CardHeader>
            <CardTitle className='text-lg'>Quick task entry</CardTitle>
            <CardDescription>Add a new todo and keep your day organized.</CardDescription>
          </CardHeader>
          <CardContent className='pt-4'>
            <TodoForm />
          </CardContent>
        </Card>
      </div>

      <div className='lg:col-span-8 space-y-6'>
        <Card className='shadow-sm border'>
          <CardHeader className='flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between'>
            <div>
              <CardTitle className='flex items-center gap-2 text-lg'>
                <CheckSquare className='h-5 w-5' />
                My Tasks
              </CardTitle>
              <CardDescription>Review your active tasks and keep progress moving.</CardDescription>
            </div>
            <div className='flex flex-wrap gap-2'>
              <Badge variant='secondary'>{pendingCount} pending</Badge>
              <Badge>{doneCount} completed</Badge>
              <Badge variant='outline'>{todos.length} total</Badge>
            </div>
          </CardHeader>

          <CardContent className='space-y-3 max-h-160 overflow-y-auto'>
            {isLoading ? (
              <div className='rounded-3xl border border-dashed border-muted/40 bg-muted/50 p-8 text-center text-sm text-muted-foreground'>
                Loading tasks...
              </div>
            ) : todos.length === 0 ? (
              <div className='rounded-3xl border border-dashed border-muted/40 bg-muted/50 p-8 text-center'>
                <p className='text-sm font-medium'>No tasks available yet.</p>
                <p className='mt-2 text-sm text-muted-foreground'>Use the form to add your first task.</p>
              </div>
            ) : (
              todos.map((todo: any) => (
                <div
                  key={todo.id}
                  className='group flex items-start gap-4 rounded-3xl border border-border bg-card/90 p-4 transition hover:-translate-y-0.5 hover:shadow-lg'
                >
                  <Checkbox
                    checked={todo.status === 'completed'}
                    onCheckedChange={() => toggleTodo(todo)}
                    className='mt-1'
                  />

                  <div className='min-w-0 flex-1'>
                    <div className='flex flex-wrap items-center gap-2'>
                      <p
                        className={cn(
                          'min-w-0 text-sm font-semibold leading-tight',
                          todo.status === 'completed' && 'line-through text-muted-foreground',
                        )}
                      >
                        {todo.title}
                      </p>
                      <Badge variant={todo.status === 'completed' ? 'secondary' : 'outline'}>
                        {todo.status === 'completed' ? 'Completed' : 'Pending'}
                      </Badge>
                    </div>

                    {todo.description && (
                      <p className='mt-2 text-sm text-muted-foreground line-clamp-2'>{todo.description}</p>
                    )}

                    {todo.date && (
                      <div className='mt-3 flex flex-wrap items-center gap-2 text-xs text-muted-foreground'>
                        <span className='rounded-full bg-muted/70 px-2 py-1'>
                          Due {new Date(todo.date).toLocaleDateString()}
                        </span>
                      </div>
                    )}
                  </div>

                  <Button
                    variant='ghost'
                    size='icon'
                    className='opacity-0 transition-opacity duration-200 group-hover:opacity-100'
                    onClick={() => handleDelete(todo.id)}
                  >
                    <Trash2 className='h-4 w-4 text-destructive' />
                  </Button>
                </div>
              ))
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
};
