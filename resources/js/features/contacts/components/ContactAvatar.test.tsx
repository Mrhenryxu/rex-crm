import { screen } from '@testing-library/react';
import { renderWithProviders } from '@/test/render';
import { ContactAvatar, initials } from './ContactAvatar';

describe('ContactAvatar', () => {
    test('initials come from the first letters of first and last name', () => {
        expect(initials('jane', 'citizen')).toBe('JC');
        expect(initials('Mary Anne', 'Smith')).toBe('MS');
        expect(initials(' Ēmma ', 'Ōtaki')).toBe('ĒŌ');
    });

    test('renders the initials and is hidden from assistive technology', () => {
        renderWithProviders(<ContactAvatar firstName="Aroha" lastName="Ngata" />);

        const avatar = screen.getByTestId('contact-avatar');
        expect(avatar).toHaveTextContent('AN');
        expect(avatar).toHaveAttribute('aria-hidden', 'true');
    });
});
