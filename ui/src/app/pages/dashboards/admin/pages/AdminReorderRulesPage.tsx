import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useReorderRulesQuery, useDeleteReorderRuleMutation } from '@/app/store/features/business/admin/reorderRulesQuery';
import { PackageSearch, Trash2, CheckCircle, XCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { toast } from 'sonner';

export const AdminReorderRulesPage = () => {
  const { data, isLoading } = useReorderRulesQuery();
  const [deleteRule] = useDeleteReorderRuleMutation();
  const rules = data?.data || [];

  const handleDelete = async (id: string) => {
    try { await deleteRule(id).unwrap(); toast.success('Rule deleted'); }
    catch { toast.error('Failed to delete'); }
  };

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight flex items-center gap-3'>
          <PackageSearch className='h-8 w-8' /> Reorder Rules
        </h1>
        <p className='text-muted-foreground'>Automatic inventory reorder triggers</p>
      </div>
      <Card>
        <CardHeader><CardTitle>All Rules</CardTitle></CardHeader>
        <CardContent>
          {rules.length === 0 ? (
            <p className='text-muted-foreground text-sm text-center py-8'>No reorder rules configured.</p>
          ) : (
            <div className='space-y-2'>
              {rules.map((r: any) => (
                <div key={r.id} className='flex items-center justify-between p-3 bg-muted/50 rounded-lg'>
                  <div className='flex items-center gap-3'>
                    <span className='font-medium'>{r.business_branch_product?.name || 'Product'}</span>
                    <span className='text-sm text-muted-foreground'>Reorder at {r.reorder_quantity}</span>
                    {r.auto_approve ? (
                      <Badge variant='default'><CheckCircle className='h-3 w-3 mr-1' /> Auto</Badge>
                    ) : (
                      <Badge variant='secondary'><XCircle className='h-3 w-3 mr-1' /> Manual</Badge>
                    )}
                  </div>
                  <Button variant='ghost' size='icon' onClick={() => handleDelete(r.id)}><Trash2 className='h-4 w-4 text-destructive' /></Button>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
};
