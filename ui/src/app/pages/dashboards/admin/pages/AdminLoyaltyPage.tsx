import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useLoyaltyProgramsQuery, useLoyaltyCardsQuery, useLoyaltyRewardsQuery } from '@/app/store/features/business/admin/loyaltyQuery';
import { Award, Heart, Gift, Users } from 'lucide-react';
import { PageLoadingState } from '@/utils/PageLoadingState';

export const AdminLoyaltyPage = () => {
  const { data: programsData, isLoading: loading1 } = useLoyaltyProgramsQuery();
  const { data: cardsData, isLoading: loading2 } = useLoyaltyCardsQuery();
  const { data: rewardsData, isLoading: loading3 } = useLoyaltyRewardsQuery();

  if (loading1 || loading2 || loading3) return <PageLoadingState />;

  const programs = programsData?.data || [];
  const cards = cardsData?.data || [];
  const rewards = rewardsData?.data || [];

  const stats = [
    { label: 'Programs', value: programs.length, icon: Award },
    { label: 'Active Cards', value: cards.length, icon: Users },
    { label: 'Rewards', value: rewards.length, icon: Gift },
    { label: 'Total Points Issued', value: cards.reduce((sum: number, c: any) => sum + Number(c.points_balance || 0), 0).toLocaleString(), icon: Heart },
  ];

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight flex items-center gap-3'>
          <Award className='h-8 w-8' /> Loyalty Program
        </h1>
        <p className='text-muted-foreground'>Customer loyalty, points, and rewards management</p>
      </div>

      <div className='grid gap-4 sm:grid-cols-2 lg:grid-cols-4'>
        {stats.map((s) => {
          const Icon = s.icon;
          return (
            <Card key={s.label}>
              <CardContent className='p-6 flex items-center gap-4'>
                <Icon className='h-8 w-8 text-primary' />
                <div>
                  <p className='text-2xl font-bold'>{s.value}</p>
                  <p className='text-sm text-muted-foreground'>{s.label}</p>
                </div>
              </CardContent>
            </Card>
          );
        })}
      </div>

      {programs.length > 0 && (
        <Card>
          <CardHeader><CardTitle>Programs</CardTitle></CardHeader>
          <CardContent>
            <div className='space-y-2'>
              {programs.map((p: any) => (
                <div key={p.id} className='flex justify-between p-3 bg-muted/50 rounded-lg'>
                  <span className='font-medium'>{p.name}</span>
                  <span className='text-muted-foreground text-sm capitalize'>{p.type} • {p.points_per_currency} pts/currency</span>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
};
