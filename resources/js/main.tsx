// Rex typefaces, self-hosted (both SIL OFL 1.1). Only the weights the theme uses, Latin subset.
import '@fontsource/inter/latin-400.css';
import '@fontsource/inter/latin-500.css';
import '@fontsource/inter/latin-600.css';
import '@fontsource/inter/latin-700.css';
import '@fontsource/sofia-sans-extra-condensed/latin-700.css';
import '@mantine/core/styles.css';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router';
import { App } from './app/App';
import { Providers } from './app/Providers';

const container = document.getElementById('app');

if (container === null) {
    throw new Error('Missing #app mount element.');
}

createRoot(container).render(
    <StrictMode>
        <Providers>
            <BrowserRouter>
                <App />
            </BrowserRouter>
        </Providers>
    </StrictMode>,
);
