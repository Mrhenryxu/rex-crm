import { screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { makeContact, mockApi, page } from '@/test/api';
import { renderApp } from '@/test/render';

const lastRequest = (requests: { url: URL }[]) => requests[requests.length - 1]!.url;

describe('Contacts listing', () => {
    test('renders the page returned by the API with initials avatars', async () => {
        mockApi(() => ({ body: page([makeContact({ first_name: 'aroha', last_name: 'ngata', phone: null }), makeContact({ id: 'b', first_name: 'John', last_name: 'Smith' })]) }));

        renderApp('/contacts');

        const table = await screen.findByRole('table', { name: 'Contacts' });
        const rows = within(table).getAllByRole('row');
        expect(rows).toHaveLength(3);
        expect(within(rows[1]!).getByRole('link', { name: 'aroha ngata' })).toHaveAttribute('href', `/contacts/${makeContact().id}`);
        expect(within(rows[1]!).getByTestId('contact-avatar')).toHaveTextContent('AN');
        expect(within(rows[2]!).getByTestId('contact-avatar')).toHaveTextContent('JS');
        expect(within(rows[2]!).getByText('+61 412 345 678')).toBeInTheDocument();
    });

    test('loads the first page with the default backend parameters', async () => {
        const requests = mockApi(() => ({ body: page([]) }));

        renderApp('/contacts');

        await screen.findByText('No contacts found');
        const url = lastRequest(requests);
        expect(url.pathname).toBe('/api/v1/contacts');
        expect(Object.fromEntries(url.searchParams)).toEqual({ sort: 'last_name', direction: 'asc', page: '1', per_page: '15' });
    });

    test('typing does not search; the Search button sends the filters to the API', async () => {
        const user = userEvent.setup();
        const requests = mockApi(() => ({ body: page([makeContact()]) }));
        renderApp('/contacts?page=3');
        await screen.findByRole('table', { name: 'Contacts' });
        const before = requests.length;

        await user.type(screen.getByRole('textbox', { name: /^name/i }), 'jo smi');
        await user.type(screen.getByRole('textbox', { name: /^phone/i }), '+61412');
        await user.type(screen.getByRole('textbox', { name: /^email domain/i }), 'acme.com');
        expect(requests).toHaveLength(before);

        await user.click(screen.getByRole('button', { name: 'Search' }));

        await waitFor(() => expect(requests.length).toBeGreaterThan(before));
        const url = lastRequest(requests);
        expect(url.searchParams.get('name')).toBe('jo smi');
        expect(url.searchParams.get('phone')).toBe('+61412');
        expect(url.searchParams.get('email_domain')).toBe('acme.com');
        expect(url.searchParams.get('page')).toBe('1');
    });

    test('Clear resets the filters and reloads the default list', async () => {
        const user = userEvent.setup();
        const requests = mockApi(() => ({ body: page([makeContact()]) }));
        renderApp('/contacts?name=jo&sort=created_at&direction=desc&page=2');
        await screen.findByRole('table', { name: 'Contacts' });
        expect(screen.getByRole('textbox', { name: /^name/i })).toHaveValue('jo');

        await user.click(screen.getByRole('button', { name: 'Clear' }));

        await waitFor(() => expect(screen.getByRole('textbox', { name: /^name/i })).toHaveValue(''));
        expect(Object.fromEntries(lastRequest(requests).searchParams)).toEqual({ sort: 'last_name', direction: 'asc', page: '1', per_page: '15' });
    });

    test('sorting and pagination are requested from the backend', async () => {
        const user = userEvent.setup();
        const requests = mockApi((request) => ({
            body: page([makeContact()], { current_page: Number(request.url.searchParams.get('page')), last_page: 3, total: 40, from: 1, to: 15 }),
        }));
        renderApp('/contacts');
        await screen.findByRole('table', { name: 'Contacts' });

        await user.click(screen.getByRole('button', { name: 'Page 2' }));
        await waitFor(() => expect(lastRequest(requests).searchParams.get('page')).toBe('2'));

        await user.click(screen.getByRole('combobox', { name: 'Sort by' }));
        await user.click(await screen.findByRole('option', { name: 'Newest first' }));

        await waitFor(() => expect(lastRequest(requests).searchParams.get('sort')).toBe('created_at'));
        expect(lastRequest(requests).searchParams.get('direction')).toBe('desc');
        expect(lastRequest(requests).searchParams.get('page')).toBe('1');
        expect(screen.getByRole('columnheader', { name: 'Added' })).toHaveAttribute('aria-sort', 'descending');

        await user.click(screen.getByRole('combobox', { name: 'Contacts per page' }));
        await user.click(await screen.findByRole('option', { name: '50 per page' }));
        await waitFor(() => expect(lastRequest(requests).searchParams.get('per_page')).toBe('50'));
    });

    test('shows skeletons while the first page loads', async () => {
        let release: () => void = () => {};
        mockApi(() => new Promise((resolve) => (release = () => resolve({ body: page([makeContact()]) }))));

        renderApp('/contacts');

        expect(screen.getByLabelText('Loading contacts')).toBeInTheDocument();
        release();
        expect(await screen.findByRole('table', { name: 'Contacts' })).toBeInTheDocument();
    });

    test('shows an empty state that suggests clearing filters', async () => {
        mockApi(() => ({ body: page([]) }));

        renderApp('/contacts?name=zzz');

        expect(await screen.findByText('No contacts found')).toBeInTheDocument();
        expect(screen.getByText(/clear them to see every contact/)).toBeInTheDocument();
    });

    test('shows backend validation errors for invalid filters', async () => {
        mockApi(() => ({
            status: 422,
            body: { message: 'The phone filter must be the start of an E.164 number.', errors: { phone: ['The phone filter must be the start of an E.164 number.'] } },
        }));

        renderApp('/contacts?phone=abc');

        const alert = await screen.findByRole('alert');
        expect(alert).toHaveTextContent('Could not load contacts');
        expect(alert).toHaveTextContent('The phone filter must be the start of an E.164 number.');
    });

    test('shows the retry time when rate limited', async () => {
        mockApi(() => ({ status: 429, body: { message: 'Too many requests. Please try again in 30 seconds.' }, headers: { 'Retry-After': '30' } }));

        renderApp('/contacts');

        expect(await screen.findByRole('alert')).toHaveTextContent('Too many requests. Please try again in 30 seconds.');
    });
});
