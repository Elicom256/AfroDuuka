import { useState, type ReactNode } from 'react';
import { Loader2 } from 'lucide-react';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog';

/**
 * A delete control that asks first.
 *
 * Deleting is the one action in this product that cannot be undone by retyping, and
 * these buttons sit in table rows a mis-click reaches easily. 27 of them fired the
 * mutation directly from the trigger (checked.md P1-19).
 *
 * This exists so a confirmation is the default rather than something each screen has to
 * remember. Two screens already had a correct hand-rolled AlertDialog, which is the shape
 * this generalises: a trigger, a question naming what is about to go, and a Delete
 * button that shows a spinner and disables itself while the call is in flight.
 *
 * `onConfirm` is responsible for its own error handling — callers already toast on
 * failure, and swallowing the rejection here would hide that.
 */
export const ConfirmDeleteButton = ({
  onConfirm,
  isDeleting = false,
  trigger,
  title = 'Delete this record?',
  description = 'This cannot be undone.',
  confirmLabel = 'Delete',
  onClick,
}: {
  onConfirm: () => void | Promise<unknown>;
  isDeleting?: boolean;
  trigger: ReactNode;
  title?: string;
  description?: string;
  confirmLabel?: string;
  /** Escape hatch for triggers that also stop row-click propagation. */
  onClick?: (event: React.MouseEvent) => void;
}) => {
  // The dialog is controlled so the destructive action is never the thing that closes
  // it: on a failed delete it stays open with the error already toasted, so the person
  // is not left wondering whether it worked.
  const [open, setOpen] = useState(false);

  const handleConfirm = async () => {
    try {
      await onConfirm();
      setOpen(false);
    } catch {
      // Left open on purpose; the caller's toast carries the reason.
    }
  };

  return (
    <AlertDialog open={open} onOpenChange={setOpen}>
      <AlertDialogTrigger asChild>
        <span onClick={onClick} className='inline-flex'>
          {trigger}
        </span>
      </AlertDialogTrigger>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>{title}</AlertDialogTitle>
          <AlertDialogDescription>{description}</AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction onClick={handleConfirm} disabled={isDeleting}>
            {isDeleting && <Loader2 className='h-4 w-4 mr-2 animate-spin' />}
            {confirmLabel}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
};
