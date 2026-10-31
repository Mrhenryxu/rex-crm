import { Button, Group, Modal, Text } from '@mantine/core';
import type { ReactNode } from 'react';

interface ConfirmationDialogProps {
    opened: boolean;
    title: string;
    children: ReactNode;
    confirmLabel: string;
    loading?: boolean;
    onConfirm: () => void;
    onCancel: () => void;
}

/** A modal that asks before a destructive action. Cancel is focused first so Enter never confirms by accident. */
export function ConfirmationDialog({ opened, title, children, confirmLabel, loading = false, onConfirm, onCancel }: ConfirmationDialogProps) {
    return (
        <Modal opened={opened} onClose={onCancel} title={title} centered closeOnClickOutside={!loading} closeOnEscape={!loading}>
            <Text size="sm">{children}</Text>
            <Group justify="flex-end" mt="lg" gap="sm">
                <Button variant="default" onClick={onCancel} disabled={loading} data-autofocus>
                    Cancel
                </Button>
                <Button color="red" onClick={onConfirm} loading={loading}>
                    {confirmLabel}
                </Button>
            </Group>
        </Modal>
    );
}
