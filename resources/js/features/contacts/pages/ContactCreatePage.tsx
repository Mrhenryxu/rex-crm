import { Card, Stack, Title } from '@mantine/core';
import { useNavigate } from 'react-router';
import { createContact } from '../api/contacts';
import { ContactForm } from '../components/ContactForm';
import type { NoticeState } from '../hooks/useNotice';

export function ContactCreatePage() {
    const navigate = useNavigate();

    return (
        <Stack gap="lg" maw={720}>
            <Title order={1}>New contact</Title>
            <Card withBorder padding="lg">
                <ContactForm
                    submitLabel="Create contact"
                    onCancel={() => navigate('/contacts')}
                    onSubmit={async (input) => {
                        const { contact, created } = await createContact(input);
                        const state: NoticeState = {
                            // POST is an upsert keyed on email, so say so when an existing contact was updated.
                            notice: created ? 'Contact created.' : 'A contact with this email already existed, so it was updated.',
                        };
                        navigate(`/contacts/${contact.id}`, { state });
                    }}
                />
            </Card>
        </Stack>
    );
}
