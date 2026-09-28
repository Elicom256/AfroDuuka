import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { TaxCategoriesTable } from '../components/tax/TaxCategoriesTable';
import { TaxRatesTable } from '../components/tax/TaxRatesTable';
import { TaxPaymentsTable } from '../components/tax/TaxPaymentsTable';
import { TaxAnalytics } from '../components/tax/TaxAnalytics';

export const ExecutiveTaxPage = () => {
  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight'>Tax Management</h1>
        <p className='text-muted-foreground'>Manage tax categories, rates, and payments made to the revenue authority.</p>
      </div>
      <Tabs defaultValue='categories'>
        <TabsList>
          <TabsTrigger value='categories'>Categories</TabsTrigger>
          <TabsTrigger value='rates'>Rates</TabsTrigger>
          <TabsTrigger value='payments'>Payments</TabsTrigger>
          <TabsTrigger value='analytics'>Analytics</TabsTrigger>
        </TabsList>
        <TabsContent value='categories' className='mt-4'>
          <TaxCategoriesTable />
        </TabsContent>
        <TabsContent value='rates' className='mt-4'>
          <TaxRatesTable />
        </TabsContent>
        <TabsContent value='payments' className='mt-4'>
          <TaxPaymentsTable />
        </TabsContent>
        <TabsContent value='analytics' className='mt-4'>
          <TaxAnalytics />
        </TabsContent>
      </Tabs>
    </div>
  );
};