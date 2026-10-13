import { baseApi } from '@/app/store/baseApi';

export const plansQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    getPlans: builder.query<PlansResponse, void>({
      query: () => ({ url: '/plans', method: 'GET' }),
      providesTags: ['PlansAPI'],
    }),
  }),
});

export const { useGetPlansQuery } = plansQuery;

interface Limits {
  maxProducts?: number;
  maxBranches?: number;
  maxUsers?: number;
}

interface Plan {
  id: number;
  name: string;
  slug: string;
  mark: string;
  description?: string;
  monthly_price: number;
  yearly_price: number;
  discount_percentage?: number;
  billing_cycle?: string;
  features?: string[];
  limits?: Limits;
  status?: string;
  is_active?: boolean;
  sort_order?: number;
  currency: string;
}

interface PlansResponse {
  plans: Plan[];
}
