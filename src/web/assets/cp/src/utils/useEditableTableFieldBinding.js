import {
    useCallback, useEffect, useRef, useSyncExternalStore,
} from 'react';
import { useEngineField } from '@verbb/plugin-kit-react/forms';

export const useEditableTableFieldBinding = (form, fieldName) => {
    const { value, setValue } = useEngineField(form, fieldName);
    const rows = Array.isArray(value) ? value : [];

    const fieldPrefix = `${fieldName}.`;
    const cellErrorsCacheRef = useRef({ snapshot: {}, deps: '' });
    const errorsCacheRef = useRef({ snapshot: [], deps: '' });

    const cellErrors = useSyncExternalStore(
        form?.store?.subscribe?.bind(form.store) ?? (() => { return () => {}; }),
        () => {
            const errorMap = form?.getErrorMapFields?.() || {};
            const entries = Object.entries(errorMap).filter(([key]) => { return key.startsWith(fieldPrefix); });
            const deps = entries.map(([k, v]) => { return `${k}:${JSON.stringify(v)}`; }).join('|');

            if (cellErrorsCacheRef.current.deps === deps) {
                return cellErrorsCacheRef.current.snapshot;
            }

            const snapshot = Object.fromEntries(entries);
            cellErrorsCacheRef.current = { snapshot, deps };
            return snapshot;
        },
        () => { return {}; },
    );

    const rawErrors = useSyncExternalStore(
        form?.store?.subscribe?.bind(form.store) ?? (() => { return () => {}; }),
        () => {
            const next =
                form?.getGroupedErrorsForPath?.(fieldName)
                    ?? form?.getErrorMapFields?.()[fieldName]
                    ?? [];
            const arr = Array.isArray(next) ? next : [];
            const deps = JSON.stringify(arr);

            if (errorsCacheRef.current.deps === deps) {
                return errorsCacheRef.current.snapshot;
            }

            errorsCacheRef.current = { snapshot: arr, deps };
            return errorsCacheRef.current.snapshot;
        },
        () => { return []; },
    );
    const errors = Array.isArray(rawErrors) ? rawErrors : [];

    const pendingCellUpdatesRef = useRef(new Map());
    const rafIdRef = useRef(null);

    const flushPendingCellUpdates = useCallback(() => {
        rafIdRef.current = null;

        if (pendingCellUpdatesRef.current.size === 0) {
            return;
        }

        const updates = Array.from(pendingCellUpdatesRef.current.entries());
        pendingCellUpdatesRef.current.clear();
        const errorMap = form?.getErrorMapFields?.() || {};

        updates.forEach(([path, nextValue]) => {
            form.setFieldValue(path, nextValue);

            if (errorMap[path]) {
                form.recomputeGroupedErrorsForPath?.(path);
            }
        });
    }, [form]);

    useEffect(() => {
        return () => {
            if (rafIdRef.current !== null && typeof cancelAnimationFrame === 'function') {
                cancelAnimationFrame(rafIdRef.current);
            }

            flushPendingCellUpdates();
        };
    }, [flushPendingCellUpdates]);

    const handleCellChange = useCallback((rowIndex, columnName, nextValue) => {
        const path = `${fieldName}.${rowIndex}.${columnName}`;
        pendingCellUpdatesRef.current.set(path, nextValue);

        if (rafIdRef.current !== null) {
            return;
        }

        if (typeof requestAnimationFrame === 'function') {
            rafIdRef.current = requestAnimationFrame(flushPendingCellUpdates);
            return;
        }

        flushPendingCellUpdates();
    }, [fieldName, flushPendingCellUpdates]);

    return {
        rows,
        setRows: setValue,
        errors,
        cellErrors,
        handleCellChange,
    };
};
