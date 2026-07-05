import { useState } from 'react';
import {
  useGetAdminPlansQuery,
  useCreatePlanMutation,
  useUpdatePlanMutation,
  useDeletePlanMutation,
} from '@/app/store/features/plans/adminPlansQuery';
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
import { Textarea } from '@/components/ui/textarea';
import { PageLoadingState } from '@/utils/PageLoadingState';
import {
  CreditCard,
  Plus,
  Pencil,
  Trash2,
  Loader2,
  CheckCircle2,
  XCircle,
} from 'lucide-react';
import { toast } from 'sonner';

export const SuperAdminPlansPage = () => {
  const { data, isLoading } = useGetAdminPlansQuery();
  const [createPlan, { isLoading: isCreating }] = useCreatePlanMutation();
  const [updatePlan, { isLoading: isUpdating }] = useUpdatePlanMutation();
  const [deletePlan, { isLoading: isDeleting }] = useDeletePlanMutation();

  const plans = data?.plans ?? [];

  const [addOpen, setAddOpen] = useState(false);
  const [editOpen, setEditOpen] = useState(false);
  const [editingPlan, setEditingPlan] = useState<any>(null);
  const [deleteId, setDeleteId] = useState<number | null>(null);

  const [form, setForm] = useState({
    name: '',
    slug: '',
    mark: '',
    description: '',
    monthly_price: '',
    yearly_price: '',
    discount_percentage: '0',
    billing_cycle: 'monthly',
    currency: 'UGX',
    status: 'active',
    is_active: true,
    sort_order: '0',
    features: '',
    limits: '',
  });

  const resetForm = () => {
    setForm({
      name: '',
      slug: '',
      mark: '',
      description: '',
      monthly_price: '',
      yearly_price: '',
      discount_percentage: '0',
      billing_cycle: 'monthly',
      currency: 'UGX',
      status: 'active',
      is_active: true,
      sort_order: '0',
      features: '',
      limits: '',
    });
  };

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!form.name || !form.slug || !form.monthly_price || !form.yearly_price) {
      toast.error('Name, slug, and prices are required');
      return;
    }
    try {
      await createPlan({
        name: form.name,
        slug: form.slug,
        mark: form.mark || undefined,
        description: form.description || undefined,
        monthly_price: Number(form.monthly_price),
        yearly_price: Number(form.yearly_price),
        discount_percentage: Number(form.discount_percentage),
        billing_cycle: form.billing_cycle,
        currency: form.currency,
        status: form.status,
        is_active: form.status === 'active',
        sort_order: Number(form.sort_order),
        features: form.features ? form.features.split('\n').filter(Boolean) : undefined,
        limits: form.limits ? JSON.parse(form.limits) : undefined,
      }).unwrap();
      toast.success('Plan created');
      setAddOpen(false);
      resetForm();
    } catch (err: any) {
      toast.error(err?.data?.message || 'Failed to create plan');
    }
  };

  const handleEdit = (plan: any) => {
    setEditingPlan(plan);
    setForm({
      name: plan.name,
      slug: plan.slug,
      mark: plan.mark ?? '',
      description: plan.description ?? '',
      monthly_price: String(plan.monthly_price),
      yearly_price: String(plan.yearly_price),
      discount_percentage: String(plan.discount_percentage ?? 0),
      billing_cycle: plan.billing_cycle ?? 'monthly',
      currency: plan.currency ?? 'UGX',
      status: plan.status,
      is_active: plan.is_active,
      sort_order: String(plan.sort_order ?? 0),
      features: Array.isArray(plan.features) ? plan.features.join('\n') : '',
      limits: plan.limits ? JSON.stringify(plan.limits, null, 2) : '',
    });
    setEditOpen(true);
  };

  const handleUpdate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingPlan) return;
    try {
      await updatePlan({
        id: editingPlan.id,
        body: {
          name: form.name,
          slug: form.slug,
          mark: form.mark || undefined,
          description: form.description || undefined,
          monthly_price: Number(form.monthly_price),
          yearly_price: Number(form.yearly_price),
          discount_percentage: Number(form.discount_percentage),
          billing_cycle: form.billing_cycle,
          currency: form.currency,
          status: form.status,
          is_active: form.status === 'active',
          sort_order: Number(form.sort_order),
          features: form.features ? form.features.split('\n').filter(Boolean) : undefined,
          limits: form.limits ? JSON.parse(form.limits) : undefined,
        },
      }).unwrap();
      toast.success('Plan updated');
      setEditOpen(false);
      setEditingPlan(null);
      resetForm();
    } catch (err: any) {
      toast.error(err?.data?.message || 'Failed to update plan');
    }
  };

  const handleDelete = async () => {
    if (deleteId === null) return;
    try {
      await deletePlan(deleteId).unwrap();
      toast.success('Plan deleted');
      setDeleteId(null);
    } catch (err: any) {
      toast.error(err?.data?.message || 'Failed to delete plan');
    }
  };

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <div className='flex items-center justify-between'>
        <div>
          <h1 className='text-3xl font-bold tracking-tight flex items-center gap-3'>
            <CreditCard className='h-8 w-8' />
            Plans
          </h1>
          <p className='text-muted-foreground mt-1'>Manage subscription plans and pricing</p>
        </div>
        <Dialog open={addOpen} onOpenChange={setAddOpen}>
          <DialogTrigger asChild>
            <Button size='sm'>
              <Plus className='h-4 w-4 mr-2' />
              Add Plan
            </Button>
          </DialogTrigger>
          <DialogContent className='max-w-lg max-h-[90vh] overflow-y-auto'>
            <DialogHeader>
              <DialogTitle>New Plan</DialogTitle>
              <DialogDescription>Create a new subscription plan.</DialogDescription>
            </DialogHeader>
            <form onSubmit={handleCreate} className='space-y-4'>
              <div className='grid grid-cols-2 gap-4'>
                <div className='space-y-2'>
                  <Label>Name *</Label>
                  <Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} placeholder='Basic' />
                </div>
                <div className='space-y-2'>
                  <Label>Slug *</Label>
                  <Input value={form.slug} onChange={(e) => setForm({ ...form, slug: e.target.value })} placeholder='basic' />
                </div>
              </div>
              <div className='grid grid-cols-2 gap-4'>
                <div className='space-y-2'>
                  <Label>Monthly Price *</Label>
                  <Input type='number' value={form.monthly_price} onChange={(e) => setForm({ ...form, monthly_price: e.target.value })} />
                </div>
                <div className='space-y-2'>
                  <Label>Yearly Price *</Label>
                  <Input type='number' value={form.yearly_price} onChange={(e) => setForm({ ...form, yearly_price: e.target.value })} />
                </div>
              </div>
              <div className='grid grid-cols-2 gap-4'>
                <div className='space-y-2'>
                  <Label>Billing Cycle</Label>
                  <Select value={form.billing_cycle} onValueChange={(v) => setForm({ ...form, billing_cycle: v })}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value='monthly'>Monthly</SelectItem>
                      <SelectItem value='yearly'>Yearly</SelectItem>
                      <SelectItem value='both'>Both</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className='space-y-2'>
                  <Label>Currency</Label>
                  <Input value={form.currency} onChange={(e) => setForm({ ...form, currency: e.target.value })} placeholder='UGX' />
                </div>
              </div>
              <div className='grid grid-cols-2 gap-4'>
                <div className='space-y-2'>
                  <Label>Mark</Label>
                  <Select value={form.mark} onValueChange={(v) => setForm({ ...form, mark: v })}>
                    <SelectTrigger><SelectValue placeholder='None' /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value='Affordable'>Affordable</SelectItem>
                      <SelectItem value='Most Popular'>Most Popular</SelectItem>
                      <SelectItem value='Best Value'>Best Value</SelectItem>
                      <SelectItem value='Enterprise'>Enterprise</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className='space-y-2'>
                  <Label>Status</Label>
                  <Select value={form.status} onValueChange={(v) => setForm({ ...form, status: v })}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value='active'>Active</SelectItem>
                      <SelectItem value='inactive'>Inactive</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
              <div className='grid grid-cols-2 gap-4'>
                <div className='space-y-2'>
                  <Label>Discount %</Label>
                  <Input type='number' value={form.discount_percentage} onChange={(e) => setForm({ ...form, discount_percentage: e.target.value })} />
                </div>
                <div className='space-y-2'>
                  <Label>Sort Order</Label>
                  <Input type='number' value={form.sort_order} onChange={(e) => setForm({ ...form, sort_order: e.target.value })} />
                </div>
              </div>
              <div className='space-y-2'>
                <Label>Description</Label>
                <Textarea value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} rows={2} />
              </div>
              <div className='space-y-2'>
                <Label>Features (one per line)</Label>
                <Textarea value={form.features} onChange={(e) => setForm({ ...form, features: e.target.value })} rows={3} placeholder='Up to 10 employees&#10;Basic analytics&#10;Email support' />
              </div>
              <div className='space-y-2'>
                <Label>Limits (JSON)</Label>
                <Textarea value={form.limits} onChange={(e) => setForm({ ...form, limits: e.target.value })} rows={2} placeholder='{"maxProducts": 50, "maxBranches": 1}' />
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
        <CardHeader><CardTitle>All Plans ({plans.length})</CardTitle></CardHeader>
        <CardContent>
          {plans.length === 0 ? (
            <p className='text-muted-foreground text-sm text-center py-8'>No plans found.</p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Monthly</TableHead>
                  <TableHead>Yearly</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Mark</TableHead>
                  <TableHead>Currency</TableHead>
                  <TableHead className='text-right'>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {plans.map((plan: any) => (
                  <TableRow key={plan.id}>
                    <TableCell>
                      <div>
                        <span className='font-medium'>{plan.name}</span>
                        {plan.description && (
                          <p className='text-xs text-muted-foreground truncate max-w-[200px]'>{plan.description}</p>
                        )}
                      </div>
                    </TableCell>
                    <TableCell className='font-medium'>{Number(plan.monthly_price).toLocaleString()}</TableCell>
                    <TableCell className='font-medium'>{Number(plan.yearly_price).toLocaleString()}</TableCell>
                    <TableCell>
                      <Badge variant='outline' className={plan.status === 'active' ? 'bg-green-500/10 text-green-600' : 'bg-gray-500/10 text-gray-600'}>
                        {plan.status === 'active' ? <CheckCircle2 className='h-3 w-3 mr-1' /> : <XCircle className='h-3 w-3 mr-1' />}
                        {plan.status}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      {plan.mark && (
                        <Badge variant='secondary' className='text-xs'>{plan.mark}</Badge>
                      )}
                    </TableCell>
                    <TableCell>{plan.currency}</TableCell>
                    <TableCell className='text-right'>
                      <div className='flex items-center justify-end gap-1'>
                        <Button variant='ghost' size='icon' onClick={() => handleEdit(plan)}>
                          <Pencil className='h-4 w-4' />
                        </Button>
                        <AlertDialog>
                          <AlertDialogTrigger asChild>
                            <Button variant='ghost' size='icon' onClick={() => setDeleteId(plan.id)}>
                              <Trash2 className='h-4 w-4 text-destructive' />
                            </Button>
                          </AlertDialogTrigger>
                          <AlertDialogContent>
                            <AlertDialogHeader>
                              <AlertDialogTitle>Delete Plan</AlertDialogTitle>
                              <AlertDialogDescription>
                                Are you sure? This action cannot be undone. This will also affect subscriptions using this plan.
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
        <DialogContent className='max-w-lg max-h-[90vh] overflow-y-auto'>
          <DialogHeader>
            <DialogTitle>Edit Plan</DialogTitle>
            <DialogDescription>Update plan details.</DialogDescription>
          </DialogHeader>
          <form onSubmit={handleUpdate} className='space-y-4'>
            <div className='grid grid-cols-2 gap-4'>
              <div className='space-y-2'>
                <Label>Name</Label>
                <Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
              </div>
              <div className='space-y-2'>
                <Label>Slug</Label>
                <Input value={form.slug} onChange={(e) => setForm({ ...form, slug: e.target.value })} />
              </div>
            </div>
            <div className='grid grid-cols-2 gap-4'>
              <div className='space-y-2'>
                <Label>Monthly Price</Label>
                <Input type='number' value={form.monthly_price} onChange={(e) => setForm({ ...form, monthly_price: e.target.value })} />
              </div>
              <div className='space-y-2'>
                <Label>Yearly Price</Label>
                <Input type='number' value={form.yearly_price} onChange={(e) => setForm({ ...form, yearly_price: e.target.value })} />
              </div>
            </div>
            <div className='grid grid-cols-2 gap-4'>
              <div className='space-y-2'>
                <Label>Billing Cycle</Label>
                <Select value={form.billing_cycle} onValueChange={(v) => setForm({ ...form, billing_cycle: v })}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value='monthly'>Monthly</SelectItem>
                    <SelectItem value='yearly'>Yearly</SelectItem>
                    <SelectItem value='both'>Both</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className='space-y-2'>
                <Label>Currency</Label>
                <Input value={form.currency} onChange={(e) => setForm({ ...form, currency: e.target.value })} />
              </div>
            </div>
            <div className='grid grid-cols-2 gap-4'>
              <div className='space-y-2'>
                <Label>Mark</Label>
                <Select value={form.mark} onValueChange={(v) => setForm({ ...form, mark: v })}>
                  <SelectTrigger><SelectValue placeholder='None' /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value='Affordable'>Affordable</SelectItem>
                    <SelectItem value='Most Popular'>Most Popular</SelectItem>
                    <SelectItem value='Best Value'>Best Value</SelectItem>
                    <SelectItem value='Enterprise'>Enterprise</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className='space-y-2'>
                <Label>Status</Label>
                <Select value={form.status} onValueChange={(v) => setForm({ ...form, status: v })}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value='active'>Active</SelectItem>
                    <SelectItem value='inactive'>Inactive</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
            <div className='grid grid-cols-2 gap-4'>
              <div className='space-y-2'>
                <Label>Discount %</Label>
                <Input type='number' value={form.discount_percentage} onChange={(e) => setForm({ ...form, discount_percentage: e.target.value })} />
              </div>
              <div className='space-y-2'>
                <Label>Sort Order</Label>
                <Input type='number' value={form.sort_order} onChange={(e) => setForm({ ...form, sort_order: e.target.value })} />
              </div>
            </div>
            <div className='space-y-2'>
              <Label>Description</Label>
              <Textarea value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} rows={2} />
            </div>
            <div className='space-y-2'>
              <Label>Features (one per line)</Label>
              <Textarea value={form.features} onChange={(e) => setForm({ ...form, features: e.target.value })} rows={3} />
            </div>
            <div className='space-y-2'>
              <Label>Limits (JSON)</Label>
              <Textarea value={form.limits} onChange={(e) => setForm({ ...form, limits: e.target.value })} rows={2} />
            </div>
            <DialogFooter>
              <Button type='button' variant='outline' onClick={() => { setEditOpen(false); setEditingPlan(null); resetForm(); }}>Cancel</Button>
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
