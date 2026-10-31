import { MantineProvider } from '@mantine/core';
import type { ReactNode } from 'react';
import { colorSchemeManager, theme } from './theme';

export function Providers({ children }: { children: ReactNode }) {
    return (
        <MantineProvider theme={theme} colorSchemeManager={colorSchemeManager} defaultColorScheme="auto">
            {children}
        </MantineProvider>
    );
}
