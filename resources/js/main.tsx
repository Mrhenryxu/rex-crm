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
