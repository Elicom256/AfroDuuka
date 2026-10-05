import { Link, useParams } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { useReceiptQuery, useDownloadReceiptPdfMutation } from '@/app/store/features/branch/receipts/receiptsQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { ArrowLeft, Download, ExternalLink } from 'lucide-react';
import { LoadingState } from '@/utils/LoadingState';
import { ReceiptView } from './ReceiptView';

export const ReceiptDetail = () => {
  const { id } = useParams<{ id: string }>();
  const { data: receiptData, isLoading } = useReceiptQuery(String(id), { skip: !id });
  const [downloadReceiptPdf, { isLoading: downloading }] = useDownloadReceiptPdfMutation();

  if (isLoading) return <PageLoadingState />;

  const receipt = receiptData?.receipt || receiptData;

  // Checked on the id rather than for truthiness. An endpoint that answers with an empty
  // object is truthy, and it used to slip past this guard and render a receipt-shaped
  // card with no receipt number, no items and a zero total — which reads as a real sale
  // worth nothing rather than as a missing receipt.
  if (!receipt?.id) {
    return (
      <div className='flex items-center justify-center h-64'>
        <p className='text-muted-foreground'>Receipt not found</p>
      </div>
    );
  }

  const handleDownloadPdf = async () => {
    try {
      const result = await downloadReceiptPdf(receipt.id).unwrap();
      const byteCharacters = atob(result.pdf);
      const byteNumbers = new Array(byteCharacters.length);
      for (let i = 0; i < byteCharacters.length; i++) {
        byteNumbers[i] = byteCharacters.charCodeAt(i);
      }
      const byteArray = new Uint8Array(byteNumbers);
      const blob = new Blob([byteArray], { type: 'application/pdf' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = result.filename;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    } catch {
      console.error('Failed to download receipt');
    }
  };

  const handleOpenPdf = async () => {
    try {
      const result = await downloadReceiptPdf(receipt.id).unwrap();
      const byteCharacters = atob(result.pdf);
      const byteNumbers = new Array(byteCharacters.length);
      for (let i = 0; i < byteCharacters.length; i++) {
        byteNumbers[i] = byteCharacters.charCodeAt(i);
      }
      const byteArray = new Uint8Array(byteNumbers);
      const blob = new Blob([byteArray], { type: 'application/pdf' });
      const url = URL.createObjectURL(blob);
      window.open(url, '_blank');
    } catch {
      console.error('Failed to open receipt');
    }
  };


  return (
    <div className='space-y-6'>
      <div className='flex items-center justify-between'>
        <Link to='../receipts' className='flex items-center gap-2 text-blue-400 hover:underline'>
          <ArrowLeft className='h-4 w-4' />
          <span>Back to Receipts</span>
        </Link>
        <div className='flex items-center gap-2'>
          <Button variant='outline' size='sm' className='gap-2' onClick={handleOpenPdf}>
            <ExternalLink className='h-4 w-4' />
            Open PDF
          </Button>
          <Button size='sm' className='gap-2' onClick={handleDownloadPdf}>
            {downloading ? (
              <LoadingState />
            ) : (
              <>
                <Download className='h-4 w-4' />
                Download PDF
              </>
            )}
          </Button>
        </div>
      </div>

      <ReceiptView receipt={receipt} />
    </div>
  );
};
