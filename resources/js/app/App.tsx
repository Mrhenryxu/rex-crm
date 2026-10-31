import { Title } from '@mantine/core';
import { Navigate, Route, Routes } from 'react-router';
import { AppLayout } from './AppLayout';
import { NotFoundPage } from './NotFoundPage';

export function App() {
    return (
        <Routes>
            <Route element={<AppLayout />}>
                <Route index element={<Navigate to="/contacts" replace />} />
                <Route path="contacts" element={<Title order={1}>Contacts</Title>} />
                <Route path="*" element={<NotFoundPage />} />
            </Route>
        </Routes>
    );
}
