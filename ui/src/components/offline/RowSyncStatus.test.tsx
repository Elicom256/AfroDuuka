import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { RowSyncStatus } from './RowSyncStatus';

describe('RowSyncStatus', () => {
  it('leaves a confirmed row unmarked', () => {
    const { container } = render(<RowSyncStatus status='synced' />);
    expect(container).toBeEmptyDOMElement();
  });

  it('marks a pending row', () => {
    render(<RowSyncStatus status='pending' />);
    expect(screen.getByText('Pending')).toHaveAttribute('data-status', 'pending');
  });

  it('marks a failed row', () => {
    render(<RowSyncStatus status='failed' />);
    expect(screen.getByText('Failed')).toHaveAttribute('data-status', 'failed');
  });
});
