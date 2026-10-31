import { Group, Select, TextInput } from '@mantine/core';
import { parsePhoneNumberFromString, type CountryCode } from 'libphonenumber-js/min';
import { useId, useState } from 'react';

type SupportedCountry = Extract<CountryCode, 'AU' | 'NZ'>;

const COUNTRIES: { value: SupportedCountry; label: string }[] = [
    { value: 'AU', label: 'Australia (+61)' },
    { value: 'NZ', label: 'New Zealand (+64)' },
];

function countryOf(e164: string): SupportedCountry {
    return e164.startsWith('+64') ? 'NZ' : 'AU';
}

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
 * Input assistance only: AU/NZ country selection, national-format display and E.164 conversion.
 * The backend validates the submitted number independently.
 */
export function PhoneField({ value, onChange, onBlur, error }: PhoneFieldProps) {
    const [country, setCountry] = useState<SupportedCountry>(countryOf(value));
    const [text, setText] = useState(display(value));
    const descriptionId = useId();

    const change = (raw: string, selectedCountry: SupportedCountry) => {
        const e164 = toE164(raw, selectedCountry);

        // Typing "+64 ..." while Australia is selected switches the country.
        if (e164.startsWith('+61') || e164.startsWith('+64')) {
            const detected = countryOf(e164);
            if (detected !== selectedCountry) {
                setCountry(detected);
            }
        }

        onChange(e164);
    };

    const preview = value !== '' && value !== text.trim() ? `Saved as ${value}` : 'Mobile or landline, e.g. 0412 345 678';

    return (
        <Group align="flex-start" gap="xs" wrap="nowrap">
            <Select
                label="Country"
                data={COUNTRIES}
                value={country}
                allowDeselect={false}
                onChange={(next) => {
                    if (next === 'AU' || next === 'NZ') {
                        setCountry(next);
                        change(text, next);
                    }
                }}
                w={190}
            />
            <TextInput
                label="Phone"
                type="tel"
                inputMode="tel"
                autoComplete="tel"
                placeholder={country === 'NZ' ? '021 123 4567' : '0412 345 678'}
                value={text}
                description={<span id={descriptionId}>{preview}</span>}
                error={error}
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
                style={{ flex: 1 }}
            />
        </Group>
    );
}
