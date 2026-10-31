import { vi } from 'vitest';
import type { Contact, ContactCall, Paginated } from '@/features/contacts/types/contact';

export interface RecordedRequest {
    method: string;
    url: URL;
    body: unknown;
}

type Responder = (request: RecordedRequest) => { status?: number; body?: unknown; headers?: Record<string, string> } | Promise<{ status?: number; body?: unknown; headers?: Record<string, string> }>;

/** Replace fetch with a responder and record every request the app sends. */
export function mockApi(responder: Responder): RecordedRequest[] {
    const requests: RecordedRequest[] = [];

    vi.spyOn(globalThis, 'fetch').mockImplementation(async (input, init) => {
        const request: RecordedRequest = {
            method: init?.method ?? 'GET',
            url: new URL(String(input)),
            body: typeof init?.body === 'string' ? JSON.parse(init.body) : undefined,
        };
        requests.push(request);

        const { status = 200, body, headers = {} } = await responder(request);

        return new Response(status === 204 ? null : JSON.stringify(body ?? {}), {
            status,
            headers: { 'Content-Type': 'application/json', ...headers },
        });
    });

    return requests;
}

export function makeContact(overrides: Partial<Contact> = {}): Contact {
    return {
        id: '01a193fc-6c50-7267-89ba-e190b7096e34',
        first_name: 'Jane',
        last_name: 'Citizen',
        email: 'jane@example.com',
        phone: '+61412345678',
        created_at: '2026-10-31T22:00:00Z',
        updated_at: '2026-10-31T22:00:00Z',
        ...overrides,
    };
}

export function makeCall(overrides: Partial<ContactCall> = {}): ContactCall {
    return {
        id: '01a193fc-6f03-73e4-adbc-92a1b2faaa41',
        contact_id: '01a193fc-6c50-7267-89ba-e190b7096e34',
        phone: '+61412345678',
        status: 'completed',
        outcome: 'answered',
        completed_at: '2026-10-31T22:05:00Z',
        created_at: '2026-10-31T22:05:00Z',
        ...overrides,
    };
}

export function page(contacts: Contact[], meta: Partial<Paginated<Contact>['meta']> = {}): Paginated<Contact> {
    return {
        data: contacts,
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: contacts.length,
            from: contacts.length ? 1 : null,
            to: contacts.length || null,
            ...meta,
        },
    };
}
