import { Plus, Trash2, MapPin, Phone, Building } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { toast } from 'sonner';
import type { BranchData } from './types';

interface BranchSetupProps {
  branches: BranchData[];
  onChange: (branches: BranchData[]) => void;
  onNext: () => void;
  onBack: () => void;
}

export const BranchSetup: React.FC<BranchSetupProps> = ({
  branches,
  onChange,
  onNext,
  onBack,
}) => {
  const addBranch = () => {
    onChange([...branches, { name: '', address: '', phone: '' }]);
  };

  const removeBranch = (index: number) => {
    if (branches.length <= 1) {
      toast.error('At least one branch is required.');
      return;
    }
    onChange(branches.filter((_, i) => i !== index));
  };

  const updateBranch = (index: number, field: keyof BranchData, value: string) => {
    const updated = [...branches];
    updated[index] = { ...updated[index], [field]: value };
    onChange(updated);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    const hasEmptyName = branches.some((b) => !b.name.trim());
    if (hasEmptyName) {
      toast.error('All branches must have a name.');
      return;
    }

    onNext();
  };

  return (
    <form onSubmit={handleSubmit} className='space-y-5'>
      <div className='space-y-4'>
        {branches.map((branch, index) => (
          <Card key={index} className='relative'>
            <CardHeader className='pb-3'>
              <div className='flex items-center justify-between'>
                <CardTitle className='text-sm font-medium'>Branch {index + 1}</CardTitle>
                {branches.length > 1 && (
                  <Button
                    type='button'
                    variant='ghost'
                    size='sm'
                    onClick={() => removeBranch(index)}
                    className='h-8 w-8 p-0 text-destructive hover:text-destructive'
                  >
                    <Trash2 className='h-4 w-4' />
                  </Button>
                )}
              </div>
            </CardHeader>
            <CardContent className='space-y-4'>
              <div className='space-y-2'>
                <Label htmlFor={`branch_name_${index}`}>
                  <Building className='h-4 w-4 text-muted-foreground' />
                  Branch name
                </Label>
                <Input
                  id={`branch_name_${index}`}
                  value={branch.name}
                  onChange={(e) => updateBranch(index, 'name', e.target.value)}
                  placeholder='Main Branch'
                  required
                />
              </div>

              <div className='space-y-2'>
                <Label htmlFor={`branch_address_${index}`}>
                  <MapPin className='h-4 w-4 text-muted-foreground' />
                  Address
                </Label>
                <Input
                  id={`branch_address_${index}`}
                  value={branch.address}
                  onChange={(e) => updateBranch(index, 'address', e.target.value)}
                  placeholder='Plot 123, Kampala Road'
                  required
                />
              </div>

              <div className='space-y-2'>
                <Label htmlFor={`branch_phone_${index}`}>
                  <Phone className='h-4 w-4 text-muted-foreground' />
                  Phone
                </Label>
                <Input
                  id={`branch_phone_${index}`}
                  type='tel'
                  value={branch.phone}
                  onChange={(e) => updateBranch(index, 'phone', e.target.value)}
                  placeholder='+256 700 000 000'
                  required
                />
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      <Button
        type='button'
        variant='outline'
        onClick={addBranch}
        className='w-full border-dashed'
      >
        <Plus className='mr-2 h-4 w-4' />
        Add another branch
      </Button>

      <div className='flex gap-3'>
        <Button type='button' variant='outline' onClick={onBack} className='flex-1'>
          Back
        </Button>
        <Button type='submit' className='flex-1'>
          Review
        </Button>
      </div>
    </form>
  );
};
