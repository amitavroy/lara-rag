import { Head, setLayoutProps } from '@inertiajs/react';
import Heading from '@/components/heading';
import { edit, index, update } from '@/routes/collections';
import type { Collection } from '@/types';
import CollectionForm from './collection-form';

export default function EditCollection({
    collection,
}: {
    collection: Collection;
}) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Collections', href: index() },
            { title: 'Edit', href: edit(collection.ulid) },
        ],
    });

    return (
        <>
            <Head title="Edit collection" />
            <div className="space-y-6 p-4">
                <Heading
                    title="Edit collection"
                    description="Change the name or description"
                />
                <CollectionForm
                    formProps={update.form(collection.ulid)}
                    collection={collection}
                    submitLabel="Save changes"
                />
            </div>
        </>
    );
}
