import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Edit, Trash2 } from 'lucide-react';
import { Spinner } from '@/components/ui/spinner';
import { useState } from 'react';

interface PurchasesTableProps {
  purchases: any[];
  products: any[];
  onEdit: (purchase: any) => void;
  onDelete: (id: number | string) => void;
}

export const PurchasesTable = ({ purchases, products, onEdit, onDelete }: PurchasesTableProps) => {
  const [deletingId, setDeletingId] = useState<string | number>('');
  const productMap = new Map(products.map((product) => [String(product.id), product.name]));

  const handleDelete = async (id: number | string) => {
    setDeletingId(id);
    await onDelete(id);
    setDeletingId('');
  };
  const headers = ['Product', 'Quantity', 'Unit Cost', 'Total', 'Status', 'Date', 'Actions'];

  return (
    <div>
      <Table>
        <TableHeader>
          <TableRow>
            {headers.map((header) => (
              <TableHead key={header}>{header}</TableHead>
            ))}
          </TableRow>
        </TableHeader>
        <TableBody>
          {purchases.length > 0 ? (
            purchases.map((purchase) => {
              const productName =
                productMap.get(String(purchase.product_id ?? purchase.product?.id)) || 'Unknown product';
              const quantity = Number(purchase.quantity ?? 0);
              const unitCost = Number(purchase.unit_cost ?? purchase.price ?? 0);
              const total = quantity * unitCost;
              return (
                <TableRow key={purchase.id}>
                  <TableCell>{productName}</TableCell>
                  <TableCell>{quantity}</TableCell>
                  <TableCell>KSH {unitCost.toLocaleString()}</TableCell>
                  <TableCell>KSH {total.toLocaleString()}</TableCell>
                  <TableCell>{purchase.status ?? 'N/A'}</TableCell>
                  <TableCell>{purchase.date ?? purchase.created_at ?? '-'}</TableCell>
                  <TableCell className='flex items-center gap-2'>
                    <Button variant='outline' size='sm' onClick={() => onEdit(purchase)}>
                      <Edit className='mr-2 h-3.5 w-3.5' />
                      Edit
                    </Button>
                    <Button variant='ghost' size='sm' onClick={() => handleDelete(purchase.id)}>
                      {deletingId === purchase.id ? (
                        <Spinner className='h-4 w-4' />
                      ) : (
                        <Trash2 className='h-3.5 w-3.5' />
                      )}
                    </Button>
                  </TableCell>
                </TableRow>
              );
            })
          ) : (
            <TableRow>
              <TableCell colSpan={7}>
                <p className='text-center py-3 hover:underline'>You haven't recorded any purchase yet!</p>
              </TableCell>
            </TableRow>
          )}
        </TableBody>
      </Table>
    </div>
  );
};
