import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { FileSpreadsheet } from 'lucide-react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { toast } from 'sonner';

interface ExportButtonProps {
  type: 'products' | 'sales' | 'purchases' | 'customers' | 'suppliers';
  label?: string;
  withDateRange?: boolean;
}

export const ExportButton = ({ type, label = 'Export', withDateRange = false }: ExportButtonProps) => {
  const [open, setOpen] = useState(false);
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');

  const handleExport = async (format: 'csv' | 'xlsx') => {
    try {
      const token = localStorage.getItem('token');
      const params = new URLSearchParams({ format });
      if (withDateRange) {
        if (dateFrom) params.set('date_from', dateFrom);
        if (dateTo) params.set('date_to', dateTo);
      }
      const qs = params.toString();
      const url = `${import.meta.env.VITE_BASE_URL}/exports/${type}?${qs}`;

      const response = await fetch(url, {
        headers: { Authorization: `Bearer ${token}` },
      });

      if (!response.ok) throw new Error('Export failed');

      // The server answers csv or xlsx depending on the format we asked for. The blob
      // is attached verbatim; there is no client-side conversion anymore.
      const blob = await response.blob();
      const downloadUrl = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = downloadUrl;
      a.download = `${type}-${new Date().toISOString().split('T')[0]}.${format}`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(downloadUrl);
      document.body.removeChild(a);

      toast.success(`${label} downloaded successfully`);
      setOpen(false);
    } catch {
      toast.error('Export failed. Please try again.');
    }
  };

  return (
    <>
      <Button variant='outline' size='sm' onClick={() => setOpen(true)}>
        <FileSpreadsheet className='h-4 w-4' />
        {label}
      </Button>

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className='sm:max-w-md'>
          <DialogHeader>
            <DialogTitle>Export {type}</DialogTitle>
            <DialogDescription>
              {withDateRange
                ? 'Select a date range to export data.'
                : 'Export all data.'}
            </DialogDescription>
          </DialogHeader>

          {withDateRange && (
            <div className='grid gap-4 py-4'>
              <div className='grid grid-cols-2 gap-4'>
                <div className='space-y-2'>
                  <Label htmlFor='date_from'>From</Label>
                  <Input
                    id='date_from'
                    type='date'
                    value={dateFrom}
                    onChange={(e) => setDateFrom(e.target.value)}
                  />
                </div>
                <div className='space-y-2'>
                  <Label htmlFor='date_to'>To</Label>
                  <Input
                    id='date_to'
                    type='date'
                    value={dateTo}
                    onChange={(e) => setDateTo(e.target.value)}
                  />
                </div>
              </div>
            </div>
          )}

          <DialogFooter>
            <Button variant='outline' onClick={() => setOpen(false)}>
              Cancel
            </Button>
            <Button onClick={() => handleExport('csv')}>
              Export CSV
            </Button>
            <Button onClick={() => handleExport('xlsx')}>
              Export XLSX
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
};