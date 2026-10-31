import { createTheme, localStorageColorSchemeManager } from '@mantine/core';

export const theme = createTheme({
    primaryColor: 'indigo',
    defaultRadius: 'md',
    fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
    headings: { fontWeight: '600' },
    cursorType: 'pointer',
    // Visible keyboard focus ring on every interactive element.
    focusRing: 'auto',
});

export const COLOR_SCHEME_STORAGE_KEY = 'rex-crm-color-scheme';

export const colorSchemeManager = localStorageColorSchemeManager({ key: COLOR_SCHEME_STORAGE_KEY });
