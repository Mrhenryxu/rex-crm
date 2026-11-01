import { useState } from 'react';
import { useLocation } from 'react-router';

export interface NoticeState {
    notice?: string;
}

/** A one-off message passed through navigation state, e.g. "Contact deleted." */
export function useNotice(): [string | null, () => void] {
    const location = useLocation();
    const initial = (location.state as NoticeState | null)?.notice ?? null;
    const [notice, setNotice] = useState<string | null>(initial);
    const [key, setKey] = useState(location.key);

    if (key !== location.key) {
        setKey(location.key);
        setNotice(initial);
    }

    return [notice, () => setNotice(null)];
}
