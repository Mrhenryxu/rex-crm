import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from 'react';
import { renderWithProviders } from '@/test/render';
import { PhoneField, toE164 } from './PhoneField';

function Harness({ initial = '', onValue }: { initial?: string; onValue: (value: string) => void }) {
    const [value, setValue] = useState(initial);

    return (
        <PhoneField
            value={value}
            onChange={(next) => {
                setValue(next);
                onValue(next);
            }}
        />
    );
}

describe('PhoneField', () => {
    test('is one field: the country picker sits inside the phone input', () => {
        renderWithProviders(<Harness onValue={() => {}} />);

        const input = screen.getByRole('textbox', { name: 'Phone' });
        const picker = screen.getByRole('combobox', { name: 'Country' });
        expect(input.parentElement).toContainElement(picker);
        expect(screen.getByRole('option', { name: 'Australia (+61)' })).toBeInTheDocument();
        expect(screen.getByRole('img', { name: 'Australia' })).toBeInTheDocument();
    });

    test('converts local AU and NZ input to E.164', () => {
        expect(toE164('0412 345 678', 'AU')).toBe('+61412345678');
        expect(toE164('021 123 4567', 'NZ')).toBe('+64211234567');
        expect(toE164('(09) 123 4567', 'NZ')).toBe('+6491234567');
        expect(toE164('', 'AU')).toBe('');
    });

    test('passes unparseable input through so validation can reject it', () => {
        expect(toE164('abc', 'AU')).toBe('abc');
    });

    test('emits E.164 while typing and formats on blur', async () => {
        const user = userEvent.setup();
        const values: string[] = [];
        renderWithProviders(<Harness onValue={(value) => values.push(value)} />);
        const input = screen.getByRole('textbox', { name: 'Phone' });

        await user.type(input, '0412345678');
        expect(values.at(-1)).toBe('+61412345678');
        expect(screen.getByText('Saved as +61412345678')).toBeInTheDocument();

        await user.tab();
        expect(input).toHaveValue('0412 345 678');
    });

    test('switching country re-interprets the local number', async () => {
        const user = userEvent.setup();
        const values: string[] = [];
        renderWithProviders(<Harness onValue={(value) => values.push(value)} />);

        await user.type(screen.getByRole('textbox', { name: 'Phone' }), '0211234567');
        await user.selectOptions(screen.getByRole('combobox', { name: 'Country' }), 'NZ');

        expect(values.at(-1)).toBe('+64211234567');
    });

    test('international input selects the matching country', async () => {
        const user = userEvent.setup();
        renderWithProviders(<Harness onValue={() => {}} />);

        await user.type(screen.getByRole('textbox', { name: 'Phone' }), '+64 21 123 4567');

        expect(screen.getByRole('combobox', { name: 'Country' })).toHaveValue('NZ');
        expect(screen.getByRole('img', { name: 'New Zealand' })).toBeInTheDocument();
    });

    test('shows an existing number in national format with its country', () => {
        renderWithProviders(<Harness initial="+6493012345" onValue={() => {}} />);

        expect(screen.getByRole('textbox', { name: 'Phone' })).toHaveValue("09 301 2345");
        expect(screen.getByRole('combobox', { name: 'Country' })).toHaveValue('NZ');
    });
});
