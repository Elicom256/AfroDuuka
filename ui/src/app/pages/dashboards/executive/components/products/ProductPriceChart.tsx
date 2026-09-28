import { useMemo, useRef, useEffect } from 'react';
import { Line } from 'react-chartjs-2';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js';
import { useProductPriceHistoryQuery } from '@/app/store/features/branch/priceHistory/priceHistoryQuery';
import { useCurrency } from '@/app/hooks/useCurrency';
import { LoadingState } from '@/utils/LoadingState';
import { format } from 'date-fns';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler);

interface ProductPriceChartProps {
  productId: string;
}

export const ProductPriceChart = ({ productId }: ProductPriceChartProps) => {
  const { currency } = useCurrency();
  const chartRef = useRef<any>(null);
  const { data, isLoading, error } = useProductPriceHistoryQuery({ productId, per_page: 10000 });

  const records = data?.data?.data ?? [];

  const sortedRecords = useMemo(() => {
    return [...records].sort(
      (a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
    );
  }, [records]);

  const chartData = useMemo(() => {
    if (!sortedRecords.length) return null;

    return {
      labels: sortedRecords.map((r) => format(new Date(r.created_at), 'MMM d')),
      datasets: [
        {
          label: `Cost Price (${currency})`,
          data: sortedRecords.map((r) => Number(r.new_cost_price)),
          borderColor: '#ef4444',
          backgroundColor: 'rgba(239, 68, 68, 0.1)',
          borderWidth: 2,
          tension: 0.3,
          pointRadius: 3,
          pointHoverRadius: 6,
          fill: false,
        },
        {
          label: `Selling Price (${currency})`,
          data: sortedRecords.map((r) => Number(r.new_sale_price)),
          borderColor: '#22c55e',
          backgroundColor: 'rgba(34, 197, 94, 0.1)',
          borderWidth: 2,
          tension: 0.3,
          pointRadius: 3,
          pointHoverRadius: 6,
          fill: false,
        },
      ],
    };
  }, [sortedRecords, currency]);

  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: true,
        position: 'top' as const,
        labels: { boxWidth: 12, padding: 12, font: { size: 11 } },
      },
      tooltip: {
        callbacks: {
          label: (ctx: any) => `${ctx.dataset.label}: ${currency} ${ctx.parsed.y}`,
        },
      },
    },
    scales: {
      y: {
        beginAtZero: false,
        ticks: {
          callback: (value: any) => `${currency} ${value}`,
        },
      },
    },
  } as const;

  useEffect(() => {
    return () => {
      if (chartRef.current) chartRef.current.destroy();
    };
  }, []);

  if (isLoading) return <LoadingState />;
  if (error) return <p className='text-sm text-red-500'>Failed to load price chart.</p>;
  if (!sortedRecords.length) {
    return (
      <p className='text-sm text-muted-foreground text-center py-4'>
        No price history data to chart.
      </p>
    );
  }

  return (
    <div className='h-72 w-full'>
      <Line ref={chartRef} data={chartData!} options={chartOptions} />
    </div>
  );
};
