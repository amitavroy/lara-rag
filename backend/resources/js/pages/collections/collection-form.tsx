import { Form, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/collections';
import type { Collection } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

type Props = {
    formProps: RouteFormDefinition<'post'>;
    collection?: Collection;
    submitLabel: string;
};

export default function CollectionForm({
    formProps,
    collection,
    submitLabel,
}: Props) {
    return (
        <Form {...formProps} className="max-w-xl space-y-6">
            {({ processing, errors }: { processing: boolean; errors: Record<string, string> }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            defaultValue={collection?.name}
                            required
                            autoFocus
                            placeholder="Collection name"
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Description</Label>
                        <textarea
                            id="description"
                            name="description"
                            rows={4}
                            defaultValue={collection?.description ?? ''}
                            placeholder="Optional description"
                            className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={processing}>
                            {submitLabel}
                        </Button>
                        <Button variant="ghost" asChild>
                            <Link href={index()}>Cancel</Link>
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
