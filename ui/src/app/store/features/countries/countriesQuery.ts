import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

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

export const countriesQuery = createApi({
  reducerPath: 'countriesApi',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/countries`,
  }),
  tagTypes: ['Countries'],
  endpoints: (builder) => ({
    countries: builder.query<CountriesResponse, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['Countries'],
    }),
  }),
});

export const { useCountriesQuery } = countriesQuery;
