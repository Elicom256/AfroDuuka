import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useReportExportsQuery } from '@/app/store/features/business/admin/loyaltyQuery';
import { FileDown, CheckCircle, Clock, XCircle, Loader2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { PageLoadingState } from '@/utils/PageLoadingState';

const statusIcons: Record<string, any> = {
  pending: Clock,
  processing: Loader2,
  completed: CheckCircle,
  failed: XCircle,
};

export const AdminReportExportsPage = () => {
  const { data, isLoading } = useReportExportsQuery();
  const exports = data?.data || [];

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight flex items-center gap-3'>
          <FileDown className='h-8 w-8' /> Report Exports
        </h1>
        <p className='text-muted-foreground'>Download generated reports (CSV, XLSX, PDF)</p>
      </div>
      <Card>
        <CardHeader><CardTitle>Generated Exports</CardTitle></CardHeader>
        <CardContent>
          {exports.length === 0 ? (
            <p className='text-muted-foreground text-sm text-center py-8'>No exports generated yet.</p>
          ) : (
            <div className='space-y-2'>
              {exports.map((e: any) => {
                const StatusIcon = statusIcons[e.status] || Clock;
                return (
                  <div key={e.id} className='flex items-center justify-between p-3 bg-muted/50 rounded-lg'>
                    <div className='flex items-center gap-3'>
                      <StatusIcon className={`h-5 w-5 ${e.status === 'processing' ? 'animate-spin' : ''} text-muted-foreground`} />
                      <span className='font-medium capitalize'>{e.report_type.replace(/_/g, ' ')}</span>
                      <Badge variant='outline' className='uppercase'>{e.format}</Badge>
                      <span className='text-xs text-muted-foreground capitalize'>{e.status}</span>
                    </div>
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
