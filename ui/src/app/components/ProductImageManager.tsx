import { useRef } from 'react';
import { ImagePlus, Loader2, Trash2, Camera } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  useGetProductAttachmentsQuery,
  useUploadProductAttachmentMutation,
  useDeleteProductAttachmentMutation,
} from '@/app/store/features/branch/attachments/attachmentsQuery';

export const ProductImageManager = ({ productId }: { productId: string | number }) => {
  const fileRef = useRef<HTMLInputElement>(null);
  const { data: attachmentsData, isLoading } = useGetProductAttachmentsQuery(productId);
  const [upload, { isLoading: isUploading }] = useUploadProductAttachmentMutation();
  const [remove, { isLoading: isDeleting }] = useDeleteProductAttachmentMutation();

  const attachments = attachmentsData?.attachments ?? [];
  const cover = attachments.find((a: any) => a.kind === 'image');

  const handleFile = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    e.target.value = '';
    if (!file) return;
    if (!file.type.startsWith('image/')) {
      toast.error('Please select an image file');
      return;
    }
    try {
      const res = await upload({ productId, file }).unwrap();
      toast.success(res?.message || 'Image uploaded');
    } catch {
      toast.error('Failed to upload image');
    }
  };

  const handleDelete = async (attachmentId: number) => {
    if (!window.confirm('Delete this image?')) return;
    try {
      await remove({ productId, attachmentId }).unwrap();
      toast.success('Image deleted');
    } catch {
      toast.error('Failed to delete image');
    }
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className='flex items-center gap-2'>
          <Camera className='h-4 w-4' /> Product Images
        </CardTitle>
      </CardHeader>
      <CardContent className='space-y-4'>
        <input ref={fileRef} type='file' accept='image/*' className='hidden' onChange={handleFile} />

        {isLoading ? (
          <div className='flex items-center gap-2 text-sm text-muted-foreground'>
            <Loader2 className='h-4 w-4 animate-spin' /> Loading images...
          </div>
        ) : attachments.length === 0 ? (
          <div className='flex flex-col items-center justify-center gap-3 border border-dashed rounded-lg py-10 text-center text-sm text-muted-foreground'>
            <ImagePlus className='h-6 w-6' />
            <p>No image yet. The first image becomes the product cover.</p>
            <Button type='button' variant='outline' size='sm' onClick={() => fileRef.current?.click()} disabled={isUploading}>
              {isUploading ? <Loader2 className='h-4 w-4 animate-spin' /> : <ImagePlus className='h-4 w-4' />}
              Upload Image
            </Button>
          </div>
        ) : (
          <>
            {cover && (
              <div className='overflow-hidden rounded-lg border'>
                <img src={cover.url} alt={cover.original_name || 'Product cover'} className='max-h-64 w-full object-contain bg-muted' />
              </div>
            )}
            <div className='flex flex-wrap gap-3'>
              {attachments.map((a: any) => (
                <div key={a.id} className='relative group'>
                  <img src={a.url} alt={a.original_name || 'attachment'} className='h-20 w-20 rounded-lg border object-cover' />
                  <Button
                    type='button'
                    variant='destructive'
                    size='icon'
                    className='absolute -top-2 -right-2 h-6 w-6 opacity-0 group-hover:opacity-100 transition-opacity'
                    onClick={() => handleDelete(a.id)}
                    disabled={isDeleting}
                  >
                    <Trash2 className='h-3 w-3' />
                  </Button>
                </div>
              ))}
            </div>
            <Button type='button' variant='outline' size='sm' onClick={() => fileRef.current?.click()} disabled={isUploading}>
              {isUploading ? <Loader2 className='h-4 w-4 animate-spin' /> : <ImagePlus className='h-4 w-4' />}
              Add Image
            </Button>
          </>
        )}
      </CardContent>
    </Card>
  );
};