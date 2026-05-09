import React, { useState } from 'react';
import { useProductsQuery } from '@/app/store/features/business/products/productsQuery';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Edit, Trash2 } from 'lucide-react';
import { PaginationComponent } from '@/app/utils/Pagination';
import { PageLoadingState } from '@/utils/PageLoadingState';

interface Product {
  id: string;
  category_id: string;
  name: string;
  sku: string;
  barcode: string;
  price: number;
  cost_price: number;
  quantity: number;
  minimum_stock: number;
  status: string;
  description: string;
  category: string;
}

export const ProductTable: React.FC = () => {
  const { data: products, isLoading: loadProducts } = useProductsQuery();
  console.log(products);

  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 10; // Adjust as needed

  if (loadProducts) return <PageLoadingState />;

  const totalPages = Math.ceil((products?.length || 0) / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const paginatedProducts = products?.products.slice(startIndex, startIndex + itemsPerPage);

  const tableHeaders = [
    'Category ID',
    'Name',
    'SKU',
    'Price (UGX)',
    'Cost Price (UGX)',
    'Quantity',
    'Min Stock',
    'Status',
    'Actions',
  ];

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
          {paginatedProducts?.map((product: Product) => (
            <TableRow key={product.id}>
              <TableCell>{product.category_id}</TableCell>
              <TableCell>{product.name}</TableCell>
              <TableCell>{product.sku}</TableCell>
              {/* <TableCell>{product.barcode}</TableCell> */}
              <TableCell>{product.price}</TableCell>
              <TableCell>{product.cost_price}</TableCell>
              <TableCell>{product.quantity}</TableCell>
              <TableCell>{product.minimum_stock}</TableCell>
              <TableCell>{(product.status as any) === true ? 'Active' : 'Inactive'}</TableCell>
              {/* <TableCell>{product.description}</TableCell> */}
              {/* <TableCell>{product.category}</TableCell> */}
              <TableCell>
                <Button variant='ghost' size='sm' className='text-green-400'>
                  <Edit className='h-4 w-4' />
                </Button>
                <Button variant='ghost' size='sm' className='text-red-400'>
                  <Trash2 className='h-4 w-4' />
                </Button>
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
      <PaginationComponent currentPage={currentPage} totalPages={totalPages} onPageChange={setCurrentPage} />
    </div>
  );
};
