import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useLoyaltyProgramsQuery, useCreateLoyaltyProgramMutation, useDeleteLoyaltyProgramMutation, useLoyaltyCardsQuery, useLoyaltyRewardsQuery, useCreateLoyaltyRewardMutation, useDeleteLoyaltyRewardMutation } from '@/app/store/features/business/admin/loyaltyQuery';
import { Award, Heart, Gift, Users } from 'lucide-react';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { AddLoyaltyProgram } from '../components/loyalty/AddLoyaltyProgram';
import { LoyaltyProgramsTable } from '../components/loyalty/LoyaltyProgramsTable';
import { AddLoyaltyReward } from '../components/loyalty/AddLoyaltyReward';
import { LoyaltyRewardsTable } from '../components/loyalty/LoyaltyRewardsTable';

export const AdminLoyaltyPage = () => {
  const { data: programsData, isLoading: loading1 } = useLoyaltyProgramsQuery();
  const { data: cardsData, isLoading: loading2 } = useLoyaltyCardsQuery();
  const { data: rewardsData, isLoading: loading3 } = useLoyaltyRewardsQuery();
  const [createProgram] = useCreateLoyaltyProgramMutation();
  const [deleteProgram] = useDeleteLoyaltyProgramMutation();
  const [createReward] = useCreateLoyaltyRewardMutation();
  const [deleteReward] = useDeleteLoyaltyRewardMutation();

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

      <Card>
        <CardHeader className='flex flex-row items-center justify-between'>
          <CardTitle>Programs ({programs.length})</CardTitle>
          <AddLoyaltyProgram createProgram={createProgram} />
        </CardHeader>
        <CardContent>
          {programs.length === 0 ? (
            <p className='text-muted-foreground text-sm text-center py-8'>No loyalty programs yet.</p>
          ) : (
            <LoyaltyProgramsTable programs={programs} onDelete={deleteProgram} />
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader className='flex flex-row items-center justify-between'>
          <CardTitle>Rewards ({rewards.length})</CardTitle>
          <AddLoyaltyReward createReward={createReward} />
        </CardHeader>
        <CardContent>
          {rewards.length === 0 ? (
            <p className='text-muted-foreground text-sm text-center py-8'>No rewards configured.</p>
          ) : (
            <LoyaltyRewardsTable rewards={rewards} onDelete={deleteReward} />
          )}
        </CardContent>
      </Card>
    </div>
  );
};
