import { z } from 'zod';
import type { ContactInput } from '../types/contact';

/**
 * Mirrors App\Features\Contacts\Rules\ContactRules for instant feedback only.
 * The API validates every request itself; its errors are always shown as well.
 */
export const AU_NZ_E164 = /^(?:\+61[23478]\d{8}|\+64(?:[34679]\d{7}|2\d{7,9}))$/;

export const NAME_MAX_LENGTH = 100;
const EMAIL_MAX_LENGTH = 254;
const CONTACT_METHOD_REQUIRED = 'Provide an email address or a phone number. At least one is required.';

const name = (label: string) =>
    z
        .string()
        .trim()
        .min(1, `${label} is required.`)
        .max(NAME_MAX_LENGTH, `${label} must be ${NAME_MAX_LENGTH} characters or fewer.`);

export const contactSchema = z
    .object({
        first_name: name('First name'),
        last_name: name('Last name'),
        email: z
            .string()
            .trim()
            .max(EMAIL_MAX_LENGTH, `Email must be ${EMAIL_MAX_LENGTH} characters or fewer.`)
            .refine((value) => value === '' || z.email().safeParse(value).success, 'Enter a valid email address.'),
        phone: z
            .string()
            .trim()
            .refine((value) => value === '' || AU_NZ_E164.test(value), 'Enter a valid Australian or New Zealand phone number.'),
    })
    .superRefine((values, context) => {
        if (values.email === '' && values.phone === '') {
            context.addIssue({ code: 'custom', path: ['email'], message: CONTACT_METHOD_REQUIRED });
            context.addIssue({ code: 'custom', path: ['phone'], message: CONTACT_METHOD_REQUIRED });
        }
    });

export type ContactFormValues = z.infer<typeof contactSchema>;

export const EMPTY_CONTACT_FORM: ContactFormValues = { first_name: '', last_name: '', email: '', phone: '' };

export function toContactInput(values: ContactFormValues): ContactInput {
    return {
        first_name: values.first_name.trim(),
        last_name: values.last_name.trim(),
        email: values.email.trim() === '' ? null : values.email.trim(),
        phone: values.phone.trim() === '' ? null : values.phone.trim(),
    };
}
