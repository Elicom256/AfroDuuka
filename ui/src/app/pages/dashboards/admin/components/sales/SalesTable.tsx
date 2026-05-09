import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Edit, Trash2 } from 'lucide-react';
import { Spinner } from '@/components/ui/spinner';
import { useState } from 'react';

interface SalesTableProps {
  sales: any[];
  products: any[];
  onEdit: (sale: any) => void;
  onDelete: (id: number | string) => void;
}

export const SalesTable = ({ sales, products, onEdit, onDelete }: SalesTableProps) => {
  const [deletingId, setDeletingId] = useState<string | number>('');

  const productMap = new Map(products.map((product) => [String(product.id), product.name]));

  const handleDelete = async (id: number | string) => {
    setDeletingId(id);
    await onDelete(id);
    setDeletingId('');
  };

  return (
    <div>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Product</TableHead>
            <TableHead>Quantity</TableHead>
            <TableHead>Unit Price</TableHead>
            <TableHead>Total</TableHead>
            <TableHead>Status</TableHead>
            <TableHead>Date</TableHead>
            <TableHead>Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {sales.map((sale) => {
            const productName = productMap.get(String(sale.product_id ?? sale.product?.id)) || 'Unknown product';
            const quantity = Number(sale.quantity ?? 0);
            const unitPrice = Number(sale.unit_price ?? sale.price ?? 0);
            const total = quantity * unitPrice;
            return (
              <TableRow key={sale.id}>
                <TableCell>{productName}</TableCell>
                <TableCell>{quantity}</TableCell>
                <TableCell>KSH {unitPrice.toLocaleString()}</TableCell>
                <TableCell>KSH {total.toLocaleString()}</TableCell>
                <TableCell>{sale.status ?? 'N/A'}</TableCell>
                <TableCell>{sale.date ?? sale.created_at ?? '-'}</TableCell>
                <TableCell className='flex items-center gap-2'>
                  <Button variant='outline' size='sm' onClick={() => onEdit(sale)}>
                    <Edit className='mr-2 h-3.5 w-3.5' />
                    Edit
                  </Button>
                  <Button variant='ghost' size='sm' onClick={() => handleDelete(sale.id)}>
                    {deletingId === sale.id ? <Spinner className='h-4 w-4' /> : <Trash2 className='h-3.5 w-3.5' />}
                  </Button>
                </TableCell>
              </TableRow>
            );
          })}
        </TableBody>
      </Table>
    </div>
  );
};
