import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { usePrintersQuery, useCreatePrinterMutation, useDeletePrinterMutation } from '@/app/store/features/business/admin/printersQuery';
import { useBranchesQuery } from '@/app/store/features/business/branches/branchesQuery';
import { Printer } from 'lucide-react';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { AddPrinter } from '../components/printers/AddPrinter';
import { PrintersTable } from '../components/printers/PrintersTable';

export const AdminPrintersPage = () => {
  const { data, isLoading } = usePrintersQuery();
  const [createPrinter] = useCreatePrinterMutation();
  const [deletePrinter] = useDeletePrinterMutation();
  const { data: branchesData } = useBranchesQuery();
  const printers = data?.data || [];
  const branches = branchesData?.data || [];

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <div className='flex items-center justify-between'>
        <div>
          <h1 className='text-3xl font-bold tracking-tight flex items-center gap-3'>
            <Printer className='h-8 w-8' /> Printers
          </h1>
          <p className='text-muted-foreground'>Manage thermal receipt printers per branch</p>
        </div>
        <AddPrinter createPrinter={createPrinter} branches={branches} />
      </div>
      <Card>
        <CardHeader><CardTitle>All Printers ({printers.length})</CardTitle></CardHeader>
        <CardContent>
          {printers.length === 0 ? (
            <p className='text-muted-foreground text-sm text-center py-8'>No printers configured.</p>
          ) : (
            <PrintersTable printers={printers} onDelete={deletePrinter} />
          )}
        </CardContent>
      </Card>
    </div>
  );
};
