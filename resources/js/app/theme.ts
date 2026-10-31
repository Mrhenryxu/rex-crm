import { AppShell, Button, createTheme, Input, localStorageColorSchemeManager, Title, type CSSVariablesResolver, type MantineColorsTuple } from '@mantine/core';
import classes from './theme.module.css';

/*
 * Design tokens from rexsoftware.com (:root custom properties in its Webflow stylesheet).
 * "Derived" values are the site's color-mix() formulas resolved to HEX; they are not declared as HEX there.
 */

// Shades 1–9 are --swatch--brand-100…900 (500 = #3DE0BF; the others are derived).
// Shade 0 is an added step (#3DE0BF mixed with 90% white) because Mantine needs ten shades.
const rex: MantineColorsTuple = ['#ECFCF9', '#D8F9F2', '#B1F3E5', '#8BECD9', '#64E6CC', '#3DE0BF', '#31B399', '#258673', '#185A4C', '#0C2D26'];

// Dark surfaces: shade 7 is --swatch--dark-900 (#1C1A33, Rex's dark theme background).
// The other shades are derived from it, as Rex has no full dark palette.
const dark: MantineColorsTuple = ['#D2D1D6', '#B0AFB8', '#8E8D99', '#6B6A7A', '#4E4C60', '#37354B', '#2A283F', '#1C1A33', '#161529', '#11101F'];

export const REX_TOKENS = {
    text: '#1C1A33', // --swatch--dark-900 / --_theme---text
    navy: '#0F2B66', // --swatch--navy / --_theme---heading-accent
    mint: '#3DE0BF', // --swatch--brand-500
    surface: '#F7F5F1', // --swatch--wheat-400 / --_theme---background-2
    border: '#E6E2D8', // --swatch--wheat-600 / --_theme---border
    muted: '#8A8782', // --swatch--wheat-700 / --_theme---icon (icons and large text only: 3.6:1 on white)
} as const;

export const theme = createTheme({
    colors: { rex, dark },
    primaryColor: 'rex',
    primaryShade: { light: 5, dark: 5 },
    white: '#FFFFFF',
    black: REX_TOKENS.text,
    // Mint is a light colour: filled components get the dark brand text, as Rex's buttons do (about 10:1).
    autoContrast: true,
    fontFamily: 'Inter, Arial, sans-serif',
    headings: {
        fontFamily: '"Sofia Sans Extra Condensed", Arial, sans-serif',
        fontWeight: '700',
        // Rex's marketing display sizes (up to 7rem) don't suit a CRM, so the app uses its smaller h4/h5/h6 tokens.
        sizes: {
            h1: { fontSize: '2.25rem', lineHeight: '0.9' }, // Rex h4 range, lh --line-height--extra-small
            h2: { fontSize: '1.5rem', lineHeight: '1.1' }, // Rex h5 max, lh --line-height--medium
            h3: { fontSize: '1.25rem', lineHeight: '1.1' },
            h4: { fontSize: '1.125rem', lineHeight: '1.3' },
        },
    },
    fontSizes: { xs: '0.75rem', sm: '0.875rem', md: '1rem', lg: '1.125rem', xl: '1.25rem' }, // eyebrow, small, main, large
    lineHeights: { md: '1.5' }, // --_typography---line-height--huge (body text)
    radius: { xs: '0.25rem', sm: '0.5rem', md: '0.875rem', lg: '1.25rem', xl: '2rem' }, // --radius--small / main / large
    defaultRadius: 'md',
    cursorType: 'pointer',
    // Rex's focus ring: 0.125rem outline, 0.1875rem offset, in a colour with enough contrast.
    focusClassName: classes.focus,
    components: {
        Button: Button.extend({ classNames: { root: classes.button } }),
        Input: Input.extend({ defaultProps: { radius: 'sm' } }), // form inputs are 8px on the site
        Title: Title.extend({ classNames: { root: classes.title } }),
        AppShell: AppShell.extend({ classNames: { main: classes.main } }),
    },
});

export const cssVariablesResolver: CSSVariablesResolver = () => ({
    variables: {},
    light: {
        '--mantine-color-body': '#FFFFFF',
        '--mantine-color-text': REX_TOKENS.text,
        '--mantine-color-dimmed': 'rgb(28 26 51 / 0.8)', // --_theme---text-secondary
        '--mantine-color-default-border': REX_TOKENS.border,
        // Mint fails contrast on white (1.7:1), so links and mint text use darker tokens.
        '--mantine-color-anchor': REX_TOKENS.text,
        '--mantine-color-rex-text': 'var(--mantine-color-rex-8)',
        '--mantine-color-rex-outline': 'var(--mantine-color-rex-7)',
        '--rex-heading-color': REX_TOKENS.navy,
        '--rex-surface': REX_TOKENS.surface,
        '--rex-focus-color': REX_TOKENS.text,
        '--rex-button-hover-bg': REX_TOKENS.text,
        '--rex-button-hover-color': '#FFFFFF',
    },
    dark: {
        '--mantine-color-body': 'var(--mantine-color-dark-7)', // .u-theme-dark background
        '--mantine-color-text': '#FFFFFF',
        '--mantine-color-dimmed': 'rgb(255 255 255 / 0.8)',
        // Mantine's default dark "light" tint (rex-9 darkened) is nearly invisible on #1C1A33; use a mint tint instead.
        '--mantine-color-rex-light': 'rgb(61 224 191 / 0.15)',
        '--mantine-color-rex-light-hover': 'rgb(61 224 191 / 0.22)',
        '--rex-heading-color': REX_TOKENS.mint, // .u-theme-dark heading accent
        '--rex-surface': 'var(--mantine-color-dark-8)',
        '--rex-focus-color': REX_TOKENS.mint,
        '--rex-button-hover-bg': '#FFFFFF', // Rex buttons on dark sections hover to white
        '--rex-button-hover-color': REX_TOKENS.text,
    },
});

export const COLOR_SCHEME_STORAGE_KEY = 'rex-crm-color-scheme';

export const colorSchemeManager = localStorageColorSchemeManager({ key: COLOR_SCHEME_STORAGE_KEY });
