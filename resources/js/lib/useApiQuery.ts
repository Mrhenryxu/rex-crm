import { useCallback, useEffect, useState, type DependencyList } from 'react';
import { ApiError, toApiError } from './api-client';

export interface ApiQueryState<T> {
    data: T | undefined;
    error: ApiError | undefined;
    loading: boolean;
    reload: () => void;
}

/**
 * Load data when dependencies change, cancelling the previous request.
 * Previous data is kept while a new request loads, so tables don't flash empty.
 */
export function useApiQuery<T>(fetcher: (signal: AbortSignal) => Promise<T>, deps: DependencyList): ApiQueryState<T> {
    const [data, setData] = useState<T | undefined>(undefined);
    const [error, setError] = useState<ApiError | undefined>(undefined);
    const [loading, setLoading] = useState(true);
    const [reloadToken, setReloadToken] = useState(0);

    useEffect(() => {
        const controller = new AbortController();
        setLoading(true);
        setError(undefined);

        fetcher(controller.signal).then(
            (result) => {
                setData(result);
                setLoading(false);
            },
            (reason: unknown) => {
                if (controller.signal.aborted) {
                    return;
                }

                setError(toApiError(reason));
                setLoading(false);
            },
        );

        return () => controller.abort();
        // eslint-disable-next-line react-hooks/exhaustive-deps -- callers pass the fetcher's inputs as deps.
    }, [...deps, reloadToken]);

    const reload = useCallback(() => setReloadToken((token) => token + 1), []);

    return { data, error, loading, reload };
}
