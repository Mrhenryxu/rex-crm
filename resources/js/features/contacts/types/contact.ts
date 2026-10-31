/** Mirrors App\Features\Contacts\Http\Resources\ContactCallResource. */
export type CallStatus = 'pending' | 'completed' | 'failed';
export type CallOutcome = 'answered' | 'busy' | 'no_answer' | 'voicemail' | 'failed';

export interface ContactCall {
    id: string;
    contact_id: string;
    phone: string;
    status: CallStatus;
    outcome: CallOutcome | null;
    completed_at: string | null;
    created_at: string;
}

/** Mirrors App\Features\Contacts\Http\Resources\ContactResource. */
export interface Contact {
    id: string;
    first_name: string;
    last_name: string;
    email: string | null;
    phone: string | null;
    created_at: string;
    updated_at: string;
    latest_calls?: ContactCall[];
}

export interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

export interface Paginated<T> {
    data: T[];
    meta: PaginationMeta;
}

export interface Resource<T> {
    data: T;
}

export const SORT_FIELDS = ['last_name', 'first_name', 'created_at'] as const;
export type SortField = (typeof SORT_FIELDS)[number];
export type SortDirection = 'asc' | 'desc';

export interface ContactFilters {
    name: string;
    phone: string;
    email_domain: string;
}

/** Query parameters for GET /contacts. Filtering, sorting and paging all happen on the server. */
export interface ContactListParams extends ContactFilters {
    sort: SortField;
    direction: SortDirection;
    page: number;
    per_page: number;
}

export interface ContactInput {
    first_name: string;
    last_name: string;
    email: string | null;
    phone: string | null;
}
