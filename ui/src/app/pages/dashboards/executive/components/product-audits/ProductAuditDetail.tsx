import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { format } from 'date-fns';
import { FileText } from 'lucide-react';

const statusColors: Record<string, string> = {
  draft: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
  in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
  completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
  approved: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
  cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
};

type Props = {
  audit: any;
};

export const ProductAuditDetail = ({ audit }: Props) => (
  <div className='space-y-6'>
    <Card className='rounded-3xl border border-border/70 bg-card/80 shadow-sm'>
      <CardHeader>
        <div className='flex items-center justify-between'>
          <div>
            <CardTitle className='text-2xl'>{audit.audit_number}</CardTitle>
            <p className='text-sm text-muted-foreground mt-1'>
              {audit.branch?.name} &middot; {format(new Date(audit.audit_date), 'PP')}
            </p>
          </div>
          <Badge className={statusColors[audit.status]} variant='outline'>
            {audit.status?.replace('_', ' ')}
          </Badge>
        </div>
      </CardHeader>
      <CardContent className='grid grid-cols-2 md:grid-cols-4 gap-4'>
        <div>
          <p className='text-sm text-muted-foreground'>Performed By</p>
          <p className='font-medium'>{audit.performed_by?.name ?? '-'}</p>
        </div>
        <div>
          <p className='text-sm text-muted-foreground'>Approved By</p>
          <p className='font-medium'>{audit.approved_by?.name ?? '-'}</p>
        </div>
        <div>
          <p className='text-sm text-muted-foreground'>Approved At</p>
          <p className='font-medium'>{audit.approved_at ? format(new Date(audit.approved_at), 'PPp') : '-'}</p>
        </div>
        <div>
          <p className='text-sm text-muted-foreground'>Total Items</p>
          <p className='font-medium'>{audit.items?.length ?? 0}</p>
        </div>
      </CardContent>
      {audit.notes && (
        <CardContent className='border-t pt-4'>
          <p className='text-sm text-muted-foreground'>Notes</p>
          <p className='text-sm'>{audit.notes}</p>
        </CardContent>
      )}
    </Card>

    <Card className='rounded-3xl border border-border/70 bg-card/80 shadow-sm'>
      <CardHeader>
        <CardTitle className='text-lg flex items-center gap-2'>
          <FileText className='h-5 w-5' /> Audit Items
        </CardTitle>
      </CardHeader>
      <CardContent className='p-0'>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Product</TableHead>
              <TableHead>SKU</TableHead>
              <TableHead className='text-right'>System Qty</TableHead>
              <TableHead className='text-right'>Counted Qty</TableHead>
              <TableHead className='text-right'>Difference</TableHead>
              <TableHead className='text-right'>Adjustment</TableHead>
              <TableHead>Notes</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {(audit.items || []).map((item: any) => (
              <TableRow key={item.id}>
                <TableCell className='font-medium'>{item.product?.name ?? `Product #${item.product_id}`}</TableCell>
                <TableCell>{item.product?.sku ?? '-'}</TableCell>
                <TableCell className='text-right'>{item.system_quantity}</TableCell>
                <TableCell className='text-right'>{item.counted_quantity}</TableCell>
                <TableCell className={`text-right font-medium ${item.difference > 0 ? 'text-green-600' : item.difference < 0 ? 'text-red-600' : ''}`}>
                  {item.difference > 0 ? '+' : ''}{item.difference}
                </TableCell>
                <TableCell className='text-right'>{item.adjustment_quantity}</TableCell>
                <TableCell className='text-muted-foreground text-sm'>{item.notes || '-'}</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  </div>
);
