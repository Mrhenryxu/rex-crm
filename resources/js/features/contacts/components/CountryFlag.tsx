import { AU, NZ } from 'country-flag-icons/react/3x2';

export type SupportedCountry = 'AU' | 'NZ';

const COUNTRY_NAMES: Record<SupportedCountry, string> = { AU: 'Australia', NZ: 'New Zealand' };

/** The country of a stored E.164 number, or null if it is neither AU nor NZ. */
export function countryOfPhone(e164: string | null | undefined): SupportedCountry | null {
    if (e164?.startsWith('+61')) {
        return 'AU';
    }

    return e164?.startsWith('+64') ? 'NZ' : null;
}

/**
 * SVG flag (emoji flags don't render on Windows or many Linux browsers).
 * Labelled with the country name, so the flag also tells screen reader users which country the number is in.
 */
export function CountryFlag({ country, width = 20 }: { country: SupportedCountry; width?: number }) {
    const Flag = country === 'AU' ? AU : NZ;
    const name = COUNTRY_NAMES[country];

    return (
        <Flag
            role="img"
            aria-label={name}
            title={name}
            width={width}
            height={(width * 2) / 3}
            style={{ display: 'block', flexShrink: 0, borderRadius: 2, boxShadow: '0 0 0 1px rgb(0 0 0 / 0.08)' }}
        />
    );
}
