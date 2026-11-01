import { Button, Stack, Text, Title } from '@mantine/core';
import { Link } from 'react-router';

export function NotFoundPage({ message = 'The page you requested does not exist.' }: { message?: string }) {
    return (
        <Stack align="flex-start" gap="sm">
            <Title order={1}>Not found</Title>
            <Text c="dimmed">{message}</Text>
            <Button component={Link} to="/contacts" variant="light">
                Back to contacts
            </Button>
        </Stack>
    );
}
