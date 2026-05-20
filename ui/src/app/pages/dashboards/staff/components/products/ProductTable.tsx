import { useState } from 'react';
import { useProductsQuery } from '@/app/store/features/business/products/productsQuery';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Eye, Trash2 } from 'lucide-react';
import { PaginationComponent } from '@/app/utils/Pagination';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { Link } from 'react-router-dom';
import { toast } from 'sonner';
import { Spinner } from '@/components/ui/spinner';

interface Product {
  id: string;
  category_id: string;
  name: string;
  sku: string;
  barcode: string;
  price: number;
  cost_price: number;
  quantity: number;
  reorder_level: number;
  status: string;
  description: string;
  category: string;
}

export const ProductTable = () => {
  const { data: products, isLoading: loadProducts } = useProductsQuery();
  const [prodId, setProdId] = useState<string>('');

  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 10;

  if (loadProducts) return <PageLoadingState />;

  const totalPages = Math.ceil((products?.length || 0) / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const paginatedProducts = products?.products.slice(startIndex, startIndex + itemsPerPage);

  const tableHeaders = ['No', 'CID', 'Name', 'SKU', 'Price', 'CP', 'Quantity', 'RL', 'Status', 'Actions'];

  return (
    <div>
      <Table>
        <TableHeader>
          <TableRow>
            {tableHeaders.map((header) => (
              <TableHead key={header}>{header}</TableHead>
            ))}
          </TableRow>
        </TableHeader>
        <TableBody>
          {paginatedProducts?.map((product: Product, i: number) => (
            <TableRow key={product.id}>
              <TableCell>{i + 1}</TableCell>
              <TableCell>{product.category_id}</TableCell>
              <TableCell>{product.name}</TableCell>
              <TableCell>{product.sku}</TableCell>
              <TableCell>{Number(product.price)}</TableCell>
              <TableCell>{Number(product.cost_price)}</TableCell>
              <TableCell>{product.quantity}</TableCell>
              <TableCell>{product.reorder_level ?? '-'}</TableCell>
              <TableCell>{(product.status as any) === true ? 'Active' : 'Inactive'}</TableCell>
              <TableCell className='grid grid-cols-2 place-items-center gap-2'>
                <Link to={`/staff/products/${product.id}`} className='text-amber-400 h-full flex items-center'>
                  <Eye size={20} />
                </Link>
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
      <PaginationComponent currentPage={currentPage} totalPages={totalPages} onPageChange={setCurrentPage} />
    </div>
  );
};
