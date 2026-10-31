import { Badge, Table, Text } from '@mantine/core';
import type { ContactCall } from '../types/contact';
import { formatDateTime, OUTCOME_COLORS, OUTCOME_LABELS, STATUS_LABELS } from '../utils/format';
import { PhoneNumberText } from './PhoneNumberText';

export function CallHistory({ calls }: { calls: ContactCall[] }) {
    if (calls.length === 0) {
        return <Text c="dimmed">No calls yet.</Text>;
    }

    return (
        <Table.ScrollContainer minWidth={480}>
            <Table aria-label="Latest calls" verticalSpacing="xs">
                <Table.Thead>
                    <Table.Tr>
                        <Table.Th scope="col">Placed</Table.Th>
                        <Table.Th scope="col">Number</Table.Th>
                        <Table.Th scope="col">Outcome</Table.Th>
                    </Table.Tr>
                </Table.Thead>
                <Table.Tbody>
                    {calls.map((call) => (
                        <Table.Tr key={call.id}>
                            <Table.Td>{formatDateTime(call.created_at)}</Table.Td>
                            <Table.Td>
                                <PhoneNumberText phone={call.phone} />
                            </Table.Td>
                            <Table.Td>
                                {call.outcome ? (
                                    <Badge color={OUTCOME_COLORS[call.outcome]} variant="light">
                                        {OUTCOME_LABELS[call.outcome]}
                                    </Badge>
                                ) : (
                                    <Badge color={call.status === 'failed' ? 'red' : 'gray'} variant="outline">
                                        {STATUS_LABELS[call.status]}
                                    </Badge>
                                )}
                            </Table.Td>
                        </Table.Tr>
                    ))}
                </Table.Tbody>
            </Table>
        </Table.ScrollContainer>
    );
}
