import { useState } from 'react';
import { useDeleteProductMutation, useProductsQuery } from '@/app/store/features/business/products/productsQuery';
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
  const [remove, { isLoading }] = useDeleteProductMutation();
  const [prodId, setProdId] = useState<string>('');
  console.log(products);

  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 10; // Adjust as needed

  if (loadProducts) return <PageLoadingState />;

  const totalPages = Math.ceil((products?.length || 0) / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const paginatedProducts = products?.products.slice(startIndex, startIndex + itemsPerPage);

  const tableHeaders = ['No', 'CID', 'Name', 'SKU', 'Price', 'CP', 'Quantity', 'RL', 'Status', 'Actions'];
  const handleDelete = async (id: string) => {
    setProdId(id);
    try {
      const res = await remove(id).unwrap();
      if (res) {
        toast.success(res.message);
      }
      setProdId('');
      return;
    } catch (error) {
      console.log('Error on del==>', error);
      toast.error('Failed to delete product');
    }
  };
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
              {/* <TableCell>{product.barcode}</TableCell> */}
              <TableCell>{Number(product.price)}</TableCell>
              <TableCell>{Number(product.cost_price)}</TableCell>
              <TableCell>{product.quantity}</TableCell>
              <TableCell>{product.reorder_level ?? '-'}</TableCell>
              <TableCell>{(product.status as any) === true ? 'Active' : 'Inactive'}</TableCell>
              {/* <TableCell>{product.description}</TableCell> */}
              {/* <TableCell>{product.category}</TableCell> */}
              <TableCell className='grid grid-cols-2 place-items-center gap-2'>
                <Link to={`/admin/products/${product.id}`} className='text-amber-400 h-full flex items-center'>
                  <Eye size={20} />
                </Link>
                <div className='h-6 flex items-center'>
                  {isLoading && prodId === product.id ? (
                    <Spinner className='size-4' />
                  ) : (
                    // <span>tt</span>
                    <Trash2
                      size={20}
                      className='text-red-400 cursor-pointer'
                      onClick={() => handleDelete(product.id)}
                    />
                  )}
                </div>
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
      <PaginationComponent currentPage={currentPage} totalPages={totalPages} onPageChange={setCurrentPage} />
    </div>
  );
};
