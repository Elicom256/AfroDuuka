import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { toast } from 'sonner';
import { Clock } from 'lucide-react';

type Props = {
  workerId: string | number;
  onSuccess?: () => void;
};

export const AddAttendanceForm: React.FC<Props> = ({ onSuccess }) => {
  const [formData, setFormData] = useState({
    check_out: '',
    session: 'morning' as const,
    status: 'present' as const,
    remarks: '',
  });

  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    try {
      // await createAttendance(payload).unwrap();

      toast.success('Attendance recorded successfully!');

      setFormData({
        check_out: '',
        session: 'morning',
        status: 'present',
        remarks: '',
      });

      onSuccess?.();
    } catch (error: any) {
      toast.error(error?.data?.message || 'Failed to record attendance');
      console.error(error);
    } finally {
      setIsSubmitting(false);
    }
  };

  const statuses = ['present', 'absent', 'late', 'excused'];

  return (
    <Card>
      <CardHeader>
        <CardTitle className='flex items-center gap-2'>
          <Clock className='h-5 w-5' />
          Record Attendance
        </CardTitle>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className='space-y-4'>
          <div className='grid grid-cols-1 md:grid-cols-2 gap-4'>
            <div className='space-y-2'>
              <Label>Session</Label>
              <Select value={formData.session} onValueChange={(v: any) => setFormData({ ...formData, session: v })}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value='morning'>Morning</SelectItem>
                  <SelectItem value='afternoon'>Afternoon</SelectItem>
                  <SelectItem value='evening'>Evening</SelectItem>
                  <SelectItem value='night'>Night</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className='space-y-2'>
              <Label>Status</Label>
              <Select value={formData.status} onValueChange={(v: any) => setFormData({ ...formData, status: v })}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {statuses.map((s) => (
                    <SelectItem key={s} value={s} className='capitalize'>
                      {s}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {(formData.status === 'present' || formData.status === 'late') && (
              <div className='space-y-2 md:col-span-2'>
                <Label htmlFor='check_out'>Check Out Time (Optional)</Label>
                <Input
                  id='check_out'
                  type='datetime-local'
                  value={formData.check_out}
                  onChange={(e) => setFormData({ ...formData, check_out: e.target.value })}
                />
              </div>
            )}
          </div>

          <div className='space-y-2'>
            <Label htmlFor='remarks'>Remarks</Label>
            <Textarea
              id='remarks'
              placeholder='Traffic, medical, early leave, etc.'
              value={formData.remarks}
              onChange={(e) => setFormData({ ...formData, remarks: e.target.value })}
              rows={3}
            />
          </div>

          <Button type='submit' className='w-full' disabled={isSubmitting}>
            {isSubmitting ? 'Recording...' : 'Record Attendance'}
          </Button>
        </form>
      </CardContent>
    </Card>
  );
};
