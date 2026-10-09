import { useState } from 'react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  useStoreSalaryMutation,
  useUpdateSalaryMutation,
} from '@/app/store/features/business/executive/salaryQuery';
import { useRolesQuery } from '@/app/store/features/business/roles/rolesQuery';
import { useBranchesQuery } from '@/app/store/features/business/branches/branchesQuery';
import { toast } from 'sonner';
import { PageLoadingState } from '@/utils/PageLoadingState';

type SalaryFormProps = {
  editItem?: any;
  trigger?: React.ReactNode;
  open?: boolean;
  onOpenChange?: (open: boolean) => void;
};

const ALL_BRANCHES = 'all';

const emptyForm = {
  role_id: '',
  amount: '',
  period: 'monthly',
  status: 'active',
  business_branch_id: ALL_BRANCHES,
};

export const SalaryForm = ({ editItem, trigger, open: controlledOpen, onOpenChange }: SalaryFormProps) => {
  const [internalOpen, setInternalOpen] = useState(false);
  const open = controlledOpen ?? internalOpen;
  const setOpen = onOpenChange ?? setInternalOpen;

  const [storeSalary, { isLoading: isStoring }] = useStoreSalaryMutation();
  const [updateSalary, { isLoading: isUpdating }] = useUpdateSalaryMutation();
  const { data: rolesData, isLoading: rolesLoading } = useRolesQuery();
  const { data: branchesData } = useBranchesQuery();

  const roles = rolesData ?? [];
  const branches = branchesData?.branches ?? [];
  const isEdit = Boolean(editItem);

  // Seeded from the props rather than synced in an effect. The parent keys this
  // component on the row being edited, so switching rows remounts it and the state is
  // correct on the first render — a useEffect here would setState after paint, which
  // the repo's lint config rejects (react-hooks/set-state-in-effect) and which also
  // shows one frame of the previous row's values.
  const [formData, setFormData] = useState(() =>
    editItem
      ? {
          role_id: editItem.role_id?.toString() ?? '',
          amount: editItem.amount?.toString() ?? '',
          period: editItem.period ?? 'monthly',
          status: editItem.status ?? 'active',
          business_branch_id: editItem.business_branch_id?.toString() ?? ALL_BRANCHES,
        }
      : emptyForm,
  );

  const updateForm = (key: string, value: any) => {
    setFormData((prev) => ({ ...prev, [key]: value }));
  };

  const handleSubmit = async (e: React.SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();

    if (!formData.role_id) {
      toast.error('Select a role');
      return;
    }

    const amount = Number(formData.amount);
    if (Number.isNaN(amount) || amount < 0) {
      toast.error('Enter a valid amount');
      return;
    }

    const payload = {
      role_id: Number(formData.role_id),
      amount,
      period: formData.period,
      status: formData.status,
      business_branch_id: formData.business_branch_id === ALL_BRANCHES ? null : Number(formData.business_branch_id),
    };

    try {
      if (isEdit) {
        const res = await updateSalary({ id: editItem.id, body: payload }).unwrap();
        toast.success(res.message);
      } else {
        const res = await storeSalary(payload).unwrap();
        toast.success(res.message);
      }

      setOpen(false);
    } catch (error) {
      console.error('Salary save error:', error);
    }
  };

  if (rolesLoading || isStoring || isUpdating) return <PageLoadingState />;

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{trigger || <Button>{isEdit ? 'Edit Salary' : 'Add Salary'}</Button>}</DialogTrigger>

      <DialogContent className='sm:max-w-lg max-h-[85vh] overflow-y-scroll'>
        <DialogHeader>
          <DialogTitle>{isEdit ? 'Edit Salary' : 'Add Salary'}</DialogTitle>
          <DialogDescription>
            A salary is set per role, so everyone holding that role is paid the same amount.
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className='space-y-6 py-4'>
          <div className='grid grid-cols-1 lg:grid-cols-2 gap-6'>
            <div className='space-y-2'>
              <Label htmlFor='role_id'>
                Role <span className='text-red-500'>*</span>
              </Label>
              <Select
                value={formData.role_id}
                onValueChange={(value) => updateForm('role_id', value)}
                disabled={isEdit}
              >
                <SelectTrigger id='role_id'>
                  <SelectValue placeholder='Select role' />
                </SelectTrigger>
                <SelectContent>
                  {roles.map((role: any) => (
                    <SelectItem key={role.id} value={role.id.toString()}>
                      {role.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className='space-y-2'>
              <Label htmlFor='amount'>
                Amount <span className='text-red-500'>*</span>
              </Label>
              <Input
                id='amount'
                type='number'
                step='0.01'
                placeholder='1500000.00'
                value={formData.amount}
                onChange={(e) => updateForm('amount', e.target.value)}
                required
              />
            </div>

            <div className='space-y-2'>
              <Label htmlFor='period'>Period</Label>
              <Select value={formData.period} onValueChange={(value) => updateForm('period', value)}>
                <SelectTrigger id='period'>
                  <SelectValue placeholder='Select period' />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value='monthly'>Monthly</SelectItem>
                  <SelectItem value='yearly'>Yearly</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className='space-y-2'>
              <Label htmlFor='status'>Status</Label>
              <Select value={formData.status} onValueChange={(value) => updateForm('status', value)}>
                <SelectTrigger id='status'>
                  <SelectValue placeholder='Select status' />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value='active'>Active</SelectItem>
                  <SelectItem value='inactive'>Inactive</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className='space-y-2 lg:col-span-2'>
              <Label htmlFor='business_branch_id'>Branch</Label>
              <Select
                value={formData.business_branch_id}
                onValueChange={(value) => updateForm('business_branch_id', value)}
              >
                <SelectTrigger id='business_branch_id'>
                  <SelectValue placeholder='All branches' />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value={ALL_BRANCHES}>All branches</SelectItem>
                  {branches.map((branch: any) => (
                    <SelectItem key={branch.id} value={branch.id.toString()}>
                      {branch.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>

          <DialogFooter>
            <Button
              type='button'
              variant='outline'
              onClick={() => setOpen(false)}
              disabled={isStoring || isUpdating}
            >
              Cancel
            </Button>
            <Button type='submit' disabled={isStoring || isUpdating}>
              {isStoring || isUpdating ? 'Saving...' : isEdit ? 'Update Salary' : 'Add Salary'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
