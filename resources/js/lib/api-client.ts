/** Field errors as returned by Laravel: `{ field: ["message", ...] }`. */
export type FieldErrors = Record<string, string[]>;

/** Every failed API call is turned into this one error type. */
export class ApiError extends Error {
    constructor(
        public readonly status: number,
        message: string,
        public readonly errors: FieldErrors = {},
        public readonly retryAfterSeconds: number | null = null,
    ) {
        super(message);
        this.name = 'ApiError';
    }

    get isNotFound(): boolean {
        return this.status === 404;
    }

    get isValidation(): boolean {
        return this.status === 422 || this.status === 409;
    }
}

type QueryValue = string | number | null | undefined;

export interface ApiRequestOptions {
    method?: 'GET' | 'POST' | 'PUT' | 'DELETE';
    body?: unknown;
    query?: Record<string, QueryValue>;
    signal?: AbortSignal;
}

export interface ApiResponse<T> {
    data: T;
    status: number;
}

const API_PREFIX = '/api/v1';

const FALLBACK_MESSAGES: Record<number, string> = {
    404: 'The requested record was not found.',
    429: 'Too many requests. Please wait and try again.',
    500: 'Something went wrong on the server. Please try again.',
};

function buildUrl(path: string, query: Record<string, QueryValue> = {}): string {
    const url = new URL(`${API_PREFIX}${path}`, window.location.origin);

    for (const [key, value] of Object.entries(query)) {
        if (value !== undefined && value !== null && value !== '') {
            url.searchParams.set(key, String(value));
        }
    }

    return url.toString();
}

function isAbort(error: unknown): boolean {
    return error instanceof DOMException && error.name === 'AbortError';
}

/** Send a request and return the parsed body together with the HTTP status. */
export async function apiRequestWithStatus<T>(path: string, options: ApiRequestOptions = {}): Promise<ApiResponse<T>> {
    const { method = 'GET', body, query, signal } = options;
    let response: Response;

    try {
        response = await fetch(buildUrl(path, query), {
            method,
            headers: {
                Accept: 'application/json',
                ...(body === undefined ? {} : { 'Content-Type': 'application/json' }),
            },
            body: body === undefined ? undefined : JSON.stringify(body),
            signal,
        });
    } catch (error) {
        if (isAbort(error)) {
            throw error;
        }

        throw new ApiError(0, 'Unable to reach the server. Check your connection and try again.');
    }

    if (response.status === 204) {
        return { data: undefined as T, status: 204 };
    }

    const payload: unknown = await response.json().catch(() => null);

    if (!response.ok) {
        const parsed = (payload ?? {}) as { message?: unknown; errors?: unknown };
        const retryAfter = Number(response.headers.get('Retry-After'));

        throw new ApiError(
            response.status,
            typeof parsed.message === 'string' && parsed.message !== '' ? parsed.message : (FALLBACK_MESSAGES[response.status] ?? `Request failed (${response.status}).`),
            typeof parsed.errors === 'object' && parsed.errors !== null ? (parsed.errors as FieldErrors) : {},
            Number.isFinite(retryAfter) && retryAfter > 0 ? retryAfter : null,
        );
    }

    return { data: payload as T, status: response.status };
}

export async function apiRequest<T>(path: string, options: ApiRequestOptions = {}): Promise<T> {
    return (await apiRequestWithStatus<T>(path, options)).data;
}

export function toApiError(error: unknown): ApiError {
    return error instanceof ApiError ? error : new ApiError(0, 'Something unexpected went wrong. Please try again.');
}
