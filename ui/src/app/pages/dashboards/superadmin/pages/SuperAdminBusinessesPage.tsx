import { useState } from 'react';
import {
  useGetSuperAdminBusinessesQuery,
  useUpdateBusinessStatusMutation,
} from '@/app/store/features/business/superAdminBusinessesQuery';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { Building2, Loader2, Search } from 'lucide-react';
import { toast } from 'sonner';
import { Input } from '@/components/ui/input';

const statusColors: Record<string, string> = {
  active: 'bg-green-500/10 text-green-600 border-green-500/20',
  deactivated: 'bg-red-500/10 text-red-600 border-red-500/20',
  banned: 'bg-gray-500/10 text-gray-600 border-gray-500/20',
};

export const SuperAdminBusinessesPage = () => {
  const { data, isLoading } = useGetSuperAdminBusinessesQuery();
  const [updateStatus, { isLoading: isUpdating }] = useUpdateBusinessStatusMutation();
  const [search, setSearch] = useState('');

  const businesses = data?.businesses ?? [];

  const filtered = search
    ? businesses.filter((b: any) =>
        b.name.toLowerCase().includes(search.toLowerCase()) ||
        b.email?.toLowerCase().includes(search.toLowerCase()) ||
        b.phone?.includes(search)
      )
    : businesses;

  const handleStatusChange = async (businessId: number, status: string) => {
    try {
      await updateStatus({ id: businessId, status }).unwrap();
      toast.success(`Business ${status === 'active' ? 'activated' : status}`);
    } catch (err: any) {
      toast.error(err?.data?.message || 'Failed to update status');
    }
  };

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight flex items-center gap-3'>
          <Building2 className='h-8 w-8' />
          Businesses
        </h1>
        <p className='text-muted-foreground mt-1'>Manage all registered businesses</p>
      </div>

      <div className='relative max-w-sm'>
        <Search className='absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground' />
        <Input
          placeholder='Search businesses...'
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className='pl-9'
        />
      </div>

      <Card>
        <CardHeader><CardTitle>All Businesses ({filtered.length})</CardTitle></CardHeader>
        <CardContent>
          {filtered.length === 0 ? (
            <p className='text-muted-foreground text-sm text-center py-8'>
              {search ? 'No businesses match your search.' : 'No businesses registered.'}
            </p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Email</TableHead>
                  <TableHead>Phone</TableHead>
                  <TableHead>Users</TableHead>
                  <TableHead>Balance</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className='text-right'>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filtered.map((business: any) => (
                  <TableRow key={business.id}>
                    <TableCell className='font-medium'>{business.name}</TableCell>
                    <TableCell className='text-sm text-muted-foreground'>{business.email ?? '-'}</TableCell>
                    <TableCell className='text-sm text-muted-foreground'>{business.phone ?? '-'}</TableCell>
                    <TableCell className='text-sm text-muted-foreground'>{business.users?.length ?? 0}</TableCell>
                    <TableCell className='text-sm'>{Number(business.subscription_balance).toLocaleString()}</TableCell>
                    <TableCell>
                      <Badge variant='outline' className={statusColors[business.status] ?? ''}>
                        {business.status}
                      </Badge>
                    </TableCell>
                    <TableCell className='text-right'>
                      <Select
                        value={business.status}
                        onValueChange={(v) => handleStatusChange(business.id, v)}
                        disabled={isUpdating}
                      >
                        <SelectTrigger className='w-[140px]'>
                          <SelectValue />
                          {isUpdating && <Loader2 className='h-3 w-3 ml-2 animate-spin' />}
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value='active'>Active</SelectItem>
                          <SelectItem value='deactivated'>Deactivate</SelectItem>
                          <SelectItem value='banned'>Ban</SelectItem>
                        </SelectContent>
                      </Select>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
    </div>
  );
};
