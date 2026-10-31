import { Navigate, Route, Routes } from 'react-router';
import { ContactCreatePage } from '@/features/contacts/pages/ContactCreatePage';
import { ContactDetailPage } from '@/features/contacts/pages/ContactDetailPage';
import { ContactEditPage } from '@/features/contacts/pages/ContactEditPage';
import { ContactsListPage } from '@/features/contacts/pages/ContactsListPage';
import { AppLayout } from './AppLayout';
import { NotFoundPage } from './NotFoundPage';

export function App() {
    return (
        <Routes>
            <Route element={<AppLayout />}>
                <Route index element={<Navigate to="/contacts" replace />} />
                <Route path="contacts" element={<ContactsListPage />} />
                <Route path="contacts/new" element={<ContactCreatePage />} />
                <Route path="contacts/:id" element={<ContactDetailPage />} />
                <Route path="contacts/:id/edit" element={<ContactEditPage />} />
                <Route path="*" element={<NotFoundPage />} />
            </Route>
        </Routes>
    );
}
