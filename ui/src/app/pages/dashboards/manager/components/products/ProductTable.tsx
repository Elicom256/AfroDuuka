import { useState } from 'react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Eye, Trash2 } from 'lucide-react';
import { PaginationComponent } from '@/app/utils/Pagination';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { Link } from 'react-router-dom';
import { toast } from 'sonner';
import { Spinner } from '@/components/ui/spinner';
import { useProductsQuery, useDeleteProductMutation } from '@/app/store/features/branch/products/branchProductsQuery';


interface Product {
  id: string;
  product_category_id: string;
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
  const { data: branchProds, isLoading: loadProducts } = useProductsQuery(void 0);
  const [remove, { isLoading }] = useDeleteProductMutation();
  const [prodId, setProdId] = useState<string>('');
  console.log('branchProds available==>', branchProds);

  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 10;

  if (loadProducts) return <PageLoadingState />;

  const totalPages = Math.ceil((branchProds?.length || 0) / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const paginatedProducts = branchProds?.products.slice(startIndex, startIndex + itemsPerPage);

  const tableHeaders = ['No', 'Name', 'SKU', 'Price', 'CP', 'Quantity', 'RL', 'Status', 'Actions'];
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
      {/* <TestProd /> */}
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
              {/* <TableCell>{product.category_id}</TableCell> */}
              <TableCell>{product.name}</TableCell>
              <TableCell>{product.sku}</TableCell>
              <TableCell>{Number(product.price)}</TableCell>
              <TableCell>{Number(product.cost_price)}</TableCell>
              <TableCell>{product.quantity}</TableCell>
              <TableCell>{product.reorder_level ?? '-'}</TableCell>
              <TableCell>{(product.status as any) === true ? 'Active' : 'Inactive'}</TableCell>
              <TableCell className='grid grid-cols-2 place-items-center gap-2'>
                <Link to={`/manager/products/${product.id}`} className='text-amber-400 h-full flex items-center'>
                  <Eye size={20} />
                </Link>
                <div className='h-6 flex items-center'>
                  {isLoading && prodId === product.id ? (
                    <Spinner className='size-4' />
                  ) : (
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
