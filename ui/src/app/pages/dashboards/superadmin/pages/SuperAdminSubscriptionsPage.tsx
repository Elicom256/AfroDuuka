import { useState } from 'react';
import {
  useGetSubscriptionsQuery,
  useCreateSubscriptionMutation,
  useUpdateSubscriptionMutation,
  useDeleteSubscriptionMutation,
} from '@/app/store/features/subscriptions/subscriptionsQuery';
import { useGetPlansQuery } from '@/app/store/features/plans/plansQuery';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PageLoadingState } from '@/utils/PageLoadingState';
import {
  Crown,
  Plus,
  Pencil,
  Trash2,
  Loader2,
  CalendarDays,
  Building2,
} from 'lucide-react';
import { toast } from 'sonner';

const statusColors: Record<string, string> = {
  active: 'bg-green-500/10 text-green-600 border-green-500/20',
  paused: 'bg-amber-500/10 text-amber-600 border-amber-500/20',
  cancelled: 'bg-red-500/10 text-red-600 border-red-500/20',
  expired: 'bg-gray-500/10 text-gray-600 border-gray-500/20',
};

export const SuperAdminSubscriptionsPage = () => {
  const { data, isLoading } = useGetSubscriptionsQuery();
  const { data: plansData } = useGetPlansQuery();
  const [createSubscription, { isLoading: isCreating }] = useCreateSubscriptionMutation();
  const [updateSubscription, { isLoading: isUpdating }] = useUpdateSubscriptionMutation();
  const [deleteSubscription, { isLoading: isDeleting }] = useDeleteSubscriptionMutation();

  const subscriptions = data?.subscriptions ?? [];
  const plans = plansData?.plans ?? [];

  const [addOpen, setAddOpen] = useState(false);
  const [editOpen, setEditOpen] = useState(false);
  const [editingSub, setEditingSub] = useState<any>(null);
  const [deleteId, setDeleteId] = useState<number | null>(null);

  const [form, setForm] = useState({
    plan_id: '',
    business_id: '',
    status: 'active',
    starts_at: '',
    ends_at: '',
  });

  const resetForm = () => {
    setForm({ plan_id: '', business_id: '', status: 'active', starts_at: '', ends_at: '' });
  };

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.plan_id) {
      toast.error('Plan is required');
      return;
    }
    try {
      await createSubscription({
        plan_id: Number(form.plan_id),
      }).unwrap();
      toast.success('Subscription created');
      setAddOpen(false);
      resetForm();
    } catch (err: any) {
      toast.error(err?.data?.message || 'Failed to create subscription');
    }
  };

  const handleEdit = (sub: any) => {
    setEditingSub(sub);
    setForm({
      plan_id: String(sub.plan_id),
      business_id: '',
      status: sub.status,
      starts_at: sub.starts_at ? sub.starts_at.slice(0, 16) : '',
      ends_at: sub.ends_at ? sub.ends_at.slice(0, 16) : '',
    });
    setEditOpen(true);
  };

  const handleUpdate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingSub) return;
    try {
      await updateSubscription({
        id: editingSub.id,
        body: {
          plan_id: Number(form.plan_id),
          status: form.status,
          starts_at: form.starts_at || undefined,
          ends_at: form.ends_at || undefined,
        },
      }).unwrap();
      toast.success('Subscription updated');
      setEditOpen(false);
      setEditingSub(null);
      resetForm();
    } catch (err: any) {
      toast.error(err?.data?.message || 'Failed to update subscription');
    }
  };

  const handleDelete = async () => {
    if (deleteId === null) return;
    try {
      await deleteSubscription(deleteId).unwrap();
      toast.success('Subscription deleted');
      setDeleteId(null);
    } catch (err: any) {
      toast.error(err?.data?.message || 'Failed to delete subscription');
    }
  };

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <div className='flex items-center justify-between'>
        <div>
          <h1 className='text-3xl font-bold tracking-tight flex items-center gap-3'>
            <Crown className='h-8 w-8' />
            Subscriptions
          </h1>
          <p className='text-muted-foreground mt-1'>Manage all business subscriptions</p>
        </div>
        <Dialog open={addOpen} onOpenChange={setAddOpen}>
          <DialogTrigger asChild>
            <Button size='sm'>
              <Plus className='h-4 w-4 mr-2' />
              Add Subscription
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>New Subscription</DialogTitle>
              <DialogDescription>Create a subscription for a business.</DialogDescription>
            </DialogHeader>
            <form onSubmit={handleCreate} className='space-y-4'>
              <div className='space-y-2'>
                <Label>Plan</Label>
                <Select value={form.plan_id} onValueChange={(v) => setForm({ ...form, plan_id: v })}>
                  <SelectTrigger><SelectValue placeholder='Select plan' /></SelectTrigger>
                  <SelectContent>
                    {plans.map((plan: any) => (
                      <SelectItem key={plan.id} value={String(plan.id)}>{plan.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <DialogFooter>
                <Button type='button' variant='outline' onClick={() => { setAddOpen(false); resetForm(); }}>Cancel</Button>
                <Button type='submit' disabled={isCreating}>
                  {isCreating && <Loader2 className='h-4 w-4 mr-2 animate-spin' />}
                  Create
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardHeader><CardTitle>All Subscriptions ({subscriptions.length})</CardTitle></CardHeader>
        <CardContent>
          {subscriptions.length === 0 ? (
            <p className='text-muted-foreground text-sm text-center py-8'>No subscriptions found.</p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Business</TableHead>
                  <TableHead>Plan</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Start Date</TableHead>
                  <TableHead>End Date</TableHead>
                  <TableHead className='text-right'>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {subscriptions.map((sub: any) => (
                  <TableRow key={sub.id}>
                    <TableCell>
                      <div className='flex items-center gap-2'>
                        <Building2 className='h-4 w-4 text-muted-foreground' />
                        <span className='font-medium'>{sub.business?.name ?? `Business #${sub.business_id}`}</span>
                      </div>
                    </TableCell>
                    <TableCell>{sub.plan?.name ?? 'N/A'}</TableCell>
                    <TableCell>
                      <Badge className={statusColors[sub.status] ?? ''} variant='outline'>{sub.status}</Badge>
                    </TableCell>
                    <TableCell>
                      <div className='flex items-center gap-1.5 text-sm'>
                        <CalendarDays className='h-3.5 w-3.5 text-muted-foreground' />
                        {sub.starts_at ? new Date(sub.starts_at).toLocaleDateString() : '-'}
                      </div>
                    </TableCell>
                    <TableCell>
                      <div className='flex items-center gap-1.5 text-sm'>
                        <CalendarDays className='h-3.5 w-3.5 text-muted-foreground' />
                        {sub.ends_at ? new Date(sub.ends_at).toLocaleDateString() : '-'}
                      </div>
                    </TableCell>
                    <TableCell className='text-right'>
                      <div className='flex items-center justify-end gap-1'>
                        <Button variant='ghost' size='icon' onClick={() => handleEdit(sub)}>
                          <Pencil className='h-4 w-4' />
                        </Button>
                        <AlertDialog>
                          <AlertDialogTrigger asChild>
                            <Button variant='ghost' size='icon' onClick={() => setDeleteId(sub.id)}>
                              <Trash2 className='h-4 w-4 text-destructive' />
                            </Button>
                          </AlertDialogTrigger>
                          <AlertDialogContent>
                            <AlertDialogHeader>
                              <AlertDialogTitle>Delete Subscription</AlertDialogTitle>
                              <AlertDialogDescription>
                                Are you sure? This action cannot be undone.
                              </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                              <AlertDialogCancel onClick={() => setDeleteId(null)}>Cancel</AlertDialogCancel>
                              <AlertDialogAction onClick={handleDelete} disabled={isDeleting}>
                                {isDeleting && <Loader2 className='h-4 w-4 mr-2 animate-spin' />}
                                Delete
                              </AlertDialogAction>
                            </AlertDialogFooter>
                          </AlertDialogContent>
                        </AlertDialog>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      <Dialog open={editOpen} onOpenChange={setEditOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Edit Subscription</DialogTitle>
            <DialogDescription>Update subscription details.</DialogDescription>
          </DialogHeader>
          <form onSubmit={handleUpdate} className='space-y-4'>
            <div className='space-y-2'>
              <Label>Plan</Label>
              <Select value={form.plan_id} onValueChange={(v) => setForm({ ...form, plan_id: v })}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  {plans.map((plan: any) => (
                    <SelectItem key={plan.id} value={String(plan.id)}>{plan.name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className='space-y-2'>
              <Label>Status</Label>
              <Select value={form.status} onValueChange={(v) => setForm({ ...form, status: v })}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  {['active', 'paused', 'cancelled', 'expired'].map((s) => (
                    <SelectItem key={s} value={s}>{s}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className='grid grid-cols-2 gap-4'>
              <div className='space-y-2'>
                <Label>Start Date</Label>
                <Input type='datetime-local' value={form.starts_at} onChange={(e) => setForm({ ...form, starts_at: e.target.value })} />
              </div>
              <div className='space-y-2'>
                <Label>End Date</Label>
                <Input type='datetime-local' value={form.ends_at} onChange={(e) => setForm({ ...form, ends_at: e.target.value })} />
              </div>
            </div>
            <DialogFooter>
              <Button type='button' variant='outline' onClick={() => { setEditOpen(false); setEditingSub(null); resetForm(); }}>Cancel</Button>
              <Button type='submit' disabled={isUpdating}>
                {isUpdating && <Loader2 className='h-4 w-4 mr-2 animate-spin' />}
                Update
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
};
