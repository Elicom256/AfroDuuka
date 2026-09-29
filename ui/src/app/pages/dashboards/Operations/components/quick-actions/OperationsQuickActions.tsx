import { useNavigate } from 'react-router-dom';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Plus, ShoppingCart, Package, FileText, Users, Truck } from 'lucide-react';

const actions = [
  {
    label: 'New Sale',
    icon: Plus,
    href: '/operations/sales',
    color: 'bg-emerald-500/10 text-emerald-500 hover:bg-emerald-500/20',
  },
  {
    label: 'Add Product',
    icon: Package,
    href: '/operations/products',
    color: 'bg-blue-500/10 text-blue-500 hover:bg-blue-500/20',
  },
  {
    label: 'New Purchase',
    icon: ShoppingCart,
    href: '/operations/purchases',
    color: 'bg-amber-500/10 text-amber-500 hover:bg-amber-500/20',
  },
  {
    label: 'Orders',
    icon: FileText,
    href: '/operations/orders',
    color: 'bg-purple-500/10 text-purple-500 hover:bg-purple-500/20',
  },
  {
    label: 'Customers',
    icon: Users,
    href: '/operations/customers',
    color: 'bg-pink-500/10 text-pink-500 hover:bg-pink-500/20',
  },
  {
    label: 'Suppliers',
    icon: Truck,
    href: '/operations/suppliers',
    color: 'bg-cyan-500/10 text-cyan-500 hover:bg-cyan-500/20',
  },
];

export const OperationsQuickActions = () => {
  const navigate = useNavigate();

  return (
    <Card className='rounded-2xl'>
      <CardHeader className='pb-3'>
        <CardTitle className='flex items-center gap-2 text-sm'>
          <span className='text-base'>⚡</span>
          Quick Actions
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div className='grid grid-cols-3 gap-2'>
          {actions.map((action) => {
            const Icon = action.icon;
            return (
              <button
                key={action.label}
                onClick={() => navigate(action.href)}
                className={`flex flex-col items-center gap-1.5 rounded-xl p-3 transition-all duration-200 hover:scale-105 ${action.color}`}
              >
                <Icon className='h-4 w-4' />
                <span className='text-[11px] font-medium'>{action.label}</span>
              </button>
            );
          })}
        </div>
      </CardContent>
    </Card>
  );
};
