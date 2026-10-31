import { Alert, Button, Card, Group, LoadingOverlay, Skeleton, Stack, Text, Title } from '@mantine/core';
import { IconUserPlus } from '@tabler/icons-react';
import { Link, useSearchParams } from 'react-router';
import { ApiErrorAlert } from '@/components/ui/ApiErrorAlert';
import { ListPagination, SortSelect } from '../components/ContactListControls';
import { ContactSearchForm } from '../components/ContactSearchForm';
import { ContactsTable } from '../components/ContactsTable';
import { useContactList } from '../hooks/useContacts';
import { useNotice } from '../hooks/useNotice';
import type { ContactFilters, ContactListParams } from '../types/contact';
import { DEFAULT_LIST_PARAMS, filtersOf, parseListParams, toSearchParams } from '../utils/listParams';

export function ContactsListPage() {
    const [searchParams, setSearchParams] = useSearchParams();
    const params = parseListParams(searchParams);
    const { data, error, loading } = useContactList(params);
    const [notice, dismissNotice] = useNotice();

    const navigate = (changes: Partial<ContactListParams>) => setSearchParams(toSearchParams({ ...params, ...changes }));

    const search = (filters: ContactFilters) => navigate({ ...filters, page: 1 });
    const clear = () => setSearchParams(toSearchParams(DEFAULT_LIST_PARAMS));

    const hasFilters = params.name !== '' || params.phone !== '' || params.email_domain !== '';

    return (
        <Stack gap="lg">
            <Group justify="space-between" align="center">
                <Title order={1}>Contacts</Title>
                <Button component={Link} to="/contacts/new" leftSection={<IconUserPlus size={18} aria-hidden />}>
                    New contact
                </Button>
            </Group>

            {notice && (
                <Alert color="green" variant="light" withCloseButton onClose={dismissNotice} closeButtonLabel="Dismiss message">
                    {notice}
                </Alert>
            )}

            <Card withBorder padding="md">
                <ContactSearchForm initialValues={filtersOf(params)} onSearch={search} onClear={clear} />
            </Card>

            {error && <ApiErrorAlert error={error} title="Could not load contacts" />}

            <Card withBorder padding="md" pos="relative" aria-busy={loading}>
                <LoadingOverlay visible={loading && data !== undefined} overlayProps={{ blur: 1 }} />

                <Group justify="space-between" align="flex-end" mb="md">
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
                        <ContactsTable contacts={data.data} sort={params.sort} direction={params.direction} />
                        <ListPagination meta={data.meta} onPageChange={(page) => navigate({ page })} onPerPageChange={(perPage) => navigate({ per_page: perPage, page: 1 })} />
                    </Stack>
                )}
            </Card>
        </Stack>
    );
}
