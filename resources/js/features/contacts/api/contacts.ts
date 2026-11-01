import { apiRequest, apiRequestWithStatus } from '@/lib/api-client';
import type { Contact, ContactCall, ContactInput, ContactListParams, Paginated, Resource } from '../types/contact';

export function listContacts(params: ContactListParams, signal?: AbortSignal): Promise<Paginated<Contact>> {
    return apiRequest<Paginated<Contact>>('/contacts', { query: { ...params }, signal });
}

export async function getContact(id: string, signal?: AbortSignal): Promise<Contact> {
    return (await apiRequest<Resource<Contact>>(`/contacts/${encodeURIComponent(id)}`, { signal })).data;
}

/** POST is an upsert keyed on email: `created` is false when an existing contact was updated. */
export async function createContact(input: ContactInput): Promise<{ contact: Contact; created: boolean }> {
    const response = await apiRequestWithStatus<Resource<Contact>>('/contacts', { method: 'POST', body: input });

    return { contact: response.data.data, created: response.status === 201 };
}

export async function updateContact(id: string, input: ContactInput): Promise<Contact> {
    return (await apiRequest<Resource<Contact>>(`/contacts/${encodeURIComponent(id)}`, { method: 'PUT', body: input })).data;
}

export async function deleteContact(id: string): Promise<void> {
    await apiRequest<void>(`/contacts/${encodeURIComponent(id)}`, { method: 'DELETE' });
}

/** The SPA places calls synchronously so the outcome is shown straight away. */
export async function placeCall(id: string): Promise<ContactCall> {
    return (await apiRequest<Resource<ContactCall>>(`/contacts/${encodeURIComponent(id)}/calls`, { method: 'POST', body: { mode: 'sync' } })).data;
}
