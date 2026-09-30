import { Card } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { useGetSalesAnalyticsQuery } from '@/app/store/features/branch/sales/salesQuery';
import { useCurrency } from '@/app/hooks/useCurrency';
import { Line } from 'react-chartjs-2';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Tooltip,
  Legend,
} from 'chart.js';
import { TrendingUp, DollarSign, ShoppingCart, Package, Trophy, ArrowUpRight, ArrowDownRight } from 'lucide-react';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Legend);

type DashboardPeriod = 'today' | 'last_7_days' | 'last_30_days' | 'this_month' | 'last_month';

const sparklineOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false }, tooltip: { enabled: false } },
  scales: { x: { display: false }, y: { display: false } },
};

const DeltaChip = ({ current, previous }: { current: number; previous: number }) => {
  if (!previous || previous <= 0) {
    return <span className='rounded-full bg-muted px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground'>—</span>;
  }
  const pct = ((current - previous) / previous) * 100;
  const positive = pct >= 0;
  return (
    <span className={`inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-[10px] font-medium ${positive ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-red-500/10 text-red-600 dark:text-red-400'}`}>
      {positive ? <ArrowUpRight className='h-2.5 w-2.5' /> : <ArrowDownRight className='h-2.5 w-2.5' />}
      {Math.abs(pct).toFixed(0)}%
    </span>
  );
};

const Sparkline = ({ data, color }: { data: number[]; color: string }) => {
  if (data.length < 2) return null;
  return (
    <div className='h-8 w-20 shrink-0'>
      <Line
        data={{
          labels: data.map((_, i) => String(i)),
          datasets: [{ data, borderColor: color, borderWidth: 2, pointRadius: 0, tension: 0.4 }],
        }}
        options={sparklineOptions}
      />
    </div>
  );
};

export const SalesStatCards = ({ period }: { period: DashboardPeriod }) => {
  const { currency, currencySymbol } = useCurrency();
  const { data, isLoading } = useGetSalesAnalyticsQuery(period);

  if (isLoading) {
    return (
      <div className='grid gap-3 sm:grid-cols-2 xl:grid-cols-4'>
        {Array.from({ length: 4 }).map((_, i) => (
          <Skeleton key={i} className='h-20 rounded-3xl' />
        ))}
      </div>
    );
  }

  const analytics = data?.data;
  const previous = analytics?.previous ?? {};
  const trend = analytics?.sales_trend ?? [];

  const totalRevenue = Number(analytics?.total_sales ?? 0);
  const totalOrders = Number(analytics?.total_transactions ?? 0);
  const totalItemsSold = Number(analytics?.items_sold ?? 0);
  const avgOrderValue = Number(analytics?.avg_sale ?? 0);

  const bestDay = trend.length > 0
    ? trend.reduce((best: any, point: any) => (Number(point.amount) > Number(best.amount) ? point : best), trend[0])
    : null;

  const symbol = currencySymbol ?? currency ?? '';
  const formatAmount = (amount: number) => `${symbol} ${Math.round(amount).toLocaleString()}`;

  const cards = [
    {
      title: 'Total Revenue',
      value: formatAmount(totalRevenue),
      icon: DollarSign,
      iconClass: 'bg-emerald-500/10 text-emerald-500',
      delta: { current: totalRevenue, previous: Number(previous.total_sales ?? 0) },
      spark: trend.map((p: any) => Number(p.amount) || 0),
      sparkColor: '#10b981',
    },
    {
      title: 'Total Orders',
      value: totalOrders.toLocaleString(),
      icon: ShoppingCart,
      iconClass: 'bg-blue-500/10 text-blue-500',
      delta: { current: totalOrders, previous: Number(previous.total_transactions ?? 0) },
      spark: trend.map((p: any) => Number(p.count) || 0),
      sparkColor: '#3b82f6',
    },
    {
      title: 'Items Sold',
      value: totalItemsSold.toLocaleString(),
      icon: Package,
      iconClass: 'bg-purple-500/10 text-purple-500',
      delta: { current: totalItemsSold, previous: Number(previous.items_sold ?? 0) },
      spark: trend.map((p: any) => Number(p.items) || 0),
      sparkColor: '#a855f7',
    },
    {
      title: 'Avg Order Value',
      value: formatAmount(avgOrderValue),
      icon: TrendingUp,
      iconClass: 'bg-amber-500/10 text-amber-500',
      delta: { current: avgOrderValue, previous: Number(previous.avg_sale ?? 0) },
      spark: trend.map((p: any) => (Number(p.count) > 0 ? (Number(p.amount) || 0) / Number(p.count) : 0)),
      sparkColor: '#f59e0b',
    },
  ];

  return (
    <div className='space-y-3'>
      <div className='grid gap-3 sm:grid-cols-2 xl:grid-cols-4'>
        {cards.map((card) => (
          <Card key={card.title} className='rounded-3xl border border-border/70 bg-card p-4 transition-all hover:shadow-md'>
            <div className='flex items-start justify-between gap-2'>
              <div className='flex items-center gap-2.5'>
                <div className={`rounded-xl p-2 ${card.iconClass}`}>
                  <card.icon className='h-4 w-4' />
                </div>
                <div>
                  <div className='flex items-center gap-1.5'>
                    <p className='text-xs text-muted-foreground'>{card.title}</p>
                    <DeltaChip current={card.delta.current} previous={card.delta.previous} />
                  </div>
                  <p className='text-lg font-semibold tracking-tight'>{card.value}</p>
                </div>
              </div>
              <Sparkline data={card.spark} color={card.sparkColor} />
            </div>
          </Card>
        ))}
      </div>

      {bestDay && Number(bestDay.amount) > 0 && (
        <Card className='rounded-3xl border border-border/70 bg-gradient-to-r from-emerald-500/10 via-card to-card p-4'>
          <div className='flex items-center gap-2.5'>
            <div className='rounded-xl bg-emerald-500/10 p-2'>
              <Trophy className='h-4 w-4 text-emerald-500' />
            </div>
            <div>
              <p className='text-xs text-muted-foreground'>Best day</p>
              <p className='text-sm font-semibold'>
                {bestDay.date} — {formatAmount(Number(bestDay.amount))} · {Number(bestDay.count) || 0} orders
              </p>
            </div>
          </div>
        </Card>
      )}
    </div>
  );
};
