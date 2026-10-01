import { Pencil, User, Building2, MapPin } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { LoadingState } from '@/utils/LoadingState';
import type { AccountData, BusinessData, BranchData } from './types';

interface PreviewProps {
  account: AccountData;
  business: BusinessData;
  branches: BranchData[];
  onEdit: (step: number) => void;
  onSave: () => void;
  isLoading: boolean;
}

export const Preview: React.FC<PreviewProps> = ({
  account,
  business,
  branches,
  onEdit,
  onSave,
  isLoading,
}) => {
  return (
    <div className='space-y-5'>
      <Card>
        <CardHeader className='pb-3'>
          <div className='flex items-center justify-between'>
            <CardTitle className='flex items-center gap-2 text-sm font-medium'>
              <User className='h-4 w-4' />
              Account
            </CardTitle>
            <Button variant='ghost' size='sm' onClick={() => onEdit(1)}>
              <Pencil className='mr-1 h-3 w-3' />
              Edit
            </Button>
          </div>
        </CardHeader>
        <CardContent className='space-y-2 text-sm'>
          <div className='grid grid-cols-2 gap-2'>
            <span className='text-muted-foreground'>Name</span>
            <span className='font-medium'>
              {account.firstname} {account.lastname}
            </span>
          </div>
          <Separator />
          <div className='grid grid-cols-2 gap-2'>
            <span className='text-muted-foreground'>Email</span>
            <span className='font-medium'>{account.email}</span>
          </div>
          <Separator />
          <div className='grid grid-cols-2 gap-2'>
            <span className='text-muted-foreground'>Phone</span>
            <span className='font-medium'>{account.phone}</span>
          </div>
          <Separator />
          <div className='grid grid-cols-2 gap-2'>
            <span className='text-muted-foreground'>Username</span>
            <span className='font-medium'>{account.username}</span>
          </div>
          <Separator />
          <div className='grid grid-cols-2 gap-2'>
            <span className='text-muted-foreground'>Country</span>
            <span className='font-medium'>{account.country_id}</span>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className='pb-3'>
          <div className='flex items-center justify-between'>
            <CardTitle className='flex items-center gap-2 text-sm font-medium'>
              <Building2 className='h-4 w-4' />
              Business
            </CardTitle>
            <Button variant='ghost' size='sm' onClick={() => onEdit(2)}>
              <Pencil className='mr-1 h-3 w-3' />
              Edit
            </Button>
          </div>
        </CardHeader>
        <CardContent className='space-y-2 text-sm'>
          <div className='grid grid-cols-2 gap-2'>
            <span className='text-muted-foreground'>Name</span>
            <span className='font-medium'>{business.name}</span>
          </div>
          <Separator />
          <div className='grid grid-cols-2 gap-2'>
            <span className='text-muted-foreground'>Category</span>
            <span className='font-medium'>{business.business_category_id}</span>
          </div>
          <Separator />
          <div className='grid grid-cols-2 gap-2'>
            <span className='text-muted-foreground'>Email</span>
            <span className='font-medium'>{business.email}</span>
          </div>
          <Separator />
          <div className='grid grid-cols-2 gap-2'>
            <span className='text-muted-foreground'>Phone</span>
            <span className='font-medium'>{business.phone}</span>
          </div>
          <Separator />
          <div className='grid grid-cols-2 gap-2'>
            <span className='text-muted-foreground'>Address</span>
            <span className='font-medium'>{business.address}</span>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className='pb-3'>
          <div className='flex items-center justify-between'>
            <CardTitle className='flex items-center gap-2 text-sm font-medium'>
              <MapPin className='h-4 w-4' />
              Branches ({branches.length})
            </CardTitle>
            <Button variant='ghost' size='sm' onClick={() => onEdit(3)}>
              <Pencil className='mr-1 h-3 w-3' />
              Edit
            </Button>
          </div>
        </CardHeader>
        <CardContent className='space-y-3'>
          {branches.map((branch, index) => (
            <div key={index} className='rounded-lg border border-border/50 p-3'>
              <p className='text-sm font-medium'>{branch.name}</p>
              <p className='text-xs text-muted-foreground'>{branch.address}</p>
              <p className='text-xs text-muted-foreground'>{branch.phone}</p>
            </div>
          ))}
        </CardContent>
      </Card>

      <Button onClick={onSave} className='w-full' disabled={isLoading}>
        {isLoading ? <LoadingState /> : 'Save and continue'}
      </Button>
    </div>
  );
};
