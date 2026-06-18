import { useEffect, useRef, useState } from 'react';

const serializeFormIds = (formIds) => {
    if (formIds === '*' || formIds === ['*']) {
        return '*';
    }

    if (formIds === null || formIds === undefined || (Array.isArray(formIds) && formIds.length === 0)) {
        return '';
    }

    return [...(Array.isArray(formIds) ? formIds : [formIds])]
        .map(String)
        .sort()
        .join(',');
};

const emptyCatalog = {
    fieldColumns: [],
    fieldColumnGroups: [],
};

export const useReportFieldColumns = ({
    formIds,
    fieldColumnsUrl,
    csrfTokenName,
    csrfTokenValue,
    enabled = true,
}) => {
    const cacheRef = useRef(new Map());
    const [fieldColumns, setFieldColumns] = useState([]);
    const [fieldColumnGroups, setFieldColumnGroups] = useState([]);
    const [isLoading, setIsLoading] = useState(false);

    useEffect(() => {
        if (!enabled) {
            return undefined;
        }

        const formIdsKey = serializeFormIds(formIds);
        const cachedCatalog = cacheRef.current.get(formIdsKey);

        if (cachedCatalog) {
            setFieldColumns(cachedCatalog.fieldColumns);
            setFieldColumnGroups(cachedCatalog.fieldColumnGroups);
            setIsLoading(false);

            return undefined;
        }

        if (!fieldColumnsUrl || formIdsKey === '') {
            setFieldColumns([]);
            setFieldColumnGroups([]);
            setIsLoading(false);

            return undefined;
        }

        let cancelled = false;

        const fetchFieldColumns = async () => {
            setIsLoading(true);

            try {
                const body = new FormData();
                body.append(csrfTokenName, csrfTokenValue);
                body.append('formIds', JSON.stringify(formIds ?? []));

                const response = await fetch(fieldColumnsUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                    },
                    body,
                });

                const payload = await response.json();

                if (cancelled) {
                    return;
                }

                const nextCatalog = {
                    fieldColumns: Array.isArray(payload.fieldColumns) ? payload.fieldColumns : [],
                    fieldColumnGroups: Array.isArray(payload.fieldColumnGroups) ? payload.fieldColumnGroups : [],
                };
                cacheRef.current.set(formIdsKey, nextCatalog);
                setFieldColumns(nextCatalog.fieldColumns);
                setFieldColumnGroups(nextCatalog.fieldColumnGroups);
            } catch {
                if (!cancelled) {
                    setFieldColumns([]);
                    setFieldColumnGroups([]);
                }
            } finally {
                if (!cancelled) {
                    setIsLoading(false);
                }
            }
        };

        fetchFieldColumns();

        return () => {
            cancelled = true;
        };
    }, [csrfTokenName, csrfTokenValue, enabled, fieldColumnsUrl, formIds]);

    return {
        fieldColumns,
        fieldColumnGroups,
        isLoading,
    };
};
