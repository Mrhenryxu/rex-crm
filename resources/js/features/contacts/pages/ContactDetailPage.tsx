import { Alert, Anchor, Badge, Button, Card, Group, SimpleGrid, Skeleton, Stack, Text, Title, Tooltip } from '@mantine/core';
import { IconArrowLeft, IconPencil, IconPhoneCall, IconTrash } from '@tabler/icons-react';
import { useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router';
import { NotFoundPage } from '@/app/NotFoundPage';
import { ApiErrorAlert } from '@/components/ui/ApiErrorAlert';
import { ConfirmationDialog } from '@/components/ui/ConfirmationDialog';
import { ApiError, toApiError } from '@/lib/api-client';
import { deleteContact, placeCall } from '../api/contacts';
import { CallHistory } from '../components/CallHistory';
import { ContactAvatar } from '../components/ContactAvatar';
import { useContact } from '../hooks/useContacts';
import { useNotice, type NoticeState } from '../hooks/useNotice';
import type { ContactCall } from '../types/contact';
import { formatDateTime, formatPhone, OUTCOME_COLORS, OUTCOME_LABELS, STATUS_LABELS } from '../utils/format';

function Detail({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div>
            <Text size="sm" c="dimmed">
                {label}
            </Text>
            <Text>{children}</Text>
        </div>
    );
}

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

            {loading && !contact && <Skeleton height={200} aria-label="Loading contact" />}

            {contact && (
                <>
                    <Group justify="space-between" align="center" wrap="wrap">
                        <Group gap="md" wrap="nowrap">
                            <ContactAvatar firstName={contact.first_name} lastName={contact.last_name} size="lg" />
                            <Title order={1}>
                                {contact.first_name} {contact.last_name}
                            </Title>
                        </Group>
                        <Group gap="sm">
                            <Tooltip label="Add a phone number to call this contact" disabled={contact.phone !== null}>
                                <Button leftSection={<IconPhoneCall size={18} aria-hidden />} onClick={call} loading={calling} disabled={contact.phone === null}>
                                    Call
                                </Button>
                            </Tooltip>
                            <Button component={Link} to={`/contacts/${contact.id}/edit`} variant="default" leftSection={<IconPencil size={18} aria-hidden />}>
                                Edit
                            </Button>
                            <Button color="red" variant="light" leftSection={<IconTrash size={18} aria-hidden />} onClick={() => setConfirmingDelete(true)}>
                                Delete
                            </Button>
                        </Group>
                    </Group>

                    {lastCall && (
                        <Alert color={lastCall.outcome ? OUTCOME_COLORS[lastCall.outcome] : 'gray'} variant="light" title="Call finished" role="status">
                            Called {formatPhone(lastCall.phone)}. Outcome:{' '}
                            <Badge color={lastCall.outcome ? OUTCOME_COLORS[lastCall.outcome] : 'gray'} variant="filled">
                                {lastCall.outcome ? OUTCOME_LABELS[lastCall.outcome] : STATUS_LABELS[lastCall.status]}
                            </Badge>
                        </Alert>
                    )}

                    <Card withBorder padding="lg">
                        <SimpleGrid cols={{ base: 1, sm: 2 }} spacing="md">
                            <Detail label="Email">{contact.email ?? '—'}</Detail>
                            <Detail label="Phone">{contact.phone ? formatPhone(contact.phone) : '—'}</Detail>
                            <Detail label="Added">{formatDateTime(contact.created_at)}</Detail>
                            <Detail label="Last updated">{formatDateTime(contact.updated_at)}</Detail>
                        </SimpleGrid>
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
