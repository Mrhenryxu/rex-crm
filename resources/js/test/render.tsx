import { render, type RenderResult } from '@testing-library/react';
import type { ReactElement } from 'react';
import { MemoryRouter, Route, Routes } from 'react-router';
import { App } from '@/app/App';
import { Providers } from '@/app/Providers';

/** Render the full app at a URL, as the browser would. */
export function renderApp(url = '/contacts'): RenderResult {
    return render(
        <Providers>
            <MemoryRouter initialEntries={[url]}>
                <App />
            </MemoryRouter>
        </Providers>,
    );
}

/** Render one element inside the providers and a router. */
export function renderWithProviders(ui: ReactElement, { url = '/', path = '*' }: { url?: string; path?: string } = {}): RenderResult {
    return render(
        <Providers>
            <MemoryRouter initialEntries={[url]}>
                <Routes>
                    <Route path={path} element={ui} />
                </Routes>
            </MemoryRouter>
        </Providers>,
    );
}
