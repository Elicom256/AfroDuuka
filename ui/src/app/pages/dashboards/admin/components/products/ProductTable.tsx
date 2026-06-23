import { useState } from 'react';
import { useProductsQuery } from '@/app/store/features/business/products/productsQuery';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Eye, Trash2 } from 'lucide-react';
import { PaginationComponent } from '@/app/utils/Pagination';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { Link, useNavigate } from 'react-router-dom';
import { toast } from 'sonner';
import { Spinner } from '@/components/ui/spinner';
import {
  useBranchProductsQuery,
  useDeleteBranchProductMutation,
} from '@/app/store/features/branch/products/branchProductsQuery';

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
  markup_percentage: number;
  product: {
    name: string;
    sku: string;
  };
}

export const ProductTable = () => {
  const { data: branchProducts, isLoading: loadBranchProducts } = useBranchProductsQuery();
  const navigate = useNavigate();
  console.log('products==>', branchProducts);
  const bproducts = branchProducts?.products;
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 10;
  console.log('bproducts==>', bproducts);

  if (loadBranchProducts) return <PageLoadingState />;

  const totalPages = Math.ceil((bproducts?.length || 0) / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const paginatedProducts = bproducts?.slice(startIndex, startIndex + itemsPerPage);

  const tableHeaders = ['No', 'Name', 'SKU', 'CP', 'MP (%)', 'Price', 'Quantity', 'RL', 'Status'];

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
            <TableRow
              key={product.id}
              onClick={() => navigate(`/admin/products/${product.id}`)}
              className='cursor-pointer'
            >
              <TableCell>{startIndex + i + 1}</TableCell>
              {/* <TableCell>{product.category_id}</TableCell> */}
              <TableCell>{product.name}</TableCell>
              <TableCell>{product?.product?.sku ?? null}</TableCell>
              {/* <TableCell>{product.barcode}</TableCell> */}
              <TableCell>{Number(product.cost_price)}</TableCell>
              <TableCell>{(product.markup_percentage * 100).toFixed(2)}</TableCell>

              {/* <TableCell>{Number(product.markup_percentage) * 100}</TableCell> */}
              <TableCell>{Number(product.price)}</TableCell>

              <TableCell>{product.quantity}</TableCell>
              <TableCell>{product.reorder_level ?? '-'}</TableCell>
              <TableCell>{(product.status as any) === true ? 'Active' : 'Inactive'}</TableCell>
              {/* <TableCell>{product.description}</TableCell> */}
              {/* <TableCell>{product.category}</TableCell> */}
              {/* <TableCell className='grid grid-cols-2 place-items-center gap-2'>
               
              </TableCell> */}
            </TableRow>
          ))}
        </TableBody>
      </Table>
      <PaginationComponent currentPage={currentPage} totalPages={totalPages} onPageChange={setCurrentPage} />
    </div>
  );
};
