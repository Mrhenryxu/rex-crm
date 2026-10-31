import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { makeContact, mockApi } from '@/test/api';
import { renderApp } from '@/test/render';

const contact = makeContact();

describe('Contact create and edit forms', () => {
    test('blocks submission without an email or phone and explains why', async () => {
        const user = userEvent.setup();
        const requests = mockApi(() => ({ body: {} }));
        renderApp('/contacts/new');

        await user.type(screen.getByRole('textbox', { name: /first name/i }), 'Jane');
        await user.type(screen.getByRole('textbox', { name: /last name/i }), 'Citizen');
        await user.click(screen.getByRole('button', { name: 'Create contact' }));

        expect(await screen.findAllByText('Provide an email address or a phone number. At least one is required.')).toHaveLength(2);
        expect(requests).toHaveLength(0);
    });

    test('creates a phone-only contact with an E.164 number and opens it', async () => {
        const user = userEvent.setup();
        const requests = mockApi((request) =>
            request.method === 'POST'
                ? { status: 201, body: { data: makeContact({ email: null }) } }
                : { body: { data: { ...makeContact({ email: null }), latest_calls: [] } } },
        );
        renderApp('/contacts/new');

        await user.type(screen.getByRole('textbox', { name: /first name/i }), 'Jane');
        await user.type(screen.getByRole('textbox', { name: /last name/i }), 'Citizen');
        await user.type(screen.getByRole('textbox', { name: 'Phone' }), '0412 345 678');
        await user.click(screen.getByRole('button', { name: 'Create contact' }));

        expect(await screen.findByText('Contact created.')).toBeInTheDocument();
        const post = requests.find((request) => request.method === 'POST')!;
        expect(post.url.pathname).toBe('/api/v1/contacts');
        expect(post.body).toEqual({ first_name: 'Jane', last_name: 'Citizen', email: null, phone: '+61412345678' });
        // The notice renders before the contact has loaded, so wait for the heading.
        expect(await screen.findByRole('heading', { name: 'Jane Citizen' })).toBeInTheDocument();
    });

    test('says when the email matched an existing contact', async () => {
        const user = userEvent.setup();
        mockApi((request) => (request.method === 'POST' ? { status: 200, body: { data: contact } } : { body: { data: { ...contact, latest_calls: [] } } }));
        renderApp('/contacts/new');

        await user.type(screen.getByRole('textbox', { name: /first name/i }), 'Jane');
        await user.type(screen.getByRole('textbox', { name: /last name/i }), 'Citizen');
        await user.type(screen.getByRole('textbox', { name: /email/i }), 'jane@example.com');
        await user.click(screen.getByRole('button', { name: 'Create contact' }));

        expect(await screen.findByText('A contact with this email already existed, so it was updated.')).toBeInTheDocument();
    });

    test('shows backend field errors on the matching fields', async () => {
        const user = userEvent.setup();
        mockApi((request) =>
            request.method === 'PUT'
                ? { status: 409, body: { message: 'The email address already belongs to another contact.', errors: { email: ['The email address already belongs to another contact.'] } } }
                : { body: { data: { ...contact, latest_calls: [] } } },
        );
        renderApp(`/contacts/${contact.id}/edit`);

        const email = await screen.findByRole('textbox', { name: /email/i });
        expect(email).toHaveValue('jane@example.com');
        await user.clear(email);
        await user.type(email, 'taken@example.com');
        await user.click(screen.getByRole('button', { name: 'Save changes' }));

        expect(await screen.findByText('The email address already belongs to another contact.')).toBeInTheDocument();
        expect(email).toHaveAttribute('aria-invalid', 'true');
    });

    test('updates a contact with PUT and returns to its details', async () => {
        const user = userEvent.setup();
        const requests = mockApi((request) =>
            request.method === 'PUT' ? { body: { data: { ...contact, first_name: 'Janet' } } } : { body: { data: { ...contact, latest_calls: [] } } },
        );
        renderApp(`/contacts/${contact.id}/edit`);

        const firstName = await screen.findByRole('textbox', { name: /first name/i });
        await user.clear(firstName);
        await user.type(firstName, 'Janet');
        await user.click(screen.getByRole('button', { name: 'Save changes' }));

        expect(await screen.findByText('Contact updated.')).toBeInTheDocument();
        const put = requests.find((request) => request.method === 'PUT')!;
        expect(put.url.pathname).toBe(`/api/v1/contacts/${contact.id}`);
        expect(put.body).toEqual({ first_name: 'Janet', last_name: 'Citizen', email: 'jane@example.com', phone: '+61412345678' });
    });

    test('disables the submit button while saving', async () => {
        const user = userEvent.setup();
        let release: () => void = () => {};
        mockApi((request) =>
            request.method === 'POST' ? new Promise((resolve) => (release = () => resolve({ status: 201, body: { data: contact } }))) : { body: { data: { ...contact, latest_calls: [] } } },
        );
        renderApp('/contacts/new');

        await user.type(screen.getByRole('textbox', { name: /first name/i }), 'Jane');
        await user.type(screen.getByRole('textbox', { name: /last name/i }), 'Citizen');
        await user.type(screen.getByRole('textbox', { name: /email/i }), 'jane@example.com');
        await user.click(screen.getByRole('button', { name: 'Create contact' }));

        await waitFor(() => expect(screen.getByRole('button', { name: 'Create contact' })).toHaveAttribute('data-loading', 'true'));
        release();
        expect(await screen.findByText('Contact created.')).toBeInTheDocument();
    });
});
