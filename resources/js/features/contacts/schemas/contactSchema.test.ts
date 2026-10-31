import { contactSchema, toContactInput } from './contactSchema';

const base = { first_name: 'Jane', last_name: 'Citizen', email: '', phone: '' };

function issues(values: typeof base): Record<string, string[]> {
    const result = contactSchema.safeParse(values);

    return result.success ? {} : (result.error.flatten().fieldErrors as Record<string, string[]>);
}

describe('contact schema', () => {
    test('requires an email or a phone number', () => {
        const errors = issues(base);

        expect(errors.email).toContain('Provide an email address or a phone number. At least one is required.');
        expect(errors.phone).toContain('Provide an email address or a phone number. At least one is required.');
    });

    test('accepts email only, phone only, or both', () => {
        expect(issues({ ...base, email: 'jane@example.com' })).toEqual({});
        expect(issues({ ...base, phone: '+61412345678' })).toEqual({});
        expect(issues({ ...base, email: 'jane@example.com', phone: '+6421123456' })).toEqual({});
    });

    test('validates both methods when both are given', () => {
        expect(issues({ ...base, email: 'jane@example.com', phone: '+14155552671' }).phone).toEqual(['Enter a valid Australian or New Zealand phone number.']);
        expect(issues({ ...base, email: 'nope', phone: '+61412345678' }).email).toEqual(['Enter a valid email address.']);
    });

    test('requires trimmed first and last names within 100 characters', () => {
        expect(issues({ ...base, email: 'a@b.com', first_name: '   ' }).first_name).toEqual(['First name is required.']);
        expect(issues({ ...base, email: 'a@b.com', last_name: 'x'.repeat(101) }).last_name).toEqual(['Last name must be 100 characters or fewer.']);
    });

    test('converts empty contact methods to null for the API', () => {
        expect(toContactInput({ first_name: ' Jane ', last_name: 'Citizen', email: ' ', phone: '+61412345678' })).toEqual({
            first_name: 'Jane',
            last_name: 'Citizen',
            email: null,
            phone: '+61412345678',
        });
    });
});
