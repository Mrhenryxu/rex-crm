import { Button, Grid, Group, TextInput } from '@mantine/core';
import { IconSearch, IconX } from '@tabler/icons-react';
import { useEffect, useState, type FormEvent } from 'react';
import type { ContactFilters } from '../types/contact';

interface ContactSearchFormProps {
    initialValues: ContactFilters;
    onSearch: (filters: ContactFilters) => void;
    onClear: () => void;
}

/**
 * Typing does not search; only the Search button (or Enter) sends the filters to the API.
 * Every field is a partial match on the server. On large screens the fields and buttons share one row.
 */
export function ContactSearchForm({ initialValues, onSearch, onClear }: ContactSearchFormProps) {
    const [values, setValues] = useState<ContactFilters>(initialValues);

    // Keep the fields in step with the URL (back/forward navigation, Clear).
    useEffect(() => {
        setValues(initialValues);
    }, [initialValues.name, initialValues.phone, initialValues.email]); // eslint-disable-line react-hooks/exhaustive-deps

    const update = (field: keyof ContactFilters) => (event: { currentTarget: HTMLInputElement }) => {
        const { value } = event.currentTarget;
        setValues((current) => ({ ...current, [field]: value }));
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        onSearch({ name: values.name.trim(), phone: values.phone.trim(), email: values.email.trim() });
    };

    return (
        <form onSubmit={submit} role="search" aria-label="Search contacts">
            <Grid align="flex-end" gap="sm">
                <Grid.Col span={{ base: 12, sm: 4, lg: 3 }}>
                    <TextInput label="Name" placeholder="Part of a first or last name" value={values.name} onChange={update('name')} />
                </Grid.Col>
                <Grid.Col span={{ base: 12, sm: 4, lg: 3 }}>
                    <TextInput label="Phone" placeholder="e.g. 0412 345" value={values.phone} onChange={update('phone')} inputMode="tel" />
                </Grid.Col>
                <Grid.Col span={{ base: 12, sm: 4, lg: 3 }}>
                    <TextInput label="Email" placeholder="e.g. acme.com.au" value={values.email} onChange={update('email')} />
                </Grid.Col>
                <Grid.Col span={{ base: 12, lg: 3 }}>
                    <Group gap="sm" wrap="nowrap">
                        <Button type="submit" leftSection={<IconSearch size={16} aria-hidden />}>
                            Search
                        </Button>
                        <Button type="button" variant="default" leftSection={<IconX size={16} aria-hidden />} onClick={onClear}>
                            Clear
                        </Button>
                    </Group>
                </Grid.Col>
            </Grid>
        </form>
    );
}
