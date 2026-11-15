import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { create, index, store } from '@/routes/collections';
import CollectionForm from './collection-form';

export default function CreateCollection() {
    return (
        <>
            <Head title="New collection" />
            <div className="space-y-6 p-4">
                <Heading
                    title="New collection"
                    description="Group the documents you upload under a name"
                />
                <CollectionForm
                    formProps={store.form()}
                    submitLabel="Create collection"
                />
            </div>
        </>
    );
}

CreateCollection.layout = {
    breadcrumbs: [
        { title: 'Collections', href: index() },
        { title: 'New', href: create() },
    ],
};
