import { Alert, Button, Card, Group, LoadingOverlay, Modal, Skeleton, Stack, Text, Title } from '@mantine/core';
import { useDisclosure } from '@mantine/hooks';
import { IconUserPlus } from '@tabler/icons-react';
import { useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router';
import { ApiErrorAlert } from '@/components/ui/ApiErrorAlert';
import { ApiError, toApiError } from '@/lib/api-client';
import { createContact, placeCall } from '../api/contacts';
import { CallResultAlert } from '../components/CallResultAlert';
import { ContactForm } from '../components/ContactForm';
import { ListPagination, SortSelect } from '../components/ContactListControls';
import { ContactSearchForm } from '../components/ContactSearchForm';
import { ContactsTable } from '../components/ContactsTable';
import { useContactList } from '../hooks/useContacts';
import { useNotice, type NoticeState } from '../hooks/useNotice';
import type { Contact, ContactCall, ContactFilters, ContactInput, ContactListParams } from '../types/contact';
import { DEFAULT_LIST_PARAMS, filtersOf, parseListParams, toSearchParams } from '../utils/listParams';

export function ContactsListPage() {
    const [searchParams, setSearchParams] = useSearchParams();
    const params = parseListParams(searchParams);
    const { data, error, loading } = useContactList(params);
    const [notice, dismissNotice] = useNotice();
    const navigateTo = useNavigate();
    const [creating, { open: openCreate, close: closeCreate }] = useDisclosure(false);
    const [callingId, setCallingId] = useState<string | null>(null);
    const [lastCall, setLastCall] = useState<{ call: ContactCall; name: string } | null>(null);
    const [callError, setCallError] = useState<ApiError | null>(null);

    const navigate = (changes: Partial<ContactListParams>) => setSearchParams(toSearchParams({ ...params, ...changes }));

    const search = (filters: ContactFilters) => navigate({ ...filters, page: 1 });
    const clear = () => setSearchParams(toSearchParams(DEFAULT_LIST_PARAMS));

    const hasFilters = params.name !== '' || params.phone !== '' || params.email !== '';

    const call = async (contact: Contact) => {
        setCallError(null);
        setLastCall(null);
        setCallingId(contact.id);

        try {
            setLastCall({ call: await placeCall(contact.id), name: `${contact.first_name} ${contact.last_name}` });
        } catch (reason) {
            setCallError(toApiError(reason));
        } finally {
            setCallingId(null);
        }
    };

    const create = async (input: ContactInput) => {
        const { contact, created } = await createContact(input);
        const state: NoticeState = {
            // POST is an upsert keyed on email, so say so when an existing contact was updated.
            notice: created ? 'Contact created.' : 'A contact with this email already existed, so it was updated.',
        };
        closeCreate();
        navigateTo(`/contacts/${contact.id}`, { state });
    };

    return (
        <Stack gap="lg">
            <Group justify="space-between" align="center">
                <Title order={1}>Contacts</Title>
                <Button onClick={openCreate} leftSection={<IconUserPlus size={18} aria-hidden />}>
                    New contact
                </Button>
            </Group>

            {notice && (
                <Alert color="green" variant="light" withCloseButton onClose={dismissNotice} closeButtonLabel="Dismiss message">
                    {notice}
                </Alert>
            )}
            {lastCall && <CallResultAlert call={lastCall.call} name={lastCall.name} onClose={() => setLastCall(null)} />}
            {callError && <ApiErrorAlert error={callError} title="The call failed" />}

            <Card withBorder padding="md">
                <ContactSearchForm initialValues={filtersOf(params)} onSearch={search} onClear={clear} />
            </Card>

            {error && <ApiErrorAlert error={error} title="Could not load contacts" />}

            <Card withBorder padding="md" pos="relative" aria-busy={loading}>
                <LoadingOverlay visible={loading && data !== undefined} overlayProps={{ blur: 1 }} />

                <Group justify="flex-end" mb="md">
                    <SortSelect sort={params.sort} direction={params.direction} onChange={(sort, direction) => navigate({ sort, direction, page: 1 })} />
                </Group>

                {data === undefined && loading && (
                    <Stack gap="xs" aria-label="Loading contacts">
                        {Array.from({ length: 5 }, (_, index) => (
                            <Skeleton key={index} height={40} />
                        ))}
                    </Stack>
                )}

                {data !== undefined && data.data.length === 0 && (
                    <Stack align="center" py="xl" gap="xs">
                        <Text fw={500}>No contacts found</Text>
                        <Text c="dimmed" size="sm">
                            {hasFilters ? 'Try different filters, or clear them to see every contact.' : 'Create your first contact to get started.'}
                        </Text>
                    </Stack>
                )}

                {data !== undefined && data.data.length > 0 && (
                    <Stack gap="md">
                        <ContactsTable contacts={data.data} sort={params.sort} direction={params.direction} callingId={callingId} onCall={call} />
                        <ListPagination meta={data.meta} onPageChange={(page) => navigate({ page })} onPerPageChange={(perPage) => navigate({ per_page: perPage, page: 1 })} />
                    </Stack>
                )}
            </Card>

            <Modal opened={creating} onClose={closeCreate} title="New contact" size="lg" centered>
                {creating && <ContactForm submitLabel="Create contact" onCancel={closeCreate} onSubmit={create} />}
            </Modal>
        </Stack>
    );
}
