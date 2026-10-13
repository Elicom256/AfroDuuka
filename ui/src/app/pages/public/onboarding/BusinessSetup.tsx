import { Building2, Globe, Mail, MapPin, Phone } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';
import { Check, ChevronsUpDown } from 'lucide-react';
import { Textarea } from '@/components/ui/textarea';
import { Field, FieldDescription, FieldError, FieldGroup, FieldLabel } from '@/components/ui/field';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { useCountriesQuery } from '@/app/store/features/countries/countriesQuery';
import { cn } from '@/lib/utils';
import { useGetPublicBusinessCategoriesQuery } from '@/app/store/features/business/setup/onboardingQuery';
import type { BusinessCategory } from '@/app/store/features/business/setup/onboardingQuery';
import type { BusinessData, StepErrors } from './types';

interface BusinessSetupProps {
  data: BusinessData;
  errors: StepErrors;
  onChange: (data: BusinessData) => void;
  onNext: () => void;
  onBack: () => void;
}

/**
 * Step 2 — the business itself.
 *
 * Every field the signup brief lists is collected here and, unlike the previous version
 * of this step, all of them reach the server: the business email and phone were being
 * dropped by StoreBusinessRequest before the service ever saw them, so the business
 * silently inherited the owner's contact details.
 */
export const BusinessSetup: React.FC<BusinessSetupProps> = ({ data, errors, onChange, onNext, onBack }) => {
  const { data: countriesData, isLoading: isLoadingCountries } = useCountriesQuery();
  const { data: categoryData, isLoading: isLoadingCategories } = useGetPublicBusinessCategoriesQuery();
  const [countryQuery, setCountryQuery] = useState('');
  const [isCountryOpen, setIsCountryOpen] = useState(false);

  const countries = useMemo(() => countriesData?.data ?? [], [countriesData]);
  const selectedCountry = useMemo(() => countries.find((c) => String(c.id) === data.country_id), [countries, data.country_id]);
  const categories = useMemo(() => {
    if (Array.isArray(categoryData)) return categoryData;
    const wrapped = categoryData as { data?: unknown; categories?: unknown } | undefined;
    if (Array.isArray(wrapped?.data)) return (wrapped.data as BusinessCategory[]) ?? [];
    if (Array.isArray(wrapped?.categories)) return (wrapped.categories as BusinessCategory[]) ?? [];
    return [];
  }, [categoryData]);

  // ~250 countries behind a dropdown nobody can scroll usefully through. Filtering as
  // they type is the difference between finding Uganda and giving up.
  const filteredCountries = useMemo(() => {
    const needle = countryQuery.trim().toLowerCase();

    if (!needle) return countries;

    return countries.filter(
      (country) =>
        country.name.toLowerCase().includes(needle) ||
        country.iso_alpha2.toLowerCase() === needle ||
        country.currency_code?.toLowerCase().includes(needle)
    );
  }, [countries, countryQuery]);

  const set = (field: keyof BusinessData, value: string) => onChange({ ...data, [field]: value });

  return (
    <form
      noValidate
      onSubmit={(e) => {
        e.preventDefault();
        onNext();
      }}
      className='space-y-6'
    >
      <FieldGroup className='gap-5'>
        <Field data-invalid={Boolean(errors.name)}>
          <FieldLabel htmlFor='business_name'>
            <Building2 className='h-4 w-4 text-muted-foreground' aria-hidden />
            Business name
          </FieldLabel>
          <Input
            id='business_name'
            name='business_name'
            autoComplete='organization'
            value={data.name}
            onChange={(e) => set('name', e.target.value)}
            placeholder="Jane's Shop"
            aria-invalid={Boolean(errors.name)}
          />
          {errors.name && <FieldError>{errors.name}</FieldError>}
        </Field>

        <Field data-invalid={Boolean(errors.business_category_id)}>
          <FieldLabel htmlFor='business_category_id'>Business category</FieldLabel>
          <Select
            value={data.business_category_id}
            onValueChange={(value) => set('business_category_id', value)}
          >
            <SelectTrigger id='business_category_id' className='w-full' aria-invalid={Boolean(errors.business_category_id)}>
              <SelectValue placeholder={isLoadingCategories ? 'Loading categories…' : 'Select your business category'} />
            </SelectTrigger>
            <SelectContent>
              {categories.map((category: BusinessCategory) => (
                <SelectItem key={category.id} value={String(category.id)}>
                  {category.name}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          {errors.business_category_id && <FieldError>{errors.business_category_id}</FieldError>}
        </Field>

        <Field data-invalid={Boolean(errors.country_id)}>
          <FieldLabel htmlFor='business_country'>
            <Globe className='h-4 w-4 text-muted-foreground' aria-hidden />
            Country
          </FieldLabel>
          <Popover open={isCountryOpen} onOpenChange={setIsCountryOpen}>
            <PopoverTrigger asChild>
              <button
                type='button'
                id='business_country'
                aria-invalid={Boolean(errors.country_id)}
                className={cn(
                  'flex h-9 w-full items-center justify-between gap-2 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50',
                  'dark:bg-input/30'
                )}
              >
                {selectedCountry ? (
                  <span className='flex items-center gap-2 truncate'>
                    <span aria-hidden>{selectedCountry.flag_emoji}</span>
                    <span className='truncate'>{selectedCountry.name}</span>
                    {selectedCountry.currency_code && (
                      <span className='text-xs text-muted-foreground'>{selectedCountry.currency_code}</span>
                    )}
                  </span>
                ) : (
                  <span className='text-muted-foreground'>
                    {isLoadingCountries ? 'Loading countries…' : 'Select your country'}
                  </span>
                )}
                <ChevronsUpDown className='size-4 shrink-0 text-muted-foreground' />
              </button>
            </PopoverTrigger>
            <PopoverContent className='w-[var(--radix-popover-trigger-width)] p-0' align='start'>
              <div className='border-b p-2'>
                <Input
                  value={countryQuery}
                  onChange={(e) => setCountryQuery(e.target.value)}
                  placeholder='Search country, ISO code or currency…'
                  className='h-8'
                  autoFocus
                />
              </div>
              <div className='max-h-60 overflow-y-auto p-1'>
                {filteredCountries.length === 0 ? (
                  <div className='px-3 py-6 text-center text-sm text-muted-foreground'>
                    No country matches “{countryQuery}”
                  </div>
                ) : (
                  filteredCountries.map((country) => {
                    const selected = String(country.id) === data.country_id;
                    return (
                      <button
                        key={country.id}
                        type='button'
                        onClick={() => {
                          set('country_id', String(country.id));
                          setIsCountryOpen(false);
                          setCountryQuery('');
                        }}
                        className={cn(
                          'flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm transition-colors hover:bg-accent hover:text-accent-foreground',
                          selected && 'bg-accent text-accent-foreground'
                        )}
                      >
                        <span aria-hidden>{country.flag_emoji}</span>
                        <span className='flex-1 truncate'>{country.name}</span>
                        {country.currency_code && (
                          <span className='text-xs text-muted-foreground'>{country.currency_code}</span>
                        )}
                        {selected && <Check className='size-4' />}
                      </button>
                    );
                  })
                )}
              </div>
            </PopoverContent>
          </Popover>
          {errors.country_id ? (
            <FieldError>{errors.country_id}</FieldError>
          ) : (
            <FieldDescription>Sets your currency and the timezone your reports run in.</FieldDescription>
          )}
        </Field>

        <Field data-invalid={Boolean(errors.email)}>
          <FieldLabel htmlFor='business_email'>
            <Mail className='h-4 w-4 text-muted-foreground' aria-hidden />
            Business email
          </FieldLabel>
          <Input
            id='business_email'
            name='business_email'
            type='email'
            autoComplete='email'
            value={data.email}
            onChange={(e) => set('email', e.target.value)}
            placeholder='business@example.com'
            aria-invalid={Boolean(errors.email)}
          />
          {errors.email ? (
            <FieldError>{errors.email}</FieldError>
          ) : (
            <FieldDescription>Optional — defaults to your personal email.</FieldDescription>
          )}
        </Field>

        <Field data-invalid={Boolean(errors.phone)}>
          <FieldLabel htmlFor='business_phone'>
            <Phone className='h-4 w-4 text-muted-foreground' aria-hidden />
            Business phone
          </FieldLabel>
          <Input
            id='business_phone'
            name='business_phone'
            type='tel'
            autoComplete='tel'
            value={data.phone}
            onChange={(e) => set('phone', e.target.value)}
            placeholder='+256 700 000 000'
            aria-invalid={Boolean(errors.phone)}
          />
          {errors.phone ? (
            <FieldError>{errors.phone}</FieldError>
          ) : (
            <FieldDescription>Optional — where your customers reach the business.</FieldDescription>
          )}
        </Field>

        <Field data-invalid={Boolean(errors.address)}>
          <FieldLabel htmlFor='business_address'>
            <MapPin className='h-4 w-4 text-muted-foreground' aria-hidden />
            Address
          </FieldLabel>
          <Textarea
            id='business_address'
            name='business_address'
            rows={2}
            value={data.address}
            onChange={(e) => set('address', e.target.value)}
            placeholder='Plot 123, Kampala Road, Kampala'
            aria-invalid={Boolean(errors.address)}
          />
          {errors.address && <FieldError>{errors.address}</FieldError>}
        </Field>
      </FieldGroup>

      <div className='flex flex-col-reverse gap-3 sm:flex-row'>
        <Button type='button' variant='outline' onClick={onBack} className='sm:flex-1'>
          Back
        </Button>
        <Button type='submit' className='sm:flex-1'>
          Continue to branches
        </Button>
      </div>
    </form>
  );
};
