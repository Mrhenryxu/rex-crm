import { MantineProvider } from '@mantine/core';
import type { ReactNode } from 'react';
import { colorSchemeManager, theme } from './theme';

/** `env="test"` turns off Mantine transitions and portals so component tests are deterministic. */
export function Providers({ children, env = 'default' }: { children: ReactNode; env?: 'default' | 'test' }) {
    return (
        <MantineProvider theme={theme} colorSchemeManager={colorSchemeManager} defaultColorScheme="auto" env={env}>
            {children}
        </MantineProvider>
    );
}
