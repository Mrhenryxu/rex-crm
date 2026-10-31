import { Anchor, Group, Table, Text } from '@mantine/core';
import { Link } from 'react-router';
import type { Contact, SortDirection, SortField } from '../types/contact';
import { formatDate, formatPhone } from '../utils/format';
import { ContactAvatar } from './ContactAvatar';

interface ContactsTableProps {
    contacts: Contact[];
    sort: SortField;
    direction: SortDirection;
}

function ariaSort(active: boolean, direction: SortDirection): 'ascending' | 'descending' | undefined {
    if (!active) {
        return undefined;
    }

    return direction === 'asc' ? 'ascending' : 'descending';
}

/** Renders one page exactly as the API returned it: no client-side sorting or filtering. */
export function ContactsTable({ contacts, sort, direction }: ContactsTableProps) {
    return (
        <Table.ScrollContainer minWidth={640}>
            <Table striped highlightOnHover verticalSpacing="sm" aria-label="Contacts">
                <Table.Thead>
                    <Table.Tr>
                        <Table.Th scope="col" aria-sort={ariaSort(sort !== 'created_at', direction)}>
                            Name
                        </Table.Th>
                        <Table.Th scope="col">Email</Table.Th>
                        <Table.Th scope="col">Phone</Table.Th>
                        <Table.Th scope="col" aria-sort={ariaSort(sort === 'created_at', direction)}>
                            Added
                        </Table.Th>
                    </Table.Tr>
                </Table.Thead>
                <Table.Tbody>
                    {contacts.map((contact) => (
                        <Table.Tr key={contact.id}>
                            <Table.Td>
                                <Group gap="sm" wrap="nowrap">
                                    <ContactAvatar firstName={contact.first_name} lastName={contact.last_name} size="md" />
                                    <Anchor component={Link} to={`/contacts/${contact.id}`} fw={500}>
                                        {contact.first_name} {contact.last_name}
                                    </Anchor>
                                </Group>
                            </Table.Td>
                            <Table.Td>{contact.email ?? <Text c="dimmed" span>—</Text>}</Table.Td>
                            <Table.Td>{contact.phone ? formatPhone(contact.phone) : <Text c="dimmed" span>—</Text>}</Table.Td>
                            <Table.Td>{formatDate(contact.created_at)}</Table.Td>
                        </Table.Tr>
                    ))}
                </Table.Tbody>
            </Table>
        </Table.ScrollContainer>
    );
}
