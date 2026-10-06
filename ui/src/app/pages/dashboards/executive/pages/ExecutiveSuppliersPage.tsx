// pages/ExecutiveSuppliersPage.tsx
import { useState } from 'react';
import { useSuppliersQuery } from '@/app/store/features/business/suppliers/supplierQuery';
import { Button } from '@/components/ui/button';
import { Edit, Plus } from 'lucide-react';
import { SupplierFormDialog } from '../components/suppliers/SupplierFormDialog';
import { useNavigate } from 'react-router-dom';
import { ExportButton } from '@/app/components/ExportButton';
import { useRolePermissions } from '@/lib/useRolePermissions';
import { QueryEmptyState, QueryErrorState } from '@/app/components/QueryErrorState';

export const ExecutiveSuppliersPage = () => {
  const navigate = useNavigate();
  const { data, isLoading: fetchingSuppliers, isFetching, isError, refetch } = useSuppliersQuery();
  const suppliers = data?.suppliers ?? [];
  // Suppliers are business-level records. A BranchManager is routed here too, to
  // read the counterparties their purchases reference, but authoring them is the
  // Executive's alone — so the write controls are hidden rather than left to 403.
  const { canManageSuppliers } = useRolePermissions();

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
        <div className='flex gap-2'>
          <ExportButton type='suppliers' label='Export' />
          {canManageSuppliers && (
            <Button onClick={handleAddNew}>
              <Plus className='mr-2 h-4 w-4' />
              Add Supplier
            </Button>
          )}
        </div>
      </div>

      <div className='grid gap-4'>
        {isError && !data ? (
          <QueryErrorState
            title='Unable to load suppliers'
            description='The supplier directory is unavailable because the request failed.'
            onRetry={refetch}
            retrying={isFetching}
          />
        ) : fetchingSuppliers ? (
          <p>Loading suppliers...</p>
        ) : (
          <>
            {isError && (
              <QueryErrorState
                title='Supplier list may be out of date'
                description='The latest refresh failed. The last loaded supplier list is still shown.'
                onRetry={refetch}
                retrying={isFetching}
              />
            )}
            {suppliers.length === 0 ? (
              <QueryEmptyState
                title='No suppliers yet'
                description='Suppliers will appear here after they are added.'
              />
            ) : (
              suppliers.map((supplier: any) => (
                <div
                  key={supplier.id}
                  className='border p-4 rounded-lg flex justify-between items-center hover:bg-white/20'
                  onClick={() => navigate(`/dashboard/suppliers/${supplier?.id}`)}
                >
                  <div>
                    <p className='font-medium'>
                      {supplier.user.firstname} {supplier.user.lastname}
                    </p>
                    {supplier.company_name && (
                      <p className='text-sm text-muted-foreground font-medium'>{supplier.company_name}</p>
                    )}
                    <p className='text-sm text-muted-foreground'>{supplier.user.email}</p>
                  </div>
                  {canManageSuppliers && (
                    <Button
                      variant='outline'
                      size='sm'
                      onClick={(e) => {
                        e.stopPropagation();
                        handleEdit(supplier);
                      }}
                    >
                      <Edit />
                      <span>Edit</span>
                    </Button>
                  )}
                </div>
              ))
            )}
          </>
        )}
      </div>

      {canManageSuppliers && (
        <SupplierFormDialog
          open={dialogOpen}
          selectedSupplier={selectedSupplier}
          setDialogOpen={setDialogOpen}
          onOpenChange={setDialogOpen}
        />
      )}
    </div>
  );
};
