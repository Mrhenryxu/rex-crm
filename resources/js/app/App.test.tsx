import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router';
import { App } from './App';
import { Providers } from './Providers';

test('renders the application', () => {
    render(
        <Providers>
            <MemoryRouter>
                <App />
            </MemoryRouter>
        </Providers>,
    );

    expect(screen.getByRole('heading', { name: 'Rex CRM' })).toBeInTheDocument();
});
