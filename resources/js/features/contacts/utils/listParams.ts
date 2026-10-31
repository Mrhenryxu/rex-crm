import { SORT_FIELDS, type ContactFilters, type ContactListParams, type SortField } from '../types/contact';

export const PER_PAGE_OPTIONS = [10, 15, 25, 50, 100] as const;

export const DEFAULT_LIST_PARAMS: ContactListParams = {
    name: '',
    phone: '',
    email_domain: '',
    sort: 'last_name',
    direction: 'asc',
    page: 1,
    per_page: 15,
};

function positiveInt(value: string | null, fallback: number): number {
    const parsed = Number(value);

    return Number.isInteger(parsed) && parsed > 0 ? parsed : fallback;
}

/** Read list state from the URL. Unknown values fall back to defaults; the API validates the rest. */
export function parseListParams(search: URLSearchParams): ContactListParams {
    const sort = search.get('sort');
    const perPage = positiveInt(search.get('per_page'), DEFAULT_LIST_PARAMS.per_page);

    return {
        name: search.get('name') ?? '',
        phone: search.get('phone') ?? '',
        email_domain: search.get('email_domain') ?? '',
        sort: SORT_FIELDS.includes(sort as SortField) ? (sort as SortField) : DEFAULT_LIST_PARAMS.sort,
        direction: search.get('direction') === 'desc' ? 'desc' : 'asc',
        page: positiveInt(search.get('page'), 1),
        per_page: (PER_PAGE_OPTIONS as readonly number[]).includes(perPage) ? perPage : DEFAULT_LIST_PARAMS.per_page,
    };
}

/** Write list state to the URL, leaving out defaults so URLs stay short. */
export function toSearchParams(params: ContactListParams): URLSearchParams {
    const search = new URLSearchParams();

    for (const key of Object.keys(params) as (keyof ContactListParams)[]) {
        const value = params[key];

        if (value !== DEFAULT_LIST_PARAMS[key] && String(value).trim() !== '') {
            search.set(key, String(value).trim());
        }
    }

    return search;
}

export function filtersOf(params: ContactListParams): ContactFilters {
    return { name: params.name, phone: params.phone, email_domain: params.email_domain };
}
