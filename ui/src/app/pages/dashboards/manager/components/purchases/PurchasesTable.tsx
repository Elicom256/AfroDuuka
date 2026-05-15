import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { format } from 'date-fns';
import { useNavigate } from 'react-router-dom';

interface PurchasesTableProps {
  purchases: any[];
  products: any[];
}

export const PurchasesTable = ({ purchases }: PurchasesTableProps) => {
  const navigate = useNavigate();
  console.log('purchases==>', purchases);

  return (
    <div>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>No</TableHead>
            <TableHead>Supplier ID</TableHead>
            <TableHead>Date</TableHead>
            <TableHead>Note</TableHead>
            <TableHead>Amount (UGX)</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {purchases.length > 0 ? (
            purchases.map((purchase, i) => {
              const totalAmount = Number(purchase.total_amount ?? 0);
              return (
                <TableRow
                  key={purchase.id}
                  onClick={() => navigate(`/manager/purchases/${purchase.id}`)}
                  className='cursor-pointer'
                >
                  <TableCell>{i + 1}</TableCell>
                  <TableCell>{purchase.supplier_id ?? 'N/A'}</TableCell>
                  <TableCell>{purchase.date ?? format(new Date(purchase.created_at), 'PPP') ?? '-'}</TableCell>
                  <TableCell>{purchase.note ? purchase.note.slice(0, 10) : '-'}</TableCell>
                  <TableCell>{totalAmount.toLocaleString()}</TableCell>
                </TableRow>
              );
            })
          ) : (
            <TableRow>
              <TableCell colSpan={5}>
                <p className='text-center py-3 hover:underline'>You haven't recorded any purchase yet!</p>
              </TableCell>
            </TableRow>
          )}
        </TableBody>
      </Table>
    </div>
  );
};
