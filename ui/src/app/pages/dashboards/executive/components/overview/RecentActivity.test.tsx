import { describe, expect, it, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';

import { RecentActivity } from './RecentActivity';
import { useGetActivityLogsQuery } from '@/app/store/features/business/executive/activityLogQuery';

vi.mock('@/app/store/features/business/executive/activityLogQuery', async (importOriginal) => {
  const actual = await importOriginal<
    typeof import('@/app/store/features/business/executive/activityLogQuery')
  >();

  return { ...actual, useGetActivityLogsQuery: vi.fn() };
});

/**
 * The dashboard widget is the executive's first look at activity, but it requested no
 * log_name — so unlike the activity page it never hit the noise exclusion and the
 * "x logged in" entries bugs.md complained about landed right at the top of the home
 * screen. The sentinel `log_name=business` is the same one the page uses.
 */
describe('RecentActivity', () => {
  beforeEach(() => {
    vi.mocked(useGetActivityLogsQuery).mockReset();
  });

  it('requests only important logs, the same way the activity page does', () => {
    vi.mocked(useGetActivityLogsQuery).mockReturnValue({
      data: { data: [] },
      isLoading: false,
    } as unknown as ReturnType<typeof useGetActivityLogsQuery>);

    render(<RecentActivity />);

    expect(useGetActivityLogsQuery).toHaveBeenCalledWith({ per_page: 5, log_name: 'business' });
  });

  it('renders the activity it is given', () => {
    vi.mocked(useGetActivityLogsQuery).mockReturnValue({
      data: {
        data: [
          {
            id: 1,
            description: 'Recorded Expense',
            causer: { name: 'Jane Doe' },
            created_at: new Date().toISOString(),
          },
        ],
      },
      isLoading: false,
    } as unknown as ReturnType<typeof useGetActivityLogsQuery>);

    render(<RecentActivity />);

    expect(screen.getByText('Recorded Expense')).toBeDefined();
    expect(screen.getByText(/Jane Doe/)).toBeDefined();
  });
});
