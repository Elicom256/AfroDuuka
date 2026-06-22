import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react';

export const countriesQuery = createApi({
  reducerPath: 'countriesApi',
  baseQuery: fetchBaseQuery({
    baseUrl: `${import.meta.env.VITE_BASE_URL}/countries`,
  }),
  tagTypes: ['Countries'],
  endpoints: (builder) => ({
    countries: builder.query<any, void>({
      query: () => ({
        url: '/',
        method: 'GET',
      }),
      providesTags: ['Countries'],
    }),
  }),
});

export const { useCountriesQuery } = countriesQuery;
