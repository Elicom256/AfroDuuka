import { Building2, Globe, Mail, MapPin, Phone } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
import { useGetPublicBusinessCategoriesQuery } from '@/app/store/features/business/setup/onboardingQuery';
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

  const countries = useMemo(() => countriesData?.data ?? [], [countriesData]);
  const categories = useMemo(() => categoryData ?? [], [categoryData]);

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
              {categories.map((category) => (
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
          <Input id='type-to-filter-countries'
            type='text'
            value={countryQuery}
            onChange={(e) => setCountryQuery(e.target.value)}
            placeholder='Type to filter countries'
            aria-label='Filter countries'
          />
          <Select value={data.country_id} onValueChange={(value) => set('country_id', value)}>
            <SelectTrigger id='business_country' className='w-full' aria-invalid={Boolean(errors.country_id)}>
              <SelectValue placeholder={isLoadingCountries ? 'Loading countries…' : 'Select your country'} />
            </SelectTrigger>
            <SelectContent>
              {filteredCountries.length === 0 ? (
                <SelectItem value='none' disabled>
                  No country matches “{countryQuery}”
                </SelectItem>
              ) : (
                filteredCountries.map((country) => (
                  <SelectItem key={country.id} value={String(country.id)}>
                    <span className='flex items-center gap-2'>
                      <span aria-hidden>{country.flag_emoji}</span>
                      <span>{country.name}</span>
                    </span>
                  </SelectItem>
                ))
              )}
            </SelectContent>
          </Select>
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
