import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { usePrintersQuery, useDeletePrinterMutation } from '@/app/store/features/business/admin/printersQuery';
import { Printer, Trash2, Wifi, Bluetooth, Cable } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { toast } from 'sonner';

const typeIcons: Record<string, any> = { bluetooth: Bluetooth, usb: Cable, network: Wifi };

export const AdminPrintersPage = () => {
  const { data, isLoading } = usePrintersQuery();
  const [deletePrinter] = useDeletePrinterMutation();
  const printers = data?.data || [];

  const handleDelete = async (id: string) => {
    try { await deletePrinter(id).unwrap(); toast.success('Printer removed'); }
    catch { toast.error('Failed to delete'); }
  };

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight flex items-center gap-3'>
          <Printer className='h-8 w-8' /> Printers
        </h1>
        <p className='text-muted-foreground'>Manage thermal receipt printers per branch</p>
      </div>
      <Card>
        <CardHeader><CardTitle>All Printers</CardTitle></CardHeader>
        <CardContent>
          {printers.length === 0 ? (
            <p className='text-muted-foreground text-sm text-center py-8'>No printers configured.</p>
          ) : (
            <div className='space-y-2'>
              {printers.map((p: any) => {
                const TypeIcon = typeIcons[p.type] || Printer;
                return (
                  <div key={p.id} className='flex items-center justify-between p-3 bg-muted/50 rounded-lg'>
                    <div className='flex items-center gap-3'>
                      <TypeIcon className='h-5 w-5 text-muted-foreground' />
                      <div>
                        <span className='font-medium'>{p.name}</span>
                        <span className='text-muted-foreground text-sm ml-2'>({p.type})</span>
                        {p.is_default && <Badge variant='secondary' className='ml-2'>Default</Badge>}
                      </div>
                    </div>
                    <Button variant='ghost' size='icon' onClick={() => handleDelete(p.id)}><Trash2 className='h-4 w-4 text-destructive' /></Button>
                  </div>
                );
              })}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
};
