import { zodResolver } from '@hookform/resolvers/zod';
import { Button, Group, SimpleGrid, Stack, TextInput } from '@mantine/core';
import { Controller, useForm } from 'react-hook-form';
import { ApiErrorAlert } from '@/components/ui/ApiErrorAlert';
import { ApiError, toApiError } from '@/lib/api-client';
import { useState } from 'react';
import { contactSchema, EMPTY_CONTACT_FORM, NAME_MAX_LENGTH, toContactInput, type ContactFormValues } from '../schemas/contactSchema';
import type { ContactInput } from '../types/contact';
import { PhoneField } from './PhoneField';

const FIELDS: (keyof ContactFormValues)[] = ['first_name', 'last_name', 'email', 'phone'];

interface ContactFormProps {
    defaultValues?: ContactFormValues;
    submitLabel: string;
    onSubmit: (input: ContactInput) => Promise<void>;
    /** Shows a Cancel button when given. */
    onCancel?: () => void;
}

export function ContactForm({ defaultValues = EMPTY_CONTACT_FORM, submitLabel, onSubmit, onCancel }: ContactFormProps) {
    const [serverError, setServerError] = useState<ApiError | null>(null);
    const {
        register,
        control,
        handleSubmit,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<ContactFormValues>({ resolver: zodResolver(contactSchema), defaultValues, mode: 'onTouched' });

    const submit = handleSubmit(async (values) => {
        setServerError(null);

        try {
            await onSubmit(toContactInput(values));
        } catch (reason) {
            const error = toApiError(reason);
            let mapped = false;

            // The backend is authoritative: show its field messages even when local validation passed.
            for (const [field, messages] of Object.entries(error.errors)) {
                if ((FIELDS as string[]).includes(field) && messages[0]) {
                    setError(field as keyof ContactFormValues, { type: 'server', message: messages[0] });
                    mapped = true;
                }
            }

            if (!mapped) {
                setServerError(error);
            }
        }
    });

    return (
        <form onSubmit={submit} noValidate aria-label="Contact details">
            <Stack gap="md">
                {serverError && <ApiErrorAlert error={serverError} title="Could not save the contact" />}

                <SimpleGrid cols={{ base: 1, sm: 2 }} spacing="md">
                    <TextInput label="First name" required maxLength={NAME_MAX_LENGTH} autoComplete="given-name" error={errors.first_name?.message} {...register('first_name')} />
                    <TextInput label="Last name" required maxLength={NAME_MAX_LENGTH} autoComplete="family-name" error={errors.last_name?.message} {...register('last_name')} />
                </SimpleGrid>

                <TextInput
                    label="Email"
                    type="email"
                    autoComplete="email"
                    description="Email or phone is required"
                    error={errors.email?.message}
                    {...register('email')}
                />

                <Controller
                    control={control}
                    name="phone"
                    render={({ field, fieldState }) => <PhoneField value={field.value} onChange={field.onChange} onBlur={field.onBlur} error={fieldState.error?.message} />}
                />

                {/* Without onCancel (e.g. inside a modal, which has its own close button) the submit button sits on the right. */}
                <Group gap="sm" justify={onCancel ? 'flex-start' : 'flex-end'}>
                    <Button type="submit" loading={isSubmitting}>
                        {submitLabel}
                    </Button>
                    {onCancel && (
                        <Button type="button" variant="default" onClick={onCancel} disabled={isSubmitting}>
                            Cancel
                        </Button>
                    )}
                </Group>
            </Stack>
        </form>
    );
}
