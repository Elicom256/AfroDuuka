import { describe, expect, it, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Provider } from 'react-redux';
import { MemoryRouter } from 'react-router-dom';

import { BusinessSetup } from './BusinessSetup';
import { store } from '@/app/store/app/store';
import { useGetPublicBusinessCategoriesQuery } from '@/app/store/features/business/setup/onboardingQuery';
import { useCountriesQuery } from '@/app/store/features/countries/countriesQuery';
import { initialBusinessData } from './types';

/**
 * Both selects on the business step are regression targets.
 *
 * The country field used to be a filter textbox sitting above a plain dropdown —
 * two controls for one answer. It is now a single searchable combobox, so the
 * assertions below open it, type into the search, and pick a result.
 *
 * The category field used to read `.data` off an endpoint that answers with the
 * collection bare, which left it silently empty: no error, just nothing to choose.
 * The fixture is the endpoint's real bare-array shape, not a paginator shape that
 * would pass against the old code and prove nothing.
 */

vi.mock('@/app/store/features/business/setup/onboardingQuery', async (importOriginal) => {
  const actual = await importOriginal<
    typeof import('@/app/store/features/business/setup/onboardingQuery')
  >();
  return { ...actual, useGetPublicBusinessCategoriesQuery: vi.fn() };
});

vi.mock('@/app/store/features/countries/countriesQuery', async (importOriginal) => {
  const actual = await importOriginal<
    typeof import('@/app/store/features/countries/countriesQuery')
  >();
  return { ...actual, useCountriesQuery: vi.fn() };
});

const categories = [
  { id: 1, name: 'Retail' },
  { id: 2, name: 'Wholesale' },
];

const countries = [
  { id: 1, name: 'Uganda', iso_alpha2: 'UG', flag_emoji: '🇺🇬', currency_code: 'UGX' },
  { id: 2, name: 'Kenya', iso_alpha2: 'KE', flag_emoji: '🇰🇪', currency_code: 'KES' },
  { id: 3, name: 'United Kingdom', iso_alpha2: 'GB', flag_emoji: '🇬🇧', currency_code: 'GBP' },
];

beforeEach(() => {
  (useGetPublicBusinessCategoriesQuery as unknown as ReturnType<typeof vi.fn>).mockReturnValue({
    data: categories,
    isLoading: false,
  });
  (useCountriesQuery as unknown as ReturnType<typeof vi.fn>).mockReturnValue({
    data: { data: countries },
    isLoading: false,
  });
});

const renderForm = (onChange = () => {}) =>
  render(
    <Provider store={store}>
      <MemoryRouter>
        <BusinessSetup
          data={initialBusinessData}
          errors={{}}
          onChange={onChange}
          onNext={() => {}}
          onBack={() => {}}
        />
      </MemoryRouter>
    </Provider>
  );

describe('the business category selector', () => {
  it('offers every category the endpoint returned', async () => {
    const user = userEvent.setup();
    renderForm();

    await user.click(screen.getAllByRole('combobox')[0]);

    expect(await screen.findByRole('option', { name: /Retail/ })).toBeTruthy();
    expect(screen.getByRole('option', { name: /Wholesale/ })).toBeTruthy();
  });
});

describe('the country combobox', () => {
  it('filters as you type and selects the matching country', async () => {
    const user = userEvent.setup();
    const onChange = vi.fn();
    renderForm(onChange);

    // The country trigger is a button associated with the Country field label, not a
    // native select, so it is opened like one and searched like a text input.
    await user.click(screen.getByRole('button', { name: /country/i }));

    const search = await screen.findByLabelText('Search countries');

    await user.type(search, 'ken');

    expect(await screen.findByRole('option', { name: /Kenya/ })).toBeTruthy();
    expect(screen.queryByRole('option', { name: /Uganda/ })).toBeNull();

    await user.click(screen.getByRole('option', { name: /Kenya/ }));

    expect(onChange).toHaveBeenCalledWith(
      expect.objectContaining({ country_id: '2' })
    );
  });

  it('filters by currency code as well as name', async () => {
    const user = userEvent.setup();
    renderForm();

    await user.click(screen.getByRole('button', { name: /country/i }));

    await user.type(await screen.findByLabelText('Search countries'), 'GBP');

    expect(await screen.findByRole('option', { name: /United Kingdom/ })).toBeTruthy();
  });
});
