import { screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { makeCall, makeContact, mockApi, page } from '@/test/api';
import { renderApp } from '@/test/render';

const contact = makeContact();

describe('Contact details', () => {
    test('shows the contact and its latest calls', async () => {
        mockApi(() => ({ body: { data: { ...contact, latest_calls: [makeCall({ outcome: 'voicemail' }), makeCall({ id: 'c2', status: 'pending', outcome: null })] } } }));

        renderApp(`/contacts/${contact.id}`);

        expect(await screen.findByRole('heading', { name: 'Jane Citizen' })).toBeInTheDocument();
        expect(screen.getByText('jane@example.com')).toBeInTheDocument();
        const calls = screen.getByRole('table', { name: 'Latest calls' });
        expect(within(calls).getByText('Voicemail')).toBeInTheDocument();
        expect(within(calls).getByText('Pending')).toBeInTheDocument();
    });

    test('places a synchronous call, shows the outcome and refreshes the history', async () => {
        const user = userEvent.setup();
        let calls = 0;
        const requests = mockApi((request) => {
            if (request.method === 'POST') {
                calls++;
                return { status: 201, body: { data: makeCall({ outcome: 'busy' }) } };
            }
            return { body: { data: { ...contact, latest_calls: calls ? [makeCall({ outcome: 'busy' })] : [] } } };
        });
        renderApp(`/contacts/${contact.id}`);
        expect(await screen.findByText('No calls yet.')).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Call' }));

        const status = await screen.findByRole('status');
        expect(status).toHaveTextContent('Outcome: Busy');
        const post = requests.find((request) => request.method === 'POST')!;
        expect(post.url.pathname).toBe(`/api/v1/contacts/${contact.id}/calls`);
        expect(post.body).toEqual({ mode: 'sync' });
        await waitFor(() => expect(within(screen.getByRole('table', { name: 'Latest calls' })).getByText('Busy')).toBeInTheDocument());
    });

    test('the call button is disabled for a contact without a phone', async () => {
        mockApi(() => ({ body: { data: { ...contact, phone: null, latest_calls: [] } } }));

        renderApp(`/contacts/${contact.id}`);

        expect(await screen.findByRole('button', { name: 'Call' })).toBeDisabled();
    });

    test('deletes only after confirmation and returns to the list', async () => {
        const user = userEvent.setup();
        const requests = mockApi((request) => {
            if (request.method === 'DELETE') {
                return { status: 204 };
            }
            return request.url.pathname === '/api/v1/contacts' ? { body: page([]) } : { body: { data: { ...contact, latest_calls: [] } } };
        });
        renderApp(`/contacts/${contact.id}`);

        await user.click(await screen.findByRole('button', { name: 'Delete' }));
        const dialog = await screen.findByRole('dialog', { name: 'Delete contact' });
        await user.click(within(dialog).getByRole('button', { name: 'Cancel' }));
        expect(requests.some((request) => request.method === 'DELETE')).toBe(false);

        await user.click(screen.getByRole('button', { name: 'Delete' }));
        await user.click(within(await screen.findByRole('dialog', { name: 'Delete contact' })).getByRole('button', { name: 'Delete contact' }));

        expect(await screen.findByText('Contact deleted.')).toBeInTheDocument();
        expect(screen.getByRole('heading', { name: 'Contacts' })).toBeInTheDocument();
        expect(requests.find((request) => request.method === 'DELETE')!.url.pathname).toBe(`/api/v1/contacts/${contact.id}`);
    });

    test('shows a not found page for an unknown contact', async () => {
        mockApi(() => ({ status: 404, body: { message: 'Contact not found.' } }));

        renderApp(`/contacts/${contact.id}`);

        expect(await screen.findByRole('heading', { name: 'Not found' })).toBeInTheDocument();
        expect(screen.getByText('This contact does not exist or has been deleted.')).toBeInTheDocument();
    });

    test('shows call errors from the API', async () => {
        const user = userEvent.setup();
        mockApi((request) =>
            request.method === 'POST'
                ? { status: 429, body: { message: 'Too many requests. Please try again in 42 seconds.' }, headers: { 'Retry-After': '42' } }
                : { body: { data: { ...contact, latest_calls: [] } } },
        );
        renderApp(`/contacts/${contact.id}`);

        await user.click(await screen.findByRole('button', { name: 'Call' }));

        expect(await screen.findByRole('alert')).toHaveTextContent('Too many requests. Please try again in 42 seconds.');
    });
});
