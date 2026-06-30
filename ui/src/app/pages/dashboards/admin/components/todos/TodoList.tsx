import { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { CheckSquare, Plus, Trash2 } from 'lucide-react';
import { cn } from '@/lib/utils';

interface Todo {
  id: string;
  text: string;
  done: boolean;
}

const STORAGE_KEY = 'duukaflow_todos';

const loadTodos = (): Todo[] => {
  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    return stored ? JSON.parse(stored) : [];
  } catch {
    return [];
  }
};

export const TodoList = () => {
  const [todos, setTodos] = useState<Todo[]>(loadTodos);
  const [input, setInput] = useState('');

  useEffect(() => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(todos));
  }, [todos]);

  const addTodo = () => {
    const text = input.trim();
    if (!text) return;
    setTodos((prev) => [...prev, { id: crypto.randomUUID(), text, done: false }]);
    setInput('');
  };

  const toggleTodo = (id: string) => {
    setTodos((prev) => prev.map((t) => (t.id === id ? { ...t, done: !t.done } : t)));
  };

  const deleteTodo = (id: string) => {
    setTodos((prev) => prev.filter((t) => t.id !== id));
  };

  const doneCount = todos.filter((t) => t.done).length;

  return (
    <Card>
      <CardHeader className='pb-3'>
        <CardTitle className='flex items-center gap-2 text-sm'>
          <CheckSquare className='h-4 w-4 text-primary' />
          To-Do List
          {todos.length > 0 && (
            <span className='ml-auto text-xs text-muted-foreground'>
              {doneCount}/{todos.length}
            </span>
          )}
        </CardTitle>
      </CardHeader>
      <CardContent className='space-y-2'>
        <div className='flex gap-2'>
          <Input
            value={input}
            onChange={(e) => setInput(e.target.value)}
            onKeyDown={(e) => e.key === 'Enter' && addTodo()}
            placeholder='Add a task...'
            className='h-8 text-xs'
          />
          <Button size='icon' variant='outline' className='h-8 w-8 shrink-0' onClick={addTodo} disabled={!input.trim()}>
            <Plus className='h-3.5 w-3.5' />
          </Button>
        </div>

        <div className='max-h-40 space-y-1 overflow-y-auto'>
          {todos.length === 0 && (
            <p className='py-2 text-center text-xs text-muted-foreground'>No tasks yet</p>
          )}
          {todos.map((todo) => (
            <div key={todo.id} className='flex items-center gap-2 rounded-md px-1 py-1 hover:bg-muted/50 group'>
              <Checkbox
                checked={todo.done}
                onCheckedChange={() => toggleTodo(todo.id)}
                className='h-4 w-4'
              />
              <span
                className={cn(
                  'flex-1 text-sm',
                  todo.done && 'text-muted-foreground line-through',
                )}
              >
                {todo.text}
              </span>
              <Button
                size='icon'
                variant='ghost'
                className='h-6 w-6 shrink-0 opacity-0 group-hover:opacity-100'
                onClick={() => deleteTodo(todo.id)}
              >
                <Trash2 className='h-3 w-3 text-muted-foreground' />
              </Button>
            </div>
          ))}
        </div>
      </CardContent>
    </Card>
  );
};
