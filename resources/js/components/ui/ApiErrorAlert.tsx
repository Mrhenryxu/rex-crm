import { Alert, List } from '@mantine/core';
import { IconAlertTriangle } from '@tabler/icons-react';
import type { ApiError } from '@/lib/api-client';

/** Shows an API error with any field messages and the retry time for rate limits. */
export function ApiErrorAlert({ error, title = 'Something went wrong' }: { error: ApiError; title?: string }) {
    const fieldMessages = Object.values(error.errors).flat();

    return (
        <Alert color="red" variant="light" title={title} icon={<IconAlertTriangle aria-hidden />} role="alert">
            {error.message}
            {error.retryAfterSeconds !== null && ` You can retry in ${error.retryAfterSeconds} seconds.`}
            {fieldMessages.length > 0 && fieldMessages.some((message) => message !== error.message) && (
                <List size="sm" mt="xs">
                    {fieldMessages.map((message) => (
                        <List.Item key={message}>{message}</List.Item>
                    ))}
                </List>
            )}
        </Alert>
    );
}
