import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { SectionHeader } from './components/SectionHeader';

export const TermsOfService = () => {
  return (
    <div className='mx-auto max-w-4xl space-y-8 px-4 py-12'>
      <SectionHeader
        badge='Legal'
        title='Terms of Service'
        description='Last updated: September 2026'
      />

      <Card>
        <CardHeader>
          <CardTitle>1. Acceptance of Terms</CardTitle>
        </CardHeader>
        <CardContent className='prose prose-sm dark:prose-invert max-w-none'>
          <p>
            By accessing and using DuukaFlow, you accept and agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use our service.
          </p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>2. Description of Service</CardTitle>
        </CardHeader>
        <CardContent className='prose prose-sm dark:prose-invert max-w-none'>
          <p>
            DuukaFlow is a cloud-based inventory management system designed for businesses in Uganda and Africa. The service includes stock tracking, sales management, financial reporting, and related features.
          </p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>3. User Accounts</CardTitle>
        </CardHeader>
        <CardContent className='prose prose-sm dark:prose-invert max-w-none'>
          <ul className='list-disc space-y-2 pl-6'>
            <li>You must provide accurate and complete information when creating an account.</li>
            <li>You are responsible for maintaining the confidentiality of your account credentials.</li>
            <li>You must notify us immediately of any unauthorized use of your account.</li>
            <li>We reserve the right to suspend or terminate accounts that violate these terms.</li>
          </ul>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>4. Subscription and Payment</CardTitle>
        </CardHeader>
        <CardContent className='prose prose-sm dark:prose-invert max-w-none'>
          <ul className='list-disc space-y-2 pl-6'>
            <li>Paid plans are billed in advance on a monthly or yearly basis.</li>
            <li>Subscriptions automatically renew unless cancelled before the renewal date.</li>
            <li>Refunds are available within 7 days of initial purchase.</li>
            <li>Prices are subject to change with 30 days notice.</li>
          </ul>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>5. Acceptable Use</CardTitle>
        </CardHeader>
        <CardContent className='prose prose-sm dark:prose-invert max-w-none'>
          <p>You agree not to:</p>
          <ul className='list-disc space-y-2 pl-6'>
            <li>Use the service for any unlawful purpose</li>
            <li>Attempt to gain unauthorized access to our systems</li>
            <li>Interfere with or disrupt the service</li>
            <li>Share your account with others</li>
            <li>Reverse engineer or attempt to extract source code</li>
          </ul>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>6. Data Ownership</CardTitle>
        </CardHeader>
        <CardContent className='prose prose-sm dark:prose-invert max-w-none'>
          <p>
            You retain all rights to your data. We do not claim ownership of any data you input into the service. You may export or delete your data at any time.
          </p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>7. Limitation of Liability</CardTitle>
        </CardHeader>
        <CardContent className='prose prose-sm dark:prose-invert max-w-none'>
          <p>
            DuukaFlow is provided "as is" without warranties of any kind. We shall not be liable for any indirect, incidental, or consequential damages arising from your use of the service.
          </p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>8. Changes to Terms</CardTitle>
        </CardHeader>
        <CardContent className='prose prose-sm dark:prose-invert max-w-none'>
          <p>
            We may update these terms from time to time. We will notify users of significant changes via email or in-app notification. Continued use of the service after changes constitutes acceptance.
          </p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>9. Contact</CardTitle>
        </CardHeader>
        <CardContent className='prose prose-sm dark:prose-invert max-w-none'>
          <p>
            For questions about these terms, contact us at legal@duukaflow.com or +256 731 794401.
          </p>
        </CardContent>
      </Card>
    </div>
  );
};
