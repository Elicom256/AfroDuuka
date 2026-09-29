import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { SectionHeader } from './components/SectionHeader';

export const PrivacyPolicy = () => {
  return (
    <div className='mx-auto max-w-4xl space-y-8 px-4 py-12'>
      <SectionHeader
        badge='Legal'
        title='Privacy Policy'
        description='Last updated: September 2026'
      />

      <Card>
        <CardHeader>
          <CardTitle>1. Information We Collect</CardTitle>
        </CardHeader>
        <CardContent className='prose prose-sm dark:prose-invert max-w-none'>
          <p>We collect information you provide directly to us:</p>
          <ul className='list-disc space-y-2 pl-6'>
            <li>Account information (name, email, phone number)</li>
            <li>Business information (business name, address, branches)</li>
            <li>Transaction data (sales, purchases, expenses)</li>
            <li>Usage data (features used, actions taken)</li>
          </ul>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>2. How We Use Your Information</CardTitle>
        </CardHeader>
        <CardContent className='prose prose-sm dark:prose-invert max-w-none'>
          <ul className='list-disc space-y-2 pl-6'>
            <li>To provide and maintain our service</li>
            <li>To process transactions and send related information</li>
            <li>To send technical notices and support messages</li>
            <li>To respond to your comments and questions</li>
            <li>To improve our service and develop new features</li>
          </ul>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>3. Information Sharing</CardTitle>
        </CardHeader>
        <CardContent className='prose prose-sm dark:prose-invert max-w-none'>
          <p>We do not sell your personal information. We may share information with:</p>
          <ul className='list-disc space-y-2 pl-6'>
            <li>Service providers who assist in operating our platform</li>
            <li>Legal authorities when required by law</li>
            <li>Payment processors to complete transactions</li>
          </ul>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>4. Data Security</CardTitle>
        </CardHeader>
        <CardContent className='prose prose-sm dark:prose-invert max-w-none'>
          <p>
            We implement appropriate security measures to protect your data including encryption in transit and at rest, regular security assessments, and access controls.
          </p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>5. Data Retention</CardTitle>
        </CardHeader>
        <CardContent className='prose prose-sm dark:prose-invert max-w-none'>
          <p>
            We retain your data for as long as your account is active. You may request deletion of your data at any time. Some information may be retained for legal compliance purposes.
          </p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>6. Your Rights</CardTitle>
        </CardHeader>
        <CardContent className='prose prose-sm dark:prose-invert max-w-none'>
          <ul className='list-disc space-y-2 pl-6'>
            <li>Access your personal data</li>
            <li>Correct inaccurate data</li>
            <li>Request deletion of your data</li>
            <li>Export your data in a portable format</li>
            <li>Opt out of marketing communications</li>
          </ul>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>7. Cookies</CardTitle>
        </CardHeader>
        <CardContent className='prose prose-sm dark:prose-invert max-w-none'>
          <p>
            We use essential cookies to maintain your session and preferences. We do not use tracking cookies for advertising purposes.
          </p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>8. Changes to This Policy</CardTitle>
        </CardHeader>
        <CardContent className='prose prose-sm dark:prose-invert max-w-none'>
          <p>
            We may update this privacy policy from time to time. We will notify you of significant changes via email or in-app notification.
          </p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>9. Contact Us</CardTitle>
        </CardHeader>
        <CardContent className='prose prose-sm dark:prose-invert max-w-none'>
          <p>
            For privacy-related inquiries, contact us at privacy@duukaflow.com or +256 731 794401.
          </p>
        </CardContent>
      </Card>
    </div>
  );
};
