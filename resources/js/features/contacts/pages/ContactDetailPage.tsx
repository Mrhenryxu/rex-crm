import { Alert, Anchor, Button, Card, Group, Skeleton, Stack, Text, Title, Tooltip } from '@mantine/core';
import { IconArrowLeft, IconMail, IconPencil, IconPhoneCall, IconTrash } from '@tabler/icons-react';
import { useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router';
import { NotFoundPage } from '@/app/NotFoundPage';
import { ApiErrorAlert } from '@/components/ui/ApiErrorAlert';
import { ConfirmationDialog } from '@/components/ui/ConfirmationDialog';
import { ApiError, toApiError } from '@/lib/api-client';
import { deleteContact, placeCall } from '../api/contacts';
import { CallHistory } from '../components/CallHistory';
import { CallResultAlert } from '../components/CallResultAlert';
import { ContactAvatar } from '../components/ContactAvatar';
import { PhoneNumberText } from '../components/PhoneNumberText';
import { useContact } from '../hooks/useContacts';
import { useNotice, type NoticeState } from '../hooks/useNotice';
import type { ContactCall } from '../types/contact';

export function ContactDetailPage() {
    const { id = '' } = useParams();
    const navigate = useNavigate();
    const { data: contact, error, loading, reload } = useContact(id);
    const [notice, dismissNotice] = useNotice();
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [lastCall, setLastCall] = useState<ContactCall | null>(null);
    const [calling, setCalling] = useState(false);
    const [confirmingDelete, setConfirmingDelete] = useState(false);
    const [deleting, setDeleting] = useState(false);

    if (error?.isNotFound) {
        return <NotFoundPage message="This contact does not exist or has been deleted." />;
    }

    const call = async () => {
        setActionError(null);
        setLastCall(null);
        setCalling(true);

        try {
            setLastCall(await placeCall(id));
            reload();
        } catch (reason) {
            setActionError(toApiError(reason));
        } finally {
            setCalling(false);
        }
    };

    const remove = async () => {
        setDeleting(true);

        try {
            await deleteContact(id);
            const state: NoticeState = { notice: 'Contact deleted.' };
            navigate('/contacts', { replace: true, state });
        } catch (reason) {
            setDeleting(false);
            setConfirmingDelete(false);
            setActionError(toApiError(reason));
        }
    };

    return (
        <Stack gap="lg">
            <Anchor component={Link} to="/contacts" size="sm">
                <Group gap={4}>
                    <IconArrowLeft size={14} aria-hidden /> Back to contacts
                </Group>
            </Anchor>

            {notice && (
                <Alert color="green" variant="light" withCloseButton onClose={dismissNotice} closeButtonLabel="Dismiss message">
                    {notice}
                </Alert>
            )}
            {error && <ApiErrorAlert error={error} title="Could not load the contact" />}
            {actionError && <ApiErrorAlert error={actionError} title="The action failed" />}
            {lastCall && <CallResultAlert call={lastCall} />}

            {loading && !contact && <Skeleton height={280} aria-label="Loading contact" />}

            {contact && (
                <>
                    <Card withBorder padding="xl">
                        <Stack align="center" gap="md">
                            <ContactAvatar firstName={contact.first_name} lastName={contact.last_name} size={96} />
                            <Title order={1} ta="center">
                                {contact.first_name} {contact.last_name}
                            </Title>

                            <Stack align="center" gap={6}>
                                {contact.email && (
                                    <Group gap={8} wrap="nowrap">
                                        <IconMail size={18} aria-hidden />
                                        <Anchor href={`mailto:${contact.email}`}>{contact.email}</Anchor>
                                    </Group>
                                )}
                                {contact.phone && <PhoneNumberText phone={contact.phone} />}
                                {!contact.email && !contact.phone && <Text c="dimmed">No contact details</Text>}
                            </Stack>

                            <Group gap="sm" justify="center" mt="xs">
                                <Tooltip label="Add a phone number to call this contact" disabled={contact.phone !== null}>
                                    <Button leftSection={<IconPhoneCall size={18} aria-hidden />} onClick={call} loading={calling} disabled={contact.phone === null}>
                                        Call
                                    </Button>
                                </Tooltip>
                                <Button
                                    component={Link}
                                    to={`/contacts/${contact.id}/edit`}
                                    variant="default"
                                    leftSection={<IconPencil size={18} aria-hidden />}
                                    // No hover effect: keep the resting background on hover.
                                    vars={() => ({ root: { '--button-hover': 'var(--mantine-color-default)' } })}
                                >
                                    Edit
                                </Button>
                                <Button color="red" variant="light" leftSection={<IconTrash size={18} aria-hidden />} onClick={() => setConfirmingDelete(true)}>
                                    Delete
                                </Button>
                            </Group>
                        </Stack>
                    </Card>

                    <Card withBorder padding="lg">
                        <Title order={2} size="h4" mb="sm">
                            Latest calls
                        </Title>
                        <CallHistory calls={contact.latest_calls ?? []} />
                    </Card>

                    <ConfirmationDialog
                        opened={confirmingDelete}
                        title="Delete contact"
                        confirmLabel="Delete contact"
                        loading={deleting}
                        onConfirm={remove}
                        onCancel={() => setConfirmingDelete(false)}
                    >
                        Delete {contact.first_name} {contact.last_name} and their call history? This cannot be undone.
                    </ConfirmationDialog>
                </>
            )}
        </Stack>
    );
}
