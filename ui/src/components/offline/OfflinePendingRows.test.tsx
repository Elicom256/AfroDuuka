import 'fake-indexeddb/auto';
import { beforeEach, describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { db } from '@/lib/offline/db';
import { enqueueOperation } from '@/lib/offline/outbox';
import { OfflinePendingRows } from './OfflinePendingRows';

beforeEach(async () => {
  await db.operations.clear();
});

describe('OfflinePendingRows', () => {
  it('opens a pending row by its local id and formats saved dates', async () => {
    const operation = await enqueueOperation(
      'todo',
      { title: 'Count stock', created_at: '2026-10-17T00:00:00.000Z' },
      { businessId: 7, branchId: 3 },
      { operationId: '11111111-1111-4111-8111-111111111111' },
    );

    render(<OfflinePendingRows />);

    const row = await screen.findByRole('button', { name: /Count stock/ });
    expect(row).toHaveTextContent(operation.operationId.slice(0, 8));

    await userEvent.click(row);

    expect(screen.getByText(operation.operationId)).toBeInTheDocument();
    expect(screen.getByText('Count stock', { selector: 'dd' })).toBeInTheDocument();
    expect(screen.getByText('October 17th, 2026')).toBeInTheDocument();
  });
});
