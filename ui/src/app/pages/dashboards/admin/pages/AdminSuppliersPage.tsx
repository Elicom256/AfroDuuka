// pages/AdminSuppliersPage.tsx
import { useState } from 'react';
import { useSuppliersQuery } from '@/app/store/features/business/suppliers/supplierQuery';
import { Button } from '@/components/ui/button';
import { Edit, Plus } from 'lucide-react';
import { SupplierFormDialog } from '../components/suppliers/SupplierFormDialog';
import { useNavigate } from 'react-router-dom';

export const AdminSuppliersPage = () => {
  const navigate = useNavigate();
  const { data, isLoading: fetchingSuppliers } = useSuppliersQuery();
  const suppliers = data?.suppliers ?? [];

  const [dialogOpen, setDialogOpen] = useState(false);
  const [selectedSupplier, setSelectedSupplier] = useState<any>(null);

  const handleAddNew = () => {
    setSelectedSupplier(null);
    setDialogOpen(true);
  };

  const handleEdit = (supplier: any) => {
    setSelectedSupplier(supplier);
    setDialogOpen(true);
  };

  return (
    <div className='space-y-6'>
      <div className='flex justify-between items-center'>
        <h1 className='text-3xl font-bold'>Suppliers</h1>
        <Button onClick={handleAddNew}>
          <Plus className='mr-2 h-4 w-4' />
          Add Supplier
        </Button>
      </div>

      <div className='grid gap-4'>
        {fetchingSuppliers ? (
          <p>Loading suppliers...</p>
        ) : suppliers.length === 0 ? (
          <p>No suppliers found.</p>
        ) : (
          suppliers.map((supplier: any) => (
            <div
              key={supplier.id}
              className='border p-4 rounded-lg flex justify-between items-center hover:bg-white/20'
              onClick={() => navigate(`/admin/suppliers/${supplier?.id}`)}
            >
              <div>
                <p className='font-medium'>
                  {supplier.user.firstname} {supplier.user.lastname}
                </p>
                {supplier.company_name && <p className='text-sm text-gray-500 font-medium'>{supplier.company_name}</p>}
                <p className='text-sm text-gray-400'>{supplier.user.email}</p>
              </div>
              <Button variant='outline' size='sm' onClick={() => handleEdit(supplier)}>
                <Edit />
                <span>Edit</span>
              </Button>
            </div>
          ))
        )}
      </div>

      <SupplierFormDialog
        open={dialogOpen}
        selectedSupplier={selectedSupplier}
        setDialogOpen={setDialogOpen}
        onOpenChange={setDialogOpen}
      />
    </div>
  );
};
