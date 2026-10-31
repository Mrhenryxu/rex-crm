import { Card, Skeleton, Stack, Title } from '@mantine/core';
import { useNavigate, useParams } from 'react-router';
import { NotFoundPage } from '@/app/NotFoundPage';
import { ApiErrorAlert } from '@/components/ui/ApiErrorAlert';
import { updateContact } from '../api/contacts';
import { ContactForm } from '../components/ContactForm';
import { useContact } from '../hooks/useContacts';
import type { NoticeState } from '../hooks/useNotice';

export function ContactEditPage() {
    const { id = '' } = useParams();
    const navigate = useNavigate();
    const { data: contact, error, loading } = useContact(id);

    if (error?.isNotFound) {
        return <NotFoundPage message="This contact does not exist or has been deleted." />;
    }

    return (
        <Stack gap="lg" maw={720}>
            <Title order={1}>Edit contact</Title>
            {error && <ApiErrorAlert error={error} title="Could not load the contact" />}
            {loading && !contact && <Skeleton height={320} aria-label="Loading contact" />}
            {contact && (
                <Card withBorder padding="lg">
                    <ContactForm
                        submitLabel="Save changes"
                        defaultValues={{ first_name: contact.first_name, last_name: contact.last_name, email: contact.email ?? '', phone: contact.phone ?? '' }}
                        onCancel={() => navigate(`/contacts/${contact.id}`)}
                        onSubmit={async (input) => {
                            await updateContact(contact.id, input);
                            const state: NoticeState = { notice: 'Contact updated.' };
                            navigate(`/contacts/${contact.id}`, { state });
                        }}
                    />
                </Card>
            )}
        </Stack>
    );
}
