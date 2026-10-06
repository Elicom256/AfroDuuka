// pages/ExecutiveCustomersPage.tsx
import { useState } from 'react';
import { useCustomersQuery } from '@/app/store/features/business/customers/customersQuery';
import { Button } from '@/components/ui/button';
import { Edit, Plus } from 'lucide-react';
import { CustomerFormDialog } from '../components/customers/CustomerFormDialog';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { useNavigate } from 'react-router-dom';
import { ExportButton } from '@/app/components/ExportButton';
import { QueryEmptyState, QueryErrorState } from '@/app/components/QueryErrorState';

export const ExecutiveCustomersPage = () => {
  const navigate = useNavigate();
  const { data, isLoading: fetchingCustomers, isFetching, isError, refetch } = useCustomersQuery();
  const customers = data?.customers || [];
  const [dialogOpen, setDialogOpen] = useState(false);
  const [selectedCustomer, setSelectedCustomer] = useState<any>(null);

  const handleAddNew = () => {
    setSelectedCustomer(null);
    setDialogOpen(true);
  };

  const handleEdit = (customer: any) => {
    setSelectedCustomer(customer);
    setDialogOpen(true);
  };

  return (
    <div className='space-y-6'>
      <div className='flex justify-between items-center'>
        <h1 className='text-3xl font-bold'>Customers</h1>
        <div className='flex gap-2'>
          <ExportButton type='customers' label='Export' />
          <Button onClick={handleAddNew}>
            <Plus className='mr-2 h-4 w-4' />
            Add Customer
          </Button>
        </div>
      </div>

      {/* You can replace this with a proper table later */}
      <div className='grid gap-4'>
        {isError && !data ? (
          <QueryErrorState
            title='Unable to load customers'
            description='The customer directory is unavailable because the request failed.'
            onRetry={refetch}
            retrying={isFetching}
          />
        ) : fetchingCustomers ? (
          <PageLoadingState />
        ) : (
          <>
            {isError && (
              <QueryErrorState
                title='Customer list may be out of date'
                description='The latest refresh failed. The last loaded customer list is still shown.'
                onRetry={refetch}
                retrying={isFetching}
              />
            )}
            {customers.length === 0 ? (
              <QueryEmptyState
                title='No customers yet'
                description='Customers will appear here after they are added.'
              />
            ) : (
              customers.map((customer: any) => (
                <div
                  key={customer.id}
                  onClick={() => navigate(`/dashboard/customers/${customer.id}`)}
                  className='border p-4 rounded-lg flex justify-between items-center'
                >
                  <div>
                    <p className='font-medium'>
                      {customer.user.firstname} {customer.user.lastname}
                    </p>
                    <p className='text-sm text-muted-foreground'>{customer.user.email}</p>
                    {customer.company_name && <p className='text-sm text-muted-foreground'>{customer.company_name}</p>}
                  </div>
                  <Button variant='outline' size='sm' onClick={() => handleEdit(customer)}>
                    <Edit />
                    <span>Edit</span>
                  </Button>
                </div>
              ))
            )}
          </>
        )}
      </div>

      <CustomerFormDialog
        open={dialogOpen}
        selectedCustomer={selectedCustomer}
        setDialogOpen={setDialogOpen}
        onOpenChange={setDialogOpen}
      />
    </div>
  );
};
