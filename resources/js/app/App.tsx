import { Title } from '@mantine/core';
import { Route, Routes } from 'react-router';

export function App() {
    return (
        <Routes>
            <Route path="*" element={<Title order={1}>Rex CRM</Title>} />
        </Routes>
    );
}
