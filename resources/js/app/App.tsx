import { Navigate, Route, Routes } from 'react-router';
import { ContactsListPage } from '@/features/contacts/pages/ContactsListPage';
import { AppLayout } from './AppLayout';
import { NotFoundPage } from './NotFoundPage';

export function App() {
    return (
        <Routes>
            <Route element={<AppLayout />}>
                <Route index element={<Navigate to="/contacts" replace />} />
                <Route path="contacts" element={<ContactsListPage />} />
                <Route path="*" element={<NotFoundPage />} />
            </Route>
        </Routes>
    );
}
