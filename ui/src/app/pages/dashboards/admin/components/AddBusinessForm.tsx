'use client';

import React, { useState } from 'react';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { toast } from 'sonner';

import { useRegisterBusinessMutation } from '@/app/store/features/business/setup/businessQuery';
import { useGetBusinessCategoriesQuery } from '@/app/store/features/business/setup/businessQuery';

export const AddBusinessForm: React.FC = () => {
  const [businessData, setBusinessData] = useState({
    name: '',
    business_category_id: '',
  });

  const [registerBusiness, { isLoading }] = useRegisterBusinessMutation();

  const { data: categories, isLoading: isCategoriesLoading } = useGetBusinessCategoriesQuery();
  console.log('categories data==>', categories);
  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setBusinessData((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e: React.SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();

    try {
      const res = await registerBusiness({
        ...businessData,
        business_category_id: Number(businessData.business_category_id),
      }).unwrap();

      toast.success(res.message || 'Business registered successfully');

      setBusinessData({
        name: '',
        business_category_id: '',
      });
      return (window.location.href = '/admin');
    } catch (error: any) {
      toast.error(error?.data?.message || 'Failed to register business');
    }
  };

  return (
    <div className='min-h-screen flex items-center justify-center bg-gray-950 px-4'>
      <Card className='w-full max-w-md bg-gray-900 border-gray-800 text-white shadow-xl'>
        <CardHeader>
          <CardTitle>Create a New Business</CardTitle>
          <CardDescription className='text-gray-400'>Fill in the details to register your business</CardDescription>
        </CardHeader>

        <form onSubmit={handleSubmit}>
          <CardContent className='space-y-4'>
            {/* Business Name */}
            <div className='space-y-2'>
              <Label htmlFor='name' className='text-gray-300'>
                Business Name
              </Label>
              <Input
                id='name'
                name='name'
                type='text'
                value={businessData.name}
                onChange={handleChange}
                placeholder='Enter business name'
                required
                className='bg-gray-800 border-gray-700 text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500'
              />
            </div>

            {/* Business Category Select */}
            <div className='space-y-2'>
              <Label className='text-gray-300'>Business Category</Label>

              <Select
                value={businessData.business_category_id}
                onValueChange={(value) =>
                  setBusinessData((prev) => ({
                    ...prev,
                    business_category_id: value,
                  }))
                }
              >
                <SelectTrigger className='bg-gray-800 border-gray-700 text-white'>
                  <SelectValue placeholder='Select a category' />
                </SelectTrigger>

                <SelectContent className='bg-gray-900 border-gray-800 text-white '>
                  {isCategoriesLoading ? (
                    <div className='p-2 text-sm text-gray-400'>Loading categories...</div>
                  ) : categories?.length ? (
                    categories.map((cat: any) => (
                      <SelectItem key={cat.id} value={String(cat.id)} className='focus:bg-gray-800'>
                        {cat.name}
                      </SelectItem>
                    ))
                  ) : (
                    <div className='p-2 text-sm text-gray-400'>No categories found</div>
                  )}
                </SelectContent>
              </Select>
            </div>
          </CardContent>

          <CardFooter>
            <Button
              type='submit'
              className='w-full bg-blue-600 hover:bg-blue-700 text-white'
              disabled={isLoading || !businessData.business_category_id}
            >
              {isLoading ? 'Registering...' : 'Register Business'}
            </Button>
          </CardFooter>
        </form>
      </Card>
    </div>
  );
};
