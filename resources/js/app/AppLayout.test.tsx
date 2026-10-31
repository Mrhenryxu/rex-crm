import { act, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { renderApp } from '@/test/render';

describe('CRM shell', () => {
    test('has a banner with the product name, main navigation and main content', () => {
        renderApp('/contacts');

        const banner = screen.getByRole('banner');
        expect(within(banner).getByText('Rex CRM')).toBeInTheDocument();
        expect(within(banner).getByRole('button', { name: /switch to (dark|light) mode/i })).toBeInTheDocument();
        expect(screen.getByRole('navigation', { name: 'Main navigation' })).toBeInTheDocument();
        expect(screen.getByRole('main')).toBeInTheDocument();
    });

    test('marks the current navigation item', () => {
        renderApp('/contacts');
        const nav = screen.getByRole('navigation', { name: 'Main navigation' });

        expect(within(nav).getByRole('link', { name: 'Contacts' })).toHaveAttribute('aria-current', 'page');
        expect(within(nav).getByRole('link', { name: 'New contact' })).not.toHaveAttribute('aria-current');
    });

    test('collapses to icon-only items that keep their accessible names', async () => {
        const user = userEvent.setup();
        renderApp('/contacts');
        const nav = screen.getByRole('navigation', { name: 'Main navigation' });

        expect(within(nav).getByText('Contacts')).toBeVisible();

        await user.click(screen.getByRole('button', { name: 'Collapse navigation' }));

        expect(within(nav).queryByText('Contacts')).not.toBeInTheDocument();
        expect(within(nav).getByRole('link', { name: 'Contacts' })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: 'Expand navigation' })).toHaveAttribute('aria-expanded', 'false');
        expect(window.localStorage.getItem('rex-crm-nav-collapsed')).toBe('true');

        await user.click(screen.getByRole('button', { name: 'Expand navigation' }));

        expect(within(nav).getByText('Contacts')).toBeVisible();
    });

    test('collapsed items show a tooltip with their label on keyboard focus', async () => {
        window.localStorage.setItem('rex-crm-nav-collapsed', 'true');
        renderApp('/contacts');
        const link = within(screen.getByRole('navigation', { name: 'Main navigation' })).getByRole('link', { name: 'Contacts' });

        act(() => link.focus());

        expect(link).toHaveFocus();
        expect(await screen.findByRole('tooltip')).toHaveTextContent('Contacts');
    });

    test('theme switch toggles and remembers the colour scheme', async () => {
        const user = userEvent.setup();
        renderApp('/contacts');

        await user.click(screen.getByRole('button', { name: 'Switch to dark mode' }));

        expect(document.documentElement).toHaveAttribute('data-mantine-color-scheme', 'dark');
        expect(window.localStorage.getItem('rex-crm-color-scheme')).toBe('dark');
        expect(screen.getByRole('button', { name: 'Switch to light mode' })).toBeInTheDocument();
    });

    test('unknown routes show a not found page inside the shell', () => {
        renderApp('/nowhere');

        expect(screen.getByRole('heading', { name: 'Not found' })).toBeInTheDocument();
        expect(screen.getByRole('navigation', { name: 'Main navigation' })).toBeInTheDocument();
    });
});
