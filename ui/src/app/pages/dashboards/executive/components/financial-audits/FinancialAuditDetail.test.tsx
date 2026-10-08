import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { Provider } from 'react-redux';
import { MemoryRouter } from 'react-router-dom';

import { FinancialAuditDetail } from './FinancialAuditDetail';
import { store } from '@/app/store/app/store';

/**
 * An audit page has to say who did the audit and who signed it off. Both were blank.
 *
 * Eager loading performedBy() serialises into `performed_by`, which is also the
 * foreign-key column, so the loaded user replaces the raw id. A User has no `name`
 * column -- a name is `firstname` and `lastname` -- so `audit.performed_by?.name` was
 * always undefined and the page rendered a dash for whoever actually did the audit.
 *
 * The fixture is the shape the API sends: a user object under the snake_case key.
 */

const audit = (overrides: Record<string, unknown> = {}) => ({
  audit_number: 'FAUDIT-0001-0001',
  audit_date: '2026-10-01',
  status: 'approved',
  expected_balance: 100000,
  actual_balance: 95000,
  difference: -5000,
  approved_at: '2026-10-02T10:00:00.000000Z',
  performed_by: { firstname: 'Amina', lastname: 'Nabirye' },
  approved_by: { firstname: 'Joseph', lastname: 'Okello' },
  ...overrides,
});

const renderDetail = (record: Record<string, unknown>) =>
  render(
    <Provider store={store}>
      <MemoryRouter>
        <FinancialAuditDetail
          audit={
            record as unknown as {
              performed_by?: { firstname?: string | null; lastname?: string | null } | null;
              approved_by?: { firstname?: string | null; lastname?: string | null } | null;
              [key: string]: unknown;
            }
          }
        />
      </MemoryRouter>
    </Provider>
  );

describe('the financial audit detail page', () => {
  it('names the person who performed the audit', () => {
    renderDetail(audit());

    expect(screen.getByText('Amina Nabirye')).toBeTruthy();
  });

  it('names the person who approved it', () => {
    renderDetail(audit());

    expect(screen.getByText('Joseph Okello')).toBeTruthy();
  });

  /**
   * The fallback has to stay. An audit that has not been approved yet has no approver,
   * and that is a fact about the record rather than a missing name.
   */
  it('falls back to a dash when there is no approver', () => {
    renderDetail(audit({ approved_by: null, status: 'completed' }));

    expect(screen.getByText('Amina Nabirye')).toBeTruthy();
    expect(screen.getAllByText('-').length).toBeGreaterThan(0);
  });

  it('shows a dash rather than nothing when the performer is unknown', () => {
    renderDetail(audit({ performed_by: null }));

    expect(screen.queryByText('Amina Nabirye')).toBeNull();
  });
});