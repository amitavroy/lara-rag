import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description: string;
    onConfirm: () => void;
    processing?: boolean;
};

export default function ConfirmDeleteDialog({
    open,
    onOpenChange,
    title,
    description,
    onConfirm,
    processing = false,
}: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>
                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button
                        variant="destructive"
                        disabled={processing}
                        onClick={onConfirm}
                    >
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
