import { useApiQuery } from '@/lib/useApiQuery';
import { getContact, listContacts } from '../api/contacts';
import type { ContactListParams } from '../types/contact';

export function useContactList(params: ContactListParams) {
    return useApiQuery((signal) => listContacts(params, signal), [
        params.name,
        params.phone,
        params.email_domain,
        params.sort,
        params.direction,
        params.page,
        params.per_page,
    ]);
}

export function useContact(id: string) {
    return useApiQuery((signal) => getContact(id, signal), [id]);
}
