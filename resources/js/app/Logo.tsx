import { Anchor, Group, Text, ThemeIcon } from '@mantine/core';
import { IconAddressBook } from '@tabler/icons-react';
import { Link } from 'react-router';

export function Logo() {
    return (
        <Anchor component={Link} to="/contacts" underline="never" c="inherit" aria-label="Rex CRM home">
            <Group gap="xs" wrap="nowrap">
                <ThemeIcon size="lg" radius="md" variant="filled" aria-hidden>
                    <IconAddressBook size={20} stroke={1.8} />
                </ThemeIcon>
                <Text component="span" fw={700} size="lg">
                    Rex CRM
                </Text>
            </Group>
        </Anchor>
    );
}
