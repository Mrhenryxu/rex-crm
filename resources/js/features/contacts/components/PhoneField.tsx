import { Group, TextInput } from '@mantine/core';
import { parsePhoneNumberFromString } from 'libphonenumber-js/min';
import { useState } from 'react';
import { CountryFlag, countryOfPhone, type SupportedCountry } from './CountryFlag';
import classes from './PhoneField.module.css';

const COUNTRIES: { value: SupportedCountry; label: string; name: string }[] = [
    { value: 'AU', label: '+61', name: 'Australia' },
    { value: 'NZ', label: '+64', name: 'New Zealand' },
];

function display(e164: string): string {
    return e164 === '' ? '' : (parsePhoneNumberFromString(e164)?.formatNational() ?? e164);
}

/** Converts local or international input for the chosen country to E.164. Unparseable input is passed through for validation to reject. */
export function toE164(raw: string, country: SupportedCountry): string {
    const trimmed = raw.trim();

    if (trimmed === '') {
        return '';
    }

    return parsePhoneNumberFromString(trimmed, country)?.number ?? trimmed;
}

interface PhoneFieldProps {
    value: string;
    onChange: (e164: string) => void;
    onBlur?: () => void;
    error?: string | undefined;
}

/**
 * One field: an AU/NZ country picker (flag and dialling code) inside the phone input.
 * Input assistance only: national-format display and E.164 conversion. The backend validates independently.
 */
export function PhoneField({ value, onChange, onBlur, error }: PhoneFieldProps) {
    const [country, setCountry] = useState<SupportedCountry>(countryOfPhone(value) ?? 'AU');
    const [text, setText] = useState(display(value));

    const change = (raw: string, selectedCountry: SupportedCountry) => {
        const e164 = toE164(raw, selectedCountry);

        // Typing "+64 ..." while Australia is selected switches the country.
        const detected = countryOfPhone(e164);
        if (detected !== null && detected !== selectedCountry) {
            setCountry(detected);
        }

        onChange(e164);
    };

    const description = value !== '' && value !== text.trim() ? `Saved as ${value}` : 'Mobile or landline, e.g. 0412 345 678';

    const countryPicker = (
        <Group gap={6} wrap="nowrap" className={classes.picker}>
            <CountryFlag country={country} />
            <select
                aria-label="Country"
                className={classes.select}
                value={country}
                onChange={(event) => {
                    const next = event.currentTarget.value as SupportedCountry;
                    setCountry(next);
                    change(text, next);
                }}
            >
                {COUNTRIES.map((option) => (
                    <option key={option.value} value={option.value} aria-label={`${option.name} (${option.label})`}>
                        {option.label}
                    </option>
                ))}
            </select>
        </Group>
    );

    return (
        <TextInput
            label="Phone"
            type="tel"
            inputMode="tel"
            autoComplete="tel"
            placeholder={country === 'NZ' ? '021 123 4567' : '0412 345 678'}
            value={text}
            description={description}
            error={error}
            leftSection={countryPicker}
            leftSectionWidth={96}
            leftSectionPointerEvents="all"
            // Space between the country divider and the number.
            styles={{ input: { paddingInlineStart: 'calc(96px + var(--mantine-spacing-sm))' } }}
            onChange={(event) => {
                const raw = event.currentTarget.value;
                setText(raw);
                change(raw, country);
            }}
            onBlur={() => {
                // Format on blur rather than while typing, so editing never fights the cursor.
                if (value !== '' && parsePhoneNumberFromString(value)?.isPossible()) {
                    setText(display(value));
                }
                onBlur?.();
            }}
        />
    );
}
