// components/todos/TodoForm.tsx
import { useState, type FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { CalendarIcon, Plus } from 'lucide-react';
import { format } from 'date-fns';
import { cn } from '@/lib/utils';
import { useCreateTodoMutation } from '@/app/store/features/todos/todoQuery';
import { toast } from 'sonner'; // or your toast library

interface TodoFormData {
  title: string;
  description: string;
  date?: Date;
}

export const TodoForm = () => {
  const [createTodo, { isLoading }] = useCreateTodoMutation();
  const [open, setOpen] = useState(false);

  const [formData, setFormData] = useState<TodoFormData>({
    title: '',
    description: '',
    date: undefined,
  });

  const handleChange = (field: keyof TodoFormData, value: string | Date | undefined) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const handleSubmit = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();

    const { title, description, date } = formData;

    if (!title.trim()) return;

    try {
      const formattedDate = date ? format(date, 'yyyy-MM-dd') : undefined;

      const response = await createTodo({
        title: title.trim(),
        description: description.trim() || undefined,
        date: formattedDate,
        status: 'undone',
      }).unwrap();

      toast.success('Task created successfully!', {
        description: response?.data?.title || title,
      });

      setOpen(false);
      setFormData({ title: '', description: '', date: undefined });
    } catch (err: any) {
      toast.error('Failed to create task', {
        description: err?.data?.message || 'Please try again',
      });
    }
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button>
          <Plus className='h-4 w-4 mr-2' />
          Add New Task
        </Button>
      </DialogTrigger>

      <DialogContent className='sm:max-w-275 max-h-[85vh] overflow-y-auto'>
        <DialogHeader>
          <DialogTitle>Add New Task</DialogTitle>
          <DialogDescription>
            Enter the details for your new task. Click save when you're done.
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className='space-y-6'>
          <div className='space-y-2'>
            <Label htmlFor='title'>
              Task Title <span className='text-red-500'>*</span>
            </Label>
            <Input
              id='title'
              value={formData.title}
              onChange={(e) => handleChange('title', e.target.value)}
              placeholder='What needs to be done?'
              className='mt-1'
              required
            />
          </div>

          <div className='space-y-2'>
            <Label htmlFor='description'>Description</Label>
            <Textarea
              id='description'
              value={formData.description}
              onChange={(e) => handleChange('description', e.target.value)}
              placeholder='Add more details about this task...'
              className='mt-1 min-h-30 resize-y'
            />
          </div>

          <div className='space-y-2'>
            <Label>Due Date</Label>
            <Popover>
              <PopoverTrigger asChild>
                <Button
                  variant='outline'
                  className={cn(
                    'mt-1 w-full justify-start text-left font-normal',
                    !formData.date && 'text-muted-foreground',
                  )}
                >
                  <CalendarIcon className='mr-2 h-4 w-4' />
                  {formData.date ? format(formData.date, 'PPP') : 'Select due date'}
                </Button>
              </PopoverTrigger>
              <PopoverContent className='w-auto p-0'>
                <Calendar mode='single' selected={formData.date} onSelect={(d) => handleChange('date', d)} />
              </PopoverContent>
            </Popover>
          </div>

          <DialogFooter>
            <Button
              type='button'
              variant='outline'
              onClick={() => setOpen(false)}
              disabled={isLoading}
            >
              Cancel
            </Button>
            <Button type='submit' disabled={!formData.title.trim() || isLoading}>
              {isLoading ? 'Creating Task...' : 'Create Task'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
