import { useDeleteSupplierMutation, useSupplierQuery } from '@/app/store/features/business/suppliers/supplierQuery';
import { useNavigate, useParams } from 'react-router-dom';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Calendar, Mail, Phone, MapPin, User, Building2, Hash, Trash2 } from 'lucide-react';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { toast } from 'sonner';

export const Supplier = () => {
  const navigate = useNavigate();
  const { id } = useParams<{ id: string }>();
  const { data, error, isLoading } = useSupplierQuery(id!, { skip: !id });
  const [destroy, { isLoading: deleting }] = useDeleteSupplierMutation();
  const supplier = data?.supplier;
  const user = supplier?.user;

  if (isLoading || deleting) return <PageLoadingState />;
  if (error || !supplier)
    return <div className='p-6 text-red-500'>Failed to load supplier, {(error as any)?.data.message}</div>;
  const handleDelete = async () => {
    try {
      console.log('id==>', supplier?.id);
      const res = await destroy(supplier?.id).unwrap();
      console.log('response==>', res);
      if (res) {
        toast.success(res.message);
        return navigate('/admin/suppliers');
      }
    } catch (error) {
      toast.error('failed to delete supplier');
      console.log('error==>', error);
    }
  };
  return (
    <div className='space-y-6 p-6'>
      {/* Header */}
      <div className='flex justify-between items-start'>
        <div>
          <div className='flex items-center gap-3'>
            <h1 className='text-3xl font-bold'>{supplier.company_name}</h1>
            <Badge variant={supplier.status === 'active' ? 'secondary' : 'destructive'}>
              {supplier.status.toUpperCase()}
            </Badge>
          </div>
          <p className='text-muted-foreground flex items-center gap-2 mt-1'>
            <Hash className='w-4 h-4' />
            {supplier.supplier_code}
          </p>
        </div>

        <div className=''>
          <Trash2 onClick={handleDelete} className='text-red-300 cursor-pointer' />
        </div>
      </div>

      <Separator />

      <div className='grid grid-cols-1 lg:grid-cols-2 gap-6'>
        {/* Company Information */}
        <Card>
          <CardHeader>
            <CardTitle className='flex items-center gap-2'>
              <Building2 className='w-5 h-5' />
              Company Information
            </CardTitle>
          </CardHeader>
          <CardContent className='space-y-4'>
            <div>
              <p className='text-sm text-muted-foreground'>Company Name</p>
              <p className='font-medium'>{supplier.company_name}</p>
            </div>
            <div>
              <p className='text-sm text-muted-foreground'>Supplier Code</p>
              <p className='font-medium'>{supplier.supplier_code}</p>
            </div>
            <div>
              <p className='text-sm text-muted-foreground'>Status</p>
              <Badge variant={supplier.status === 'active' ? 'secondary' : 'destructive'}>{supplier.status}</Badge>
            </div>
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
              <CardTitle>Representative Details</CardTitle>
              <CardDescription>Person responsible for this supplier</CardDescription>
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
                <p>{new Date(supplier.created_at).toLocaleString()}</p>
              </div>
              <div>
                <p className='text-muted-foreground'>Last Updated</p>
                <p>{new Date(supplier.updated_at).toLocaleString()}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
};
