import { Group, Pagination, Select, Text } from '@mantine/core';
import type { PaginationMeta, SortDirection, SortField } from '../types/contact';
import { PER_PAGE_OPTIONS } from '../utils/listParams';

const SORT_OPTIONS: { value: string; label: string }[] = [
    { value: 'last_name:asc', label: 'Last name (A–Z)' },
    { value: 'last_name:desc', label: 'Last name (Z–A)' },
    { value: 'first_name:asc', label: 'First name (A–Z)' },
    { value: 'first_name:desc', label: 'First name (Z–A)' },
    { value: 'created_at:desc', label: 'Newest first' },
    { value: 'created_at:asc', label: 'Oldest first' },
];

export function SortSelect({ sort, direction, onChange }: { sort: SortField; direction: SortDirection; onChange: (sort: SortField, direction: SortDirection) => void }) {
    return (
        <Select
            label="Sort by"
            data={SORT_OPTIONS}
            value={`${sort}:${direction}`}
            allowDeselect={false}
            onChange={(value) => {
                if (value) {
                    const [field, dir] = value.split(':') as [SortField, SortDirection];
                    onChange(field, dir);
                }
            }}
            w={200}
        />
    );
}

export function ListPagination({ meta, onPageChange, onPerPageChange }: { meta: PaginationMeta; onPageChange: (page: number) => void; onPerPageChange: (perPage: number) => void }) {
    return (
        <Group justify="space-between" wrap="wrap" gap="sm">
            <Text size="sm" c="dimmed" aria-live="polite">
                {meta.total === 0 ? 'No contacts' : `Showing ${meta.from}–${meta.to} of ${meta.total} contacts`}
            </Text>
            <Group gap="sm" wrap="wrap">
                <Select
                    aria-label="Contacts per page"
                    data={PER_PAGE_OPTIONS.map((option) => ({ value: String(option), label: `${option} per page` }))}
                    value={String(meta.per_page)}
                    allowDeselect={false}
                    onChange={(value) => value && onPerPageChange(Number(value))}
                    w={150}
                />
                <Pagination
                    total={meta.last_page}
                    value={Math.min(meta.current_page, meta.last_page)}
                    onChange={onPageChange}
                    getControlProps={(control) => ({ 'aria-label': `${control} page` })}
                    getItemProps={(page) => ({ 'aria-label': `Page ${page}` })}
                />
            </Group>
        </Group>
    );
}
