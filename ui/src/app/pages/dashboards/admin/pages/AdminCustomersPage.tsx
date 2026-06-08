// pages/AdminCustomersPage.tsx
import { useState } from 'react';
import { useCustomersQuery } from '@/app/store/features/business/customers/customersQuery';
import { Button } from '@/components/ui/button';
import { Edit, Plus } from 'lucide-react';
import { CustomerFormDialog } from '../components/customers/CustomerFormDialog';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { useNavigate } from 'react-router-dom';

export const AdminCustomersPage = () => {
  const navigate = useNavigate();
  const { data, isLoading: fetchingCustomers } = useCustomersQuery();
  const customers = data?.customers || [];
  console.log('customers==>', customers);
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
        <Button onClick={handleAddNew}>
          <Plus className='mr-2 h-4 w-4' />
          Add Customer
        </Button>
      </div>

      {/* You can replace this with a proper table later */}
      <div className='grid gap-4'>
        {fetchingCustomers ? (
          <PageLoadingState />
        ) : customers.length === 0 ? (
          <p>No customers found.</p>
        ) : (
          customers.map((customer: any) => (
            <div
              key={customer.id}
              onClick={() => navigate(`/admin/customers/${customer.id}`)}
              className='border p-4 rounded-lg flex justify-between items-center'
            >
              <div>
                <p className='font-medium'>
                  {customer.user.firstname} {customer.user.lastname}
                </p>
                <p className='text-sm text-gray-500'>{customer.user.email}</p>
                {customer.company_name && <p className='text-sm text-gray-600'>{customer.company_name}</p>}
              </div>
              <Button variant='outline' size='sm' onClick={() => handleEdit(customer)}>
                <Edit />
                <span>Edit</span>
              </Button>
            </div>
          ))
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
