import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { SyncStatusBadge } from './SyncStatusBadge';

describe('SyncStatusBadge', () => {
  it.each([
    ['pending', 'Pending', 'outline'],
    ['syncing', 'Syncing', 'secondary'],
    ['synced', 'Synced', 'success'],
    ['failed', 'Failed', 'destructive'],
  ] as const)('renders %s as "%s" with the %s variant', (status, label, variant) => {
    render(<SyncStatusBadge status={status} />);

    const badge = screen.getByText(label);
    expect(badge).toHaveAttribute('data-variant', variant);
    expect(badge).toHaveAttribute('data-status', status);
  });

  it('carries a custom className so a row can position it', () => {
    render(<SyncStatusBadge status='pending' className='my-marker' />);
    expect(screen.getByText('Pending')).toHaveClass('my-marker');
  });
});
