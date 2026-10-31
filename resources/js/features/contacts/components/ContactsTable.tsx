import { ActionIcon, Anchor, Group, Table, Text, Tooltip } from '@mantine/core';
import { IconMail, IconPhoneCall } from '@tabler/icons-react';
import type { MouseEvent } from 'react';
import { Link, useNavigate } from 'react-router';
import type { Contact, SortDirection, SortField } from '../types/contact';
import { ContactAvatar } from './ContactAvatar';
import { PhoneNumberText } from './PhoneNumberText';
import classes from './ContactsTable.module.css';

interface ContactsTableProps {
    contacts: Contact[];
    sort: SortField;
    direction: SortDirection;
    /** Contact id whose call is in progress, to show a loading state on its call button. */
    callingId: string | null;
    onCall: (contact: Contact) => void;
}

function ariaSort(sort: SortField, direction: SortDirection): 'ascending' | 'descending' | undefined {
    if (sort === 'created_at') {
        return undefined;
    }

    return direction === 'asc' ? 'ascending' : 'descending';
}

/** Clicks on links and buttons inside a row do their own thing instead of opening the contact. */
function isInteractive(event: MouseEvent): boolean {
    return (event.target as HTMLElement).closest('a, button, input, select') !== null;
}

/**
 * Renders one page exactly as the API returned it: no client-side sorting or filtering.
 * The whole row opens the contact; the name link keeps that reachable by keyboard.
 */
export function ContactsTable({ contacts, sort, direction, callingId, onCall }: ContactsTableProps) {
    const navigate = useNavigate();

    return (
        <Table.ScrollContainer minWidth={640}>
            <Table striped highlightOnHover verticalSpacing="sm" aria-label="Contacts">
                <Table.Thead>
                    <Table.Tr>
                        <Table.Th scope="col" aria-sort={ariaSort(sort, direction)}>
                            Name
                        </Table.Th>
                        <Table.Th scope="col">Email</Table.Th>
                        <Table.Th scope="col">Phone</Table.Th>
                        <Table.Th scope="col" ta="right">
                            Actions
                        </Table.Th>
                    </Table.Tr>
                </Table.Thead>
                <Table.Tbody>
                    {contacts.map((contact) => {
                        const fullName = `${contact.first_name} ${contact.last_name}`;

                        return (
                            <Table.Tr
                                key={contact.id}
                                className={classes.row}
                                onClick={(event) => {
                                    if (!isInteractive(event)) {
                                        navigate(`/contacts/${contact.id}`);
                                    }
                                }}
                            >
                                <Table.Td>
                                    <Group gap="sm" wrap="nowrap">
                                        <ContactAvatar firstName={contact.first_name} lastName={contact.last_name} size="md" />
                                        <Anchor component={Link} to={`/contacts/${contact.id}`} fw={500} c="inherit" underline="hover">
                                            {fullName}
                                        </Anchor>
                                    </Group>
                                </Table.Td>
                                <Table.Td>{contact.email ?? <Text c="dimmed" span>—</Text>}</Table.Td>
                                <Table.Td>{contact.phone ? <PhoneNumberText phone={contact.phone} /> : <Text c="dimmed" span>—</Text>}</Table.Td>
                                <Table.Td>
                                    <Group gap={6} justify="flex-end" wrap="nowrap">
                                        <Tooltip label={contact.phone ? `Call ${fullName}` : 'No phone number'}>
                                            <ActionIcon
                                                variant="light"
                                                size="lg"
                                                aria-label={`Call ${fullName}`}
                                                disabled={contact.phone === null}
                                                loading={callingId === contact.id}
                                                onClick={() => onCall(contact)}
                                            >
                                                <IconPhoneCall size={18} aria-hidden />
                                            </ActionIcon>
                                        </Tooltip>
                                        <Tooltip label={contact.email ? `Email ${fullName}` : 'No email address'}>
                                            {contact.email ? (
                                                <ActionIcon component="a" href={`mailto:${contact.email}`} variant="light" size="lg" aria-label={`Email ${fullName}`}>
                                                    <IconMail size={18} aria-hidden />
                                                </ActionIcon>
                                            ) : (
                                                <ActionIcon variant="light" size="lg" aria-label={`Email ${fullName}`} disabled>
                                                    <IconMail size={18} aria-hidden />
                                                </ActionIcon>
                                            )}
                                        </Tooltip>
                                    </Group>
                                </Table.Td>
                            </Table.Tr>
                        );
                    })}
                </Table.Tbody>
            </Table>
        </Table.ScrollContainer>
    );
}
