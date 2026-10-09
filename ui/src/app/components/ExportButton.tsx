import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Download, FileSpreadsheet } from 'lucide-react';
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
import * as XLSX from 'xlsx';

interface ExportButtonProps {
  type: 'products' | 'sales' | 'purchases' | 'customers' | 'suppliers';
  label?: string;
  withDateRange?: boolean;
}

export const ExportButton = ({ type, label = 'Export', withDateRange = false }: ExportButtonProps) => {
  const [open, setOpen] = useState(false);
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [format, setFormat] = useState<'csv' | 'xlsx'>('csv');

  const handleExport = async () => {
    try {
      const token = localStorage.getItem('token');
      const params = new URLSearchParams();
      if (withDateRange) {
        if (dateFrom) params.set('date_from', dateFrom);
        if (dateTo) params.set('date_to', dateTo);
      }
      const qs = params.toString();
      const url = `${import.meta.env.VITE_BASE_URL}/exports/${type}${qs ? `?${qs}` : ''}`;

      const response = await fetch(url, {
        headers: { Authorization: `Bearer ${token}` },
      });

      if (!response.ok) throw new Error('Export failed');

      const blob = await response.blob();

      if (format === 'csv') {
        const downloadUrl = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = downloadUrl;
        a.download = `${type}-${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(downloadUrl);
        document.body.removeChild(a);
      } else {
        // XLSX: read the CSV text, then generate a workbook via SheetJS
        const text = await response.text();
        const rows = text.trim().split('\n').map((r) => r.split(','));
        if (rows.length === 0) throw new Error('Empty export data');
        const headers = rows.shift()!;
        const allRows = [headers, ...rows];
        const sheet = XLSX.utils.aoa_to_sheet(allRows);
        const workbook: any = { SheetNames: ['data'], Sheets: { data: sheet } };
        const base64 = XLSX.write(workbook, { bookType: 'xlsx', type: 'base64' });
        const downloadUrl = `data:application/octet-stream;base64,${base64}`;
        const a = document.createElement('a');
        a.href = downloadUrl;
        a.download = `${type}-${new Date().toISOString().split('T')[0]}.xlsx`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
      }

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
            <Button onClick={() => setFormat('csv')}>
              Export CSV
            </Button>
            <Button onClick={() => setFormat('xlsx')}>
              Export XLSX
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
};