import { Button, Group, SimpleGrid, TextInput } from '@mantine/core';
import { IconSearch, IconX } from '@tabler/icons-react';
import { useEffect, useState, type FormEvent } from 'react';
import type { ContactFilters } from '../types/contact';

interface ContactSearchFormProps {
    initialValues: ContactFilters;
    onSearch: (filters: ContactFilters) => void;
    onClear: () => void;
}

/** Typing does not search; only the Search button (or Enter) sends the filters to the API. */
export function ContactSearchForm({ initialValues, onSearch, onClear }: ContactSearchFormProps) {
    const [values, setValues] = useState<ContactFilters>(initialValues);

    // Keep the fields in step with the URL (back/forward navigation, Clear).
    useEffect(() => {
        setValues(initialValues);
    }, [initialValues.name, initialValues.phone, initialValues.email_domain]); // eslint-disable-line react-hooks/exhaustive-deps

    const update = (field: keyof ContactFilters) => (event: { currentTarget: HTMLInputElement }) => {
        const { value } = event.currentTarget;
        setValues((current) => ({ ...current, [field]: value }));
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        onSearch({ name: values.name.trim(), phone: values.phone.trim(), email_domain: values.email_domain.trim() });
    };

    return (
        <form onSubmit={submit} role="search" aria-label="Search contacts">
            <SimpleGrid cols={{ base: 1, sm: 3 }} spacing="sm">
                <TextInput label="Name" description="Start of first or last name" placeholder="e.g. Jo Smi" value={values.name} onChange={update('name')} />
                <TextInput label="Phone" description="Start of an E.164 number" placeholder="e.g. +61412" value={values.phone} onChange={update('phone')} inputMode="tel" />
                <TextInput label="Email domain" description="Exact domain" placeholder="e.g. example.com" value={values.email_domain} onChange={update('email_domain')} />
            </SimpleGrid>
            <Group mt="sm" gap="sm">
                <Button type="submit" leftSection={<IconSearch size={16} aria-hidden />}>
                    Search
                </Button>
                <Button type="button" variant="default" leftSection={<IconX size={16} aria-hidden />} onClick={onClear}>
                    Clear
                </Button>
            </Group>
        </form>
    );
}
