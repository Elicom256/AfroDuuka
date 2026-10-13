import { baseApi } from '@/app/store/baseApi';

export interface Country {
  id: number;
  name: string;
  iso_alpha2: string;
  flag_emoji: string;
  currency_code?: string;
  calling_code?: string;
}

interface CountriesResponse {
  data: Country[];
}

export const countriesQuery = baseApi.injectEndpoints({
  endpoints: (builder) => ({
    countries: builder.query<CountriesResponse, void>({
      query: () => ({
        url: '/countries/',
        method: 'GET',
      }),
      providesTags: ['Countries'],
    }),
  }),
});

export const { useCountriesQuery } = countriesQuery;
