import { Building2, Mail, Phone, MapPin, Globe } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { useCountriesQuery } from '@/app/store/features/countries/countriesQuery';
import { useGetPublicBusinessCategoriesQuery } from '@/app/store/features/business/setup/onboardingQuery';
import type { BusinessData } from './types';

interface BusinessSetupProps {
  data: BusinessData;
  onChange: (data: BusinessData) => void;
  onNext: () => void;
  onBack: () => void;
}

export const BusinessSetup: React.FC<BusinessSetupProps> = ({
  data,
  onChange,
  onNext,
  onBack,
}) => {
  const { data: countriesData } = useCountriesQuery();
  const { data: businessCategories } = useGetPublicBusinessCategoriesQuery();
  const countries = countriesData?.data || [];
  const categories = businessCategories?.data || [];

  const handleChange = (field: keyof BusinessData, value: string) => {
    onChange({ ...data, [field]: value });
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onNext();
  };

  return (
    <form onSubmit={handleSubmit} className='space-y-5'>
      <div className='space-y-2'>
        <Label htmlFor='business_name'>
          <Building2 className='h-4 w-4 text-muted-foreground' />
          Business name
        </Label>
        <Input
          id='business_name'
          name='business_name'
          type='text'
          autoComplete='organization'
          value={data.name}
          onChange={(e) => handleChange('name', e.target.value)}
          placeholder="Jane's Shop"
          required
        />
      </div>

      <div className='space-y-2'>
        <Label htmlFor='business_category_id'>Business category</Label>
        <Select
          value={data.business_category_id}
          onValueChange={(value) => handleChange('business_category_id', value)}
        >
          <SelectTrigger id='business_category_id' className='w-full'>
            <SelectValue placeholder='Select your business category' />
          </SelectTrigger>
          <SelectContent>
            {categories.map((category: any) => (
              <SelectItem key={category.id} value={String(category.id)}>
                {category.name}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      <div className='space-y-2'>
        <Label htmlFor='business_country'>
          <Globe className='h-4 w-4 text-muted-foreground' />
          Country
        </Label>
        <Select
          value={data.country_id}
          onValueChange={(value) => handleChange('country_id', value)}
        >
          <SelectTrigger className='w-full'>
            <SelectValue placeholder='Select business country' />
          </SelectTrigger>
          <SelectContent>
            {countries.map((country: any) => (
              <SelectItem key={country.id} value={String(country.id)}>
                <span className='flex items-center gap-2'>
                  <span className='text-lg'>{country.flag_emoji}</span>
                  <span>{country.name}</span>
                </span>
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      <div className='space-y-2'>
        <Label htmlFor='business_email'>
          <Mail className='h-4 w-4 text-muted-foreground' />
          Business email
        </Label>
        <Input
          id='business_email'
          name='business_email'
          type='email'
          autoComplete='email'
          value={data.email}
          onChange={(e) => handleChange('email', e.target.value)}
          placeholder='business@example.com'
          required
        />
      </div>

      <div className='space-y-2'>
        <Label htmlFor='business_phone'>
          <Phone className='h-4 w-4 text-muted-foreground' />
          Business phone
        </Label>
        <Input
          id='business_phone'
          name='business_phone'
          type='tel'
          autoComplete='tel'
          value={data.phone}
          onChange={(e) => handleChange('phone', e.target.value)}
          placeholder='+256 700 000 000'
          required
        />
      </div>

      <div className='space-y-2'>
        <Label htmlFor='business_address'>
          <MapPin className='h-4 w-4 text-muted-foreground' />
          Address
        </Label>
        <Textarea
          id='business_address'
          name='business_address'
          value={data.address}
          onChange={(e) => handleChange('address', e.target.value)}
          placeholder='Plot 123, Kampala Road, Kampala'
          required
          rows={2}
        />
      </div>

      <div className='flex gap-3'>
        <Button type='button' variant='outline' onClick={onBack} className='flex-1'>
          Back
        </Button>
        <Button type='submit' className='flex-1'>
          Continue
        </Button>
      </div>
    </form>
  );
};
