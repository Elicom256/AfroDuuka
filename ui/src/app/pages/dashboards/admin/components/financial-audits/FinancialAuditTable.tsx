import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Eye, FileText, ThumbsUp, XCircle, Edit, Trash2 } from 'lucide-react';
import { Link } from 'react-router-dom';
import { PaginationComponent } from '@/app/utils/Pagination';
import { format } from 'date-fns';
import { useCurrency } from '@/app/hooks/useCurrency';

const statusColors: Record<string, string> = {
  draft: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
  in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
  completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
  approved: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
  cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
};

type Props = {
  audits: any[];
  currentPage: number;
  totalPages: number;
  onPageChange: (page: number) => void;
  onApprove: (item: any) => void;
  onEdit: (item: any) => void;
  onDelete: (id: number) => void;
  onCancel: (id: number) => void;
};

export const FinancialAuditTable = ({
  audits, currentPage, totalPages, onPageChange,
  onApprove, onEdit, onDelete, onCancel,
}: Props) => {
  const { currencySymbol } = useCurrency();
  return (
    <Card className='rounded-3xl border border-border/70 bg-card/80 shadow-sm'>
      <CardContent className='p-0'>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Audit #</TableHead>
              <TableHead>Branch</TableHead>
              <TableHead>Date</TableHead>
              <TableHead className='text-right'>Expected</TableHead>
              <TableHead className='text-right'>Actual</TableHead>
              <TableHead className='text-right'>Difference</TableHead>
              <TableHead>Status</TableHead>
              <TableHead className='text-right'>Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {audits.length === 0 ? (
              <TableRow>
                <TableCell colSpan={8} className='py-10 text-center text-muted-foreground'>
                  No financial audits found.
                </TableCell>
              </TableRow>
            ) : (
              audits.map((audit: any) => (
                <TableRow key={audit.id}>
                  <TableCell className='font-medium'>{audit.audit_number}</TableCell>
                  <TableCell>{audit.branch?.name ?? '-'}</TableCell>
                  <TableCell>{format(new Date(audit.audit_date), 'PP')}</TableCell>
                  <TableCell className='text-right'>{currencySymbol}{Number(audit.expected_balance).toLocaleString()}</TableCell>
                  <TableCell className='text-right'>{currencySymbol}{Number(audit.actual_balance).toLocaleString()}</TableCell>
                  <TableCell className={`text-right font-medium ${audit.difference > 0 ? 'text-green-600' : audit.difference < 0 ? 'text-red-600' : ''}`}>
                    {audit.difference > 0 ? '+' : ''}{currencySymbol}{Number(audit.difference).toLocaleString()}
                  </TableCell>
                  <TableCell>
                    <Badge className={statusColors[audit.status]} variant='outline'>
                      {audit.status?.replace('_', ' ')}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div className='flex justify-end gap-1'>
                      <Button variant='ghost' size='icon' asChild>
                        <Link to={`/admin/financial-audits/${audit.id}`}><Eye className='h-4 w-4' /></Link>
                      </Button>
                      {audit.status === 'completed' && (
                        <Button variant='ghost' size='icon' onClick={() => onApprove(audit)}>
                          <ThumbsUp className='h-4 w-4 text-emerald-500' />
                        </Button>
                      )}
                      {(audit.status === 'draft' || audit.status === 'in_progress') && (
                        <Button variant='ghost' size='icon' onClick={() => onEdit(audit)}>
                          <Edit className='h-4 w-4' />
                        </Button>
                      )}
                      {(audit.status === 'draft' || audit.status === 'in_progress' || audit.status === 'completed') && (
                        <Button variant='ghost' size='icon' onClick={() => onCancel(audit.id)}>
                          <XCircle className='h-4 w-4 text-orange-500' />
                        </Button>
                      )}
                      {(audit.status === 'draft' || audit.status === 'cancelled') && (
                        <Button variant='ghost' size='icon' onClick={() => onDelete(audit.id)}>
                          <Trash2 className='h-4 w-4 text-red-500' />
                        </Button>
                      )}
                      {(audit.status === 'approved' || audit.status === 'completed') && (
                        <Button variant='ghost' size='icon' asChild>
                          <Link to={`/admin/financial-audits/${audit.id}/report`}><FileText className='h-4 w-4' /></Link>
                        </Button>
                      )}
                    </div>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
        {totalPages > 1 && (
          <div className='py-4'>
            <PaginationComponent currentPage={currentPage} totalPages={totalPages} onPageChange={onPageChange} />
          </div>
        )}
      </CardContent>
    </Card>
  );
};
