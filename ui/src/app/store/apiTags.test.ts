import { describe, expect, it } from 'vitest';
import { apiTags, dashboardTags, sharedTagSets } from './apiTags';

describe('shared RTK tags', () => {
  it('exposes a single dashboard invalidation tag shared by sales, purchases and product data', () => {
    expect(apiTags.dashboard).toBe('DashboardAPI');
    expect(sharedTagSets.sales).toContain(apiTags.dashboard);
    expect(sharedTagSets.purchases).toContain(apiTags.dashboard);
    expect(sharedTagSets.products).toContain(apiTags.dashboard);
    expect(dashboardTags).toContain(apiTags.dashboard);
  });
});
