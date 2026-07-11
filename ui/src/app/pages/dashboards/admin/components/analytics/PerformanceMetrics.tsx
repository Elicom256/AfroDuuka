import { useState, useEffect, useMemo } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { TrendingUp, TrendingDown, Minus, Package } from 'lucide-react';
import { Bar } from 'react-chartjs-2';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js';

import { useProductMetricsQuery, useProductsQuery } from '@/app/store/features/branch/products/branchProductsQuery';
import { LoadingState } from '@/utils/LoadingState';
import { Error } from './Error';
import { periods } from '../periodHelper';
import { MetricsCards } from './MetricsCards';
import { useCurrency } from '@/app/hooks/useCurrency';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

export const PerformanceMetrics = () => {
  const { currency } = useCurrency();
  const [selectedProductId, setSelectedProductId] = useState<string>('');
  const [selectedPeriod, setSelectedPeriod] = useState('last_7_days');

  // Fetch products list
  const { data: productsData } = useProductsQuery();
  const products = productsData?.products || [];

  // Fetch metrics for selected product
  const { data, isLoading, isError, error } = useProductMetricsQuery(
    {
      id: selectedProductId,
      period: selectedPeriod,
    },
    { skip: !selectedProductId },
  );

  const metrics = data?.data;

  // Auto select first product
  useEffect(() => {
    if (products.length > 0 && !selectedProductId) {
      setSelectedProductId(String(products[0].id));
    }
  }, [products, selectedProductId]);

  const handleProductChange = (productId: string) => setSelectedProductId(productId);
  const handlePeriodChange = (period: string) => setSelectedPeriod(period);

  // Bar Chart Data
  const chartData = useMemo(() => {
    if (!metrics) return null;

    return {
      labels: ['Sales', 'Purchases'],
      datasets: [
        {
          label: `Amount (${currency})`,
          data: [Number(metrics.sales || 0), Number(metrics.purchases || 0)],
          backgroundColor: ['#10b981', '#f97316'],
          borderRadius: 8,
          barThickness: 65,
        },
      ],
    };
  }, [metrics]);

  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: (context: any) => ` ${currency} ${context.parsed.y.toLocaleString()}`,
        },
      },
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          callback: (value: string | number) => {
            const num = typeof value === 'string' ? parseFloat(value) : value;
            return `${currency} ${(num / 1000000).toFixed(1)}M`;
          },
        },
      },
    },
  };

  if (isLoading) return <LoadingState />;
  if (isError) return <Error error={error} />;

  return (
    <Card>
      <CardHeader>
        <div className='flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4'>
          <div>
            <CardTitle className='flex items-center gap-2'>
              <Package className='h-6 w-6' />
              Product Performance
            </CardTitle>
            <CardDescription>Analyze individual product performance</CardDescription>
          </div>
        </div>
      </CardHeader>

      <CardContent className='space-y-6'>
        {/* Product Selector */}
        <div>
          <label className='text-sm font-medium mb-2 block'>Select Product</label>
          <Select value={selectedProductId} onValueChange={handleProductChange}>
            <SelectTrigger>
              <SelectValue placeholder='Choose a product to analyze' />
            </SelectTrigger>
            <SelectContent>
              {products.map((product: any) => (
                <SelectItem key={product.id} value={String(product.id)}>
                  {product.name} {product.quantity ? `(${product.quantity} in stock)` : ''}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        {/* Period Selector */}
        <div className='flex gap-1 bg-muted p-1 rounded-lg w-fit'>
          {periods.map((p) => (
            <Button
              key={p.value}
              variant={selectedPeriod === p.value ? 'default' : 'ghost'}
              size='sm'
              onClick={() => handlePeriodChange(p.value)}
              className='text-xs'
            >
              {p.label}
            </Button>
          ))}
        </div>

        {!metrics ? (
          <div className='text-center py-12 text-muted-foreground'>Select a product to view performance metrics</div>
        ) : (
          <>
            {/* KPI Cards */}
            <MetricsCards metrics={metrics} />
            {/* Sales vs Purchases Bar Chart */}
            <div>
              <h3 className='text-lg font-medium mb-3'>Sales vs Purchases</h3>
              <div className='h-70 w-full'>
                <Bar data={chartData!} options={chartOptions} />
              </div>
            </div>

            {/* Growth Indicator */}
            {metrics.growth_label === null && (
              <div
                className={`rounded-2xl p-6 flex items-center justify-between ${metrics.sales_growth > 0 ? 'bg-emerald-50' : metrics.sales_growth === 0 ? 'bg-gray-500' : 'bg-red-50'}`}
              >
                <div>
                  <p className='text-sm text-muted-foreground'>Sales Growth</p>
                  <p
                    className={`text-2xl font-semibold flex items-center gap-2 ${metrics.sales_growth > 0 ? 'text-emerald-600' : metrics.sales_growth === 0 ? '' : 'text-red-600'}`}
                  >
                    {metrics.sales_growth > 0 && <TrendingUp className='h-6 w-6' />}
                    {metrics.sales_growth === 0 && <Minus className='h-6 w-6' />}
                    {metrics.sales_growth < 0 && <TrendingDown className='h-6 w-6' />}
                    {metrics.growth_label}
                  </p>
                </div>
              </div>
            )}
          </>
        )}
      </CardContent>
    </Card>
  );
};
