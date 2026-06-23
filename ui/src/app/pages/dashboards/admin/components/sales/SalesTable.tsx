import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { format } from 'date-fns';
import { useNavigate } from 'react-router-dom';
import { useCurrency } from '@/app/hooks/useCurrency';

interface SalesTableProps {
  sales: any[];
  products: any[];
}

export const SalesTable = ({ sales }: SalesTableProps) => { 
  const { currency } = useCurrency();
  const navigate = useNavigate();
  console.log('sale==>', sales);
  return (
    <div>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>No</TableHead>
            {/* <TableHead>Qty</TableHead> */}
            <TableHead>Status</TableHead>
            <TableHead>Date</TableHead>
            <TableHead>Note</TableHead>
            <TableHead> Amount({currency})</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {sales.map((sale, i) => {
            const totalPrice = Number(sale.total_amount ?? sale.total_amount ?? 0);
            return (
              <TableRow key={sale.id} onClick={() => navigate(`/admin/sales/${sale.id}`)} className='cursor-pointer'>
                <TableHead>{i + 1}</TableHead>
                {/* <TableHead>
                  {sale.sale_items.reduce((acc: number, val: any) => acc + Number(val.quantity), 0)}
                </TableHead> */}
                <TableCell>{sale.status ?? 'N/A'}</TableCell>
                <TableCell>{sale.date ?? format(new Date(sale.created_at), 'PPP') ?? '-'}</TableCell>
                <TableCell>{sale.note?.slice(0, 10)}</TableCell>
                <TableCell> {totalPrice.toLocaleString()}</TableCell>

                {/* <TableCell className='flex items-center gap-2'>
                  <Button variant='outline' size='sm' onClick={() => onEdit(sale)}>
                    <Edit className='mr-2 h-3.5 w-3.5' />
                    Edit
                  </Button>
                  <Button variant='ghost' size='sm' onClick={() => handleDelete(sale.id)}>
                    {deletingId === sale.id ? <Spinner className='h-4 w-4' /> : <Trash2 className='h-3.5 w-3.5' />}
                  </Button>
                </TableCell> */}
              </TableRow>
            );
          })}
        </TableBody>
      </Table>
    </div>
  );
};
