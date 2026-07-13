import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Pencil, Trash2 } from 'lucide-react';
import {
  useUpdateExpenseCategoryMutation,
  useDeleteExpenseCategoryMutation,
} from '@/app/store/features/business/admin/expenseCategoriesQuery';

type ExpenseCategoryTableProps = {
  categories: any[];
};

export const ExpenseCategoryTable = ({ categories }: ExpenseCategoryTableProps) => {
  const [updateCategory] = useUpdateExpenseCategoryMutation();
  const [deleteCategory] = useDeleteExpenseCategoryMutation();
  const [editOpen, setEditOpen] = useState(false);
  const [selected, setSelected] = useState<any>(null);
  const [formData, setFormData] = useState({ name: '', description: '' });

  const handleEdit = (item: any) => {
    setSelected(item);
    setFormData({ name: item.name, description: item.description ?? '' });
    setEditOpen(true);
  };

  const handleUpdate = async (e: React.SyntheticEvent) => {
    e.preventDefault();
    if (!selected?.id) return;
    try {
      const res = await updateCategory({ id: selected.id, body: formData }).unwrap();
      toast.success(res?.message || 'Category updated');
      setEditOpen(false);
      setSelected(null);
    } catch {
      toast.error('Failed to update category');
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Are you sure you want to delete this category?')) return;
    try {
      await deleteCategory(id).unwrap();
      toast.success('Category deleted');
    } catch {
      toast.error('Failed to delete category');
    }
  };

  return (
    <>
      <Card>
        <CardHeader>
          <CardTitle>All Categories</CardTitle>
        </CardHeader>
        <CardContent className='p-0'>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Description</TableHead>
                <TableHead className='w-24'>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {categories.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={3} className='py-10 text-center text-muted-foreground'>No categories found.</TableCell>
                </TableRow>
              ) : (
                categories.map((cat: any) => (
                  <TableRow key={cat.id}>
                    <TableCell className='font-medium'>{cat.name}</TableCell>
                    <TableCell className='text-muted-foreground'>{cat.description || '—'}</TableCell>
                    <TableCell>
                      <div className='flex gap-2'>
                        <Button variant='ghost' size='icon' onClick={() => handleEdit(cat)}>
                          <Pencil className='h-4 w-4' />
                        </Button>
                        <Button variant='ghost' size='icon' onClick={() => handleDelete(cat.id)}>
                          <Trash2 className='h-4 w-4' />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <Dialog open={editOpen} onOpenChange={(o) => { setEditOpen(o); if (!o) { setSelected(null); setFormData({ name: '', description: '' }); } }}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Edit Expense Category</DialogTitle>
            <DialogDescription>Update the category details.</DialogDescription>
          </DialogHeader>
          <form onSubmit={handleUpdate}>
            <div className='grid gap-4 py-4'>
              <div className='grid grid-cols-4 items-center gap-4'>
                <Label htmlFor='edit-name' className='text-right'>Name</Label>
                <Input id='edit-name' value={formData.name} onChange={(e) => setFormData((p) => ({ ...p, name: e.target.value }))} className='col-span-3' required />
              </div>
              <div className='grid grid-cols-4 items-center gap-4'>
                <Label htmlFor='edit-description' className='text-right'>Description</Label>
                <Textarea id='edit-description' value={formData.description} onChange={(e) => setFormData((p) => ({ ...p, description: e.target.value }))} className='col-span-3' />
              </div>
            </div>
            <DialogFooter>
              <Button type='submit'>Update</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </>
  );
};
