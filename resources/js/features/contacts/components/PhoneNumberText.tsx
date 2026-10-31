import { Group, Text } from '@mantine/core';
import { formatPhone } from '../utils/format';
import { CountryFlag, countryOfPhone } from './CountryFlag';

/** An E.164 number shown in international format with its country flag in front. */
export function PhoneNumberText({ phone }: { phone: string }) {
    const country = countryOfPhone(phone);

    return (
        <Group gap={8} wrap="nowrap" component="span">
            {country && <CountryFlag country={country} />}
            <Text span inherit style={{ whiteSpace: 'nowrap' }}>
                {formatPhone(phone)}
            </Text>
        </Group>
    );
}
