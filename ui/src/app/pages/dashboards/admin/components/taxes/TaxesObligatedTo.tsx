import { useGetAdminTaxesQuery, useDeleteAdminTaxMutation } from '@/app/store/features/business/admin/taxesQuery';
import { Card } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { Button } from '@/components/ui/button';
import { Trash2 } from 'lucide-react';
import { Link } from 'react-router-dom';

export const TaxesObligatedTo = () => {
  const { data, isLoading } = useGetAdminTaxesQuery();
  const [deleteTax, { isLoading: deleting }] = useDeleteAdminTaxMutation();
  const taxes =  data?.business_taxes?.data ?? [];
  console.log('business taxes==>', taxes);
  if (isLoading) return <PageLoadingState />;

  return (
    <Card>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Name</TableHead>
            <TableHead>Rate</TableHead>
            <TableHead>Type</TableHead>
            <TableHead>Status</TableHead>
            <TableHead>Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {taxes && taxes.length === 0 && (
            <TableRow>
              <TableCell colSpan={5}>No taxes found</TableCell>
            </TableRow>
          )}

          {taxes &&
            taxes.map((tax: any) => (
              <TableRow key={tax.id}>
                <TableCell>
                  <Link to={`/admin/obligated-taxes/${tax.id}`} className='text-primary underline'>
                    {tax.name}
                  </Link>
                </TableCell>
                <TableCell>{tax.rate}</TableCell>
                <TableCell>{tax.type}</TableCell>
                <TableCell>{tax.status}</TableCell>
                <TableCell>
                  <div className='flex gap-2'>
                    <Button
                      size='icon-sm'
                      variant='destructive'
                      disabled={deleting}
                      onClick={async () => {
                        const ok = window.confirm('Delete this tax?');
                        if (!ok) return;
                        try {
                          await deleteTax(String(tax.id)).unwrap();
                        } catch (err) {
                          console.error('delete tax error', err);
                        }
                      }}
                    >
                      <Trash2 className='h-4 w-4' />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            ))}
        </TableBody>
      </Table>
    </Card>
  );
};
