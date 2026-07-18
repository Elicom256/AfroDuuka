import { useEffect, useMemo, useState } from 'react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { PaginationComponent } from '@/app/utils/Pagination';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { Package2, PencilLine, Trash2 } from 'lucide-react';
import { toast } from 'sonner';
import { useProductsQuery, useDeleteProductMutation } from '@/app/store/features/branch/products/branchProductsQuery';
import { EditProduct } from './EditProduct';
import { useNavigate } from 'react-router-dom';

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
  minimum_stock: number;
  status: string | boolean;
  description: string;
  markup_percentage: number;
  product_category?: { name: string };
  category_name?: string;
}

const getCategoryName = (product: Product) => {
  return product.category_name || product.product_category?.name || 'Uncategorized';
};

export const ProductTable = () => {
  const { data: branchProducts, isLoading: loadBranchProducts, error } = useProductsQuery();
  const [remove, { isLoading: isDeleting }] = useDeleteProductMutation();
  const [currentPage, setCurrentPage] = useState(1);
  const [selectedProduct, setSelectedProduct] = useState<Product | null>(null);
  const [isEditOpen, setIsEditOpen] = useState(false);
  const itemsPerPage = 8;
  const navigate = useNavigate();

  const products = useMemo(() => branchProducts?.products ?? [], [branchProducts]);
  const totalPages = Math.max(1, Math.ceil(products.length / itemsPerPage));
  const startIndex = (currentPage - 1) * itemsPerPage;
  console.log('branchProducts ==>', branchProducts ?? error);

  useEffect(() => {
    if (currentPage > totalPages) {
      setCurrentPage(totalPages);
    }
  }, [currentPage, totalPages]);

  const paginatedProducts = useMemo(
    () => products.slice(startIndex, startIndex + itemsPerPage),
    [products, startIndex],
  );

  const handleDelete = async (id: string) => {
    if (!window.confirm('Delete this product?')) return;

    try {
      const res = await remove(id).unwrap();
      toast.success(res?.message || 'Product deleted successfully');
    } catch (error) {
      console.error('Delete error:', error);
      toast.error('Failed to delete product');
    }
  };

  const openEditDialog = (product: Product) => {
    setSelectedProduct(product);
    setIsEditOpen(true);
  };

  if (loadBranchProducts) return <PageLoadingState />;
  console.log('products==>', products);
  return (
    <div className='space-y-4'>
      <Card className='border-border/60'>
        <CardHeader className='flex flex-row items-center justify-between gap-4'>
          <div>
            <CardTitle className='text-lg'>Inventory products</CardTitle>
            <p className='text-sm text-muted-foreground'>
              Review stock, pricing, and category linkage across your branches.
            </p>
          </div>
          <Badge variant='secondary'>{products.length} products</Badge>
        </CardHeader>
        <CardContent className='p-0'>
          {products.length === 0 ? (
            <div className='flex flex-col items-center justify-center gap-3 px-6 py-12 text-center'>
              <div className='rounded-full bg-muted p-3'>
                <Package2 className='h-5 w-5 text-muted-foreground' />
              </div>
              <div>
                <p className='font-medium'>No products yet</p>
                <p className='text-sm text-muted-foreground'>Add a product to start tracking inventory and pricing.</p>
              </div>
            </div>
          ) : (
            <div className='overflow-x-auto'>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>ID</TableHead>
                    <TableHead></TableHead>
                    <TableHead>Name</TableHead>
                    <TableHead>SKU</TableHead>
                    <TableHead>Barcode</TableHead>
                    <TableHead>Price</TableHead>
                    <TableHead>Cost</TableHead>
                    <TableHead>Quantity</TableHead>
                    <TableHead>Min Stock</TableHead>
                    <TableHead>Status</TableHead>
                    {/* <TableHead>Description</TableHead> */}
                    {/* <TableHead>Category</TableHead> */}
                    <TableHead className='text-right'>Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {paginatedProducts.map((product: Product) => (
                    <TableRow key={product.id} onClick={() => navigate(`/admin/products/${product.id}`)}>
                      <TableCell>{product.id}</TableCell>
                      <TableCell className='text-xl'>{product.emoji || ''}</TableCell>
                      <TableCell className='font-medium'>{product.name}</TableCell>
                      <TableCell>{product.sku || '-'}</TableCell>
                      <TableCell>{product.barcode || '-'}</TableCell>
                      <TableCell>{Number(product.price).toFixed(2)}</TableCell>
                      <TableCell>{Number(product.cost_price).toFixed(2)}</TableCell>
                      <TableCell>{product.quantity}</TableCell>
                      <TableCell>{product.minimum_stock ?? product.reorder_level ?? '-'}</TableCell>
                      <TableCell>
                        <Badge
                          variant={product.status === true || product.status === 'active' ? 'default' : 'secondary'}
                        >
                          {product.status === true || product.status === 'active' ? 'Active' : 'Inactive'}
                        </Badge>
                      </TableCell>
                      {/* <TableCell className='max-w-55 truncate'>
                        {product.description || 'No description'}
                      </TableCell> */}
                      {/* <TableCell>{getCategoryName(product)}</TableCell> */}
                      <TableCell className='text-right'>
                        <div className='flex justify-end gap-2'>
                          <Button variant='outline' size='icon' onClick={() => openEditDialog(product)}>
                            <PencilLine className='h-4 w-4' />
                          </Button>
                          {/* <Button
                            variant='outline'
                            size='icon'
                            className='text-destructive hover:text-destructive'
                            onClick={() => handleDelete(product.id)}
                            disabled={isDeleting}
                          >
                            <Trash2 className='h-4 w-4' />
                          </Button> */}
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          )}
        </CardContent>
      </Card>

      <PaginationComponent currentPage={currentPage} totalPages={totalPages} onPageChange={setCurrentPage} />

      {selectedProduct && <EditProduct open={isEditOpen} onOpenChange={setIsEditOpen} product={selectedProduct} />}
    </div>
  );
};
