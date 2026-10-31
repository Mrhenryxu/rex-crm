import { Anchor } from '@mantine/core';
import { Link } from 'react-router';
import logoMarkup from '@/assets/rex-logo.svg?raw';
import classes from './Logo.module.css';

// Build-time asset, not user input. The navy wordmark follows the text colour so it stays legible in dark mode;
// the mint mark and the light-mode colours are unchanged from the original file.
const markup = logoMarkup.replaceAll('fill="#0F2B66"', 'fill="currentColor"').replace('<svg ', '<svg aria-hidden="true" focusable="false" ');

export function Logo() {
    return (
        <Anchor component={Link} to="/contacts" underline="never" className={classes.link} aria-label="Rex CRM home">
            <span className={classes.logo} data-testid="rex-logo" dangerouslySetInnerHTML={{ __html: markup }} />
        </Anchor>
    );
}
