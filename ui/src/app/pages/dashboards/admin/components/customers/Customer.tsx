import { useCustomerQuery, useDeleteCustomerMutation } from '@/app/store/features/business/customers/customersQuery';
import { useNavigate, useParams } from 'react-router-dom';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Calendar, Mail, Phone, MapPin, User, Hash, FileText, Trash2 } from 'lucide-react';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { toast } from 'sonner';

export const Customer = () => {
  const navigate = useNavigate();

  const { id } = useParams<{ id: string }>();
  const { data, error, isLoading } = useCustomerQuery(id!, { skip: !id });
  const [destroy, { isLoading: deleting }] = useDeleteCustomerMutation();

  const customer = data?.customer;
  const user = customer?.user;

  if (isLoading || deleting) return <PageLoadingState />;
  if (error || !customer)
    return <div className='p-6 text-red-500'>Failed to load customer, {(error as any)?.data.message}</div>;

  const handleDelete = async () => {
    try {
      console.log('id==>', customer?.id);
      const res = await destroy(customer?.id).unwrap();
      console.log('response==>', res);
      if (res) {
        toast.success(res.message);
        return navigate('/admin/customers');
      }
    } catch (error) {
      toast.error('failed to delete customer');
      console.log('error==>', error);
    }
  };

  return (
    <div className='space-y-6 p-6'>
      {/* Header */}
      <div className='flex justify-between items-start'>
        <div>
          <div className='flex items-center gap-3'>
            <h1 className='text-3xl font-bold'>{customer.company_name || `${user?.firstname} ${user?.lastname}`}</h1>
            <Badge variant={customer.status === 'active' ? 'secondary' : 'destructive'}>
              {customer.status.toUpperCase()}
            </Badge>
          </div>
          <p className='text-muted-foreground flex items-center gap-2 mt-1'>
            <Hash className='w-4 h-4' />
            {customer.customer_code}
          </p>
        </div>

        <Trash2 onClick={handleDelete} className='text-red-300 cursor-pointer' />
      </div>

      <Separator />

      <div className='grid grid-cols-1 lg:grid-cols-2 gap-6'>
        {/* Customer Information */}
        <Card>
          <CardHeader>
            <CardTitle className='flex items-center gap-2'>
              <User className='w-5 h-5' />
              Customer Information
            </CardTitle>
          </CardHeader>
          <CardContent className='space-y-4'>
            {customer.company_name && (
              <div>
                <p className='text-sm text-muted-foreground'>Company Name</p>
                <p className='font-medium'>{customer.company_name}</p>
              </div>
            )}

            <div>
              <p className='text-sm text-muted-foreground'>Customer Code</p>
              <p className='font-medium'>{customer.customer_code}</p>
            </div>

            <div>
              <p className='text-sm text-muted-foreground'>Status</p>
              <Badge variant={customer.status === 'active' ? 'secondary' : 'destructive'}>{customer.status}</Badge>
            </div>

            {customer.remarks && (
              <div>
                <p className='text-sm text-muted-foreground flex items-center gap-2'>
                  <FileText className='w-4 h-4' />
                  Remarks
                </p>
                <p className='text-sm'>{customer.remarks}</p>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Contact Information */}
        <Card>
          <CardHeader>
            <CardTitle className='flex items-center gap-2'>
              <User className='w-5 h-5' />
              Contact Information
            </CardTitle>
          </CardHeader>
          <CardContent className='space-y-4'>
            <div className='flex items-center gap-3'>
              <Mail className='w-5 h-5 text-muted-foreground' />
              <div>
                <p className='text-sm text-muted-foreground'>Email</p>
                <p>{user?.email || 'N/A'}</p>
              </div>
            </div>

            <div className='flex items-center gap-3'>
              <Phone className='w-5 h-5 text-muted-foreground' />
              <div>
                <p className='text-sm text-muted-foreground'>Phone</p>
                <p>{user?.phone || 'N/A'}</p>
              </div>
            </div>

            <div className='flex items-start gap-3'>
              <MapPin className='w-5 h-5 text-muted-foreground mt-0.5' />
              <div>
                <p className='text-sm text-muted-foreground'>Address</p>
                <p>{user?.address || 'N/A'}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Personal Details */}
        {user && (
          <Card className='lg:col-span-2'>
            <CardHeader>
              <CardTitle>Personal Details</CardTitle>
              <CardDescription>Customer profile information</CardDescription>
            </CardHeader>
            <CardContent>
              <div className='grid grid-cols-1 md:grid-cols-2 gap-6'>
                <div>
                  <p className='text-sm text-muted-foreground'>Full Name</p>
                  <p className='font-medium'>
                    {user.firstname} {user.lastname}
                  </p>
                </div>
                <div>
                  <p className='text-sm text-muted-foreground'>Username</p>
                  <p className='font-medium'>{user.username}</p>
                </div>
                <div>
                  <p className='text-sm text-muted-foreground'>NIN</p>
                  <p>{user.nin}</p>
                </div>
                <div>
                  <p className='text-sm text-muted-foreground'>Role ID</p>
                  <p>{user.role_id}</p>
                </div>
              </div>
            </CardContent>
          </Card>
        )}

        {/* Timestamps */}
        <Card className='lg:col-span-2'>
          <CardHeader>
            <CardTitle className='flex items-center gap-2'>
              <Calendar className='w-5 h-5' />
              Record Information
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className='grid grid-cols-1 md:grid-cols-2 gap-6 text-sm'>
              <div>
                <p className='text-muted-foreground'>Created At</p>
                <p>{new Date(customer.created_at).toLocaleString()}</p>
              </div>
              <div>
                <p className='text-muted-foreground'>Last Updated</p>
                <p>{new Date(customer.updated_at).toLocaleString()}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
};
