import type { CashFlowDirection } from '@/app/store/features/business/executive/cashFlowQuery';

export type FinancePerson = {
  id?: number;
  firstname?: string | null;
  lastname?: string | null;
};

/** A customer or supplier, which may be a company or a person. */
export type FinanceCounterparty = {
  company_name?: string | null;
  user?: FinancePerson | null;
};

export type FinanceTransactionRecord = {
  id: number;
  transaction_code: string;
  type: string;
  /**
   * Present only on an adjustment. Null means the row was written before a direction was
   * required, so the server cannot say which way the money moved and excludes it from the
   * cash balance — the reason the executive dashboard warns about these.
   */
  direction?: CashFlowDirection | null;
  amount: number;
  currency: string;
  category: string;
  description: string;
  status: string;
  running_balance?: number;
  transaction_date: string;
  notes?: string | null;
  payment_method?: string | null;
  reference?: string | null;
  branch?: { id?: number; name: string } | null;
  /**
   * The user who recorded the row, not the foreign key. Eager loading createdBy replaces
   * the `created_by` id column with the loaded user, so this is an object.
   */
  created_by?: FinancePerson | null;
  customer?: FinanceCounterparty | null;
  supplier?: FinanceCounterparty | null;
  sale?: { id?: number } | null;
  purchase?: { id?: number } | null;
  sale_return?: { id?: number } | null;
  purchase_return?: { id?: number } | null;
  stock_transfer?: { id?: number } | null;
  expense?: { id?: number } | null;
};

export const typeVariant: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
  sale: 'default',
  purchase: 'destructive',
  expense: 'destructive',
  refund: 'secondary',
  payment_in: 'default',
  payment_out: 'secondary',
  adjustment: 'outline',
};

export const typeColors: Record<string, string> = {
  sale: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
  purchase: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
  expense: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
  refund: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
  payment_in: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
  payment_out: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
  adjustment: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
};

/**
 * Does this row move money out of the business?
 *
 * Keyed off `type` alone, an adjustment was always drawn as an inflow — a green `+` on an
 * amount that could equally have left the till. `direction` is what actually decides, and
 * it is only present on an adjustment. When it is null the row is one of the legacy
 * adjustments the server cannot sign, so it is drawn as neither: colouring it either way
 * would be a guess, and the row carries a control to record the answer instead.
 *
 * Returns null for that unsigned case, so callers must treat null as "sign unknown" rather
 * than coercing it to a boolean.
 */
export const isOutflow = (record: Pick<FinanceTransactionRecord, 'type' | 'direction'>) => {
  if (record.type === 'adjustment') {
    return record.direction === 'debit' ? true : record.direction === 'credit' ? false : null;
  }

  return ['purchase', 'expense', 'payment_out', 'refund'].includes(record.type);
};

export const amountTone = (outflow: boolean | null) =>
  outflow === null ? 'text-muted-foreground' : outflow ? 'text-red-600' : 'text-green-600';

export const amountSign = (outflow: boolean | null) =>
  outflow === null ? '' : outflow ? '-' : '+';

export const personName = (person?: FinancePerson | null) =>
  [person?.firstname, person?.lastname].filter(Boolean).join(' ');

/** A counterparty is named by its company if it has one, otherwise by the person behind it. */
export const counterpartyName = (counterparty?: FinanceCounterparty | null) =>
  counterparty?.company_name || personName(counterparty?.user);

/**
 * The document a ledger row was written for, if there is one.
 *
 * Which relation is populated decides the label, so a row that references nothing is not
 * given an empty link.
 */
export const sourceDocument = (record: FinanceTransactionRecord) => {
  if (record.sale) return { label: 'Sale', id: record.sale.id, to: '/dashboard/sales' };
  if (record.purchase) return { label: 'Purchase', id: record.purchase.id, to: '/dashboard/purchases' };
  if (record.sale_return)
    return { label: 'Sale return', id: record.sale_return.id, to: '/dashboard/sale-returns' };
  if (record.purchase_return)
    return { label: 'Purchase return', id: record.purchase_return.id, to: '/dashboard/purchase-returns' };
  if (record.stock_transfer)
    return { label: 'Stock transfer', id: record.stock_transfer.id, to: '/dashboard/stock-transfers' };
  if (record.expense) return { label: 'Expense', id: record.expense.id, to: '/dashboard/expenses' };

  return null;
};