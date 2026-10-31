import { Alert, Badge } from '@mantine/core';
import type { ContactCall } from '../types/contact';
import { formatPhone, OUTCOME_COLORS, OUTCOME_LABELS, STATUS_LABELS } from '../utils/format';

/** The result of a mock call, announced to screen readers as a status update. */
export function CallResultAlert({ call, name, onClose }: { call: ContactCall; name?: string; onClose?: () => void }) {
    const color = call.outcome ? OUTCOME_COLORS[call.outcome] : 'gray';

    return (
        <Alert color={color} variant="light" title="Call finished" role="status" withCloseButton={onClose !== undefined} onClose={onClose} closeButtonLabel="Dismiss call result">
            Called {name ? `${name} on ` : ''}
            {formatPhone(call.phone)}. Outcome:{' '}
            <Badge color={color} variant="filled">
                {call.outcome ? OUTCOME_LABELS[call.outcome] : STATUS_LABELS[call.status]}
            </Badge>
        </Alert>
    );
}
