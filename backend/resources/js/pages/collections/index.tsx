import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { create, destroy, edit, index, toggleActive } from '@/routes/collections';
import type { Collection } from '@/types';

export default function CollectionsIndex({
    collections,
}: {
    collections: Collection[];
}) {
    const [pendingDelete, setPendingDelete] = useState<Collection | null>(
        null,
    );
    const [deleting, setDeleting] = useState(false);

    const confirmDelete = () => {
        if (!pendingDelete) {
            return;
        }

        router.delete(destroy(pendingDelete.ulid), {
            preserveScroll: true,
            onStart: () => setDeleting(true),
            onFinish: () => {
                setDeleting(false);
                setPendingDelete(null);
            },
        });
    };

    return (
        <>
            <Head title="Collections" />
            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Collections"
                        description="Group your documents and switch them on or off"
                    />
                    <Button asChild>
                        <Link href={create()}>New collection</Link>
                    </Button>
                </div>

                {collections.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-10 text-center text-sm text-muted-foreground">
                        You have no collections yet. Create one to get started.
                    </div>
                ) : (
                    <ul className="divide-y rounded-xl border">
                        {collections.map((collection) => (
                            <li
                                key={collection.ulid}
                                className="flex items-center justify-between gap-4 p-4"
                            >
                                <div className="min-w-0 space-y-1">
                                    <div className="flex items-center gap-2">
                                        <span className="truncate font-medium">
                                            {collection.name}
                                        </span>
                                        <Badge
                                            variant={
                                                collection.is_active
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {collection.is_active
                                                ? 'Active'
                                                : 'Inactive'}
                                        </Badge>
                                    </div>
                                    {collection.description && (
                                        <p className="truncate text-sm text-muted-foreground">
                                            {collection.description}
                                        </p>
                                    )}
                                </div>
                                <div className="flex shrink-0 items-center gap-2">
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={edit(collection.ulid)}>
                                            Edit
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            router.patch(
                                                toggleActive(collection.ulid),
                                                {},
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        {collection.is_active
                                            ? 'Deactivate'
                                            : 'Activate'}
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        onClick={() =>
                                            setPendingDelete(collection)
                                        }
                                    >
                                        Delete
                                    </Button>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <ConfirmDeleteDialog
                open={pendingDelete !== null}
                onOpenChange={(open) => !open && setPendingDelete(null)}
                title="Delete collection?"
                description={`"${pendingDelete?.name ?? ''}" will be removed. This cannot be undone.`}
                onConfirm={confirmDelete}
                processing={deleting}
            />
        </>
    );
}

CollectionsIndex.layout = {
    breadcrumbs: [{ title: 'Collections', href: index() }],
};
