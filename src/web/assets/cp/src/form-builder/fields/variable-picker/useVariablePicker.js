import { useCallback, useEffect, useMemo, useState } from 'react';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';
import {
    getVariableCategoryEntries,
    matchesVariableQuery,
    toTopLevelGroups,
} from './variablePickerUtils.js';

/**
 * Formie-owned picker state: category navigation, search, apply/reset.
 */
export function useVariablePicker({
    variableCategories,
    variableCategoryLabels,
    variableCategoryOrder,
    onApply,
    isOpen,
    initialPage,
    getDefaultIfEmpty,
    deferUntilOpen = false,
}) {
    const t = useTranslation();
    const [pages, setPages] = useState([]);
    const [search, setSearch] = useState('');

    const page = pages.at(-1);
    const shouldResolveGroups = !deferUntilOpen || Boolean(isOpen) || Boolean(page) || Boolean(initialPage);

    const flatCategories = useMemo(() => {
        if (!shouldResolveGroups) {
            return [];
        }

        return getVariableCategoryEntries(variableCategories, variableCategoryLabels, variableCategoryOrder);
    }, [shouldResolveGroups, variableCategories, variableCategoryLabels, variableCategoryOrder]);

    const groupedTopLevel = useMemo(() => {
        if (!shouldResolveGroups) {
            return [];
        }

        return toTopLevelGroups(flatCategories, t);
    }, [flatCategories, shouldResolveGroups, t]);

    const normalizedSearch = search.trim().toLowerCase();

    const groups = useMemo(() => {
        if (!shouldResolveGroups && !page) {
            return [];
        }

        if (page?.children?.length) {
            const { children } = page;
            const groupValue = page.value ?? page.label ?? 'selectors';
            if (!normalizedSearch) {
                return [{ label: t('Selectors'), value: groupValue, items: children }];
            }
            const filtered = children.filter((item) => {
                return matchesVariableQuery(item, normalizedSearch);
            });
            return filtered.length ? [{ label: t('Selectors'), value: groupValue, items: filtered }] : [];
        }

        if (!normalizedSearch) {
            return groupedTopLevel;
        }

        return groupedTopLevel
            .map((group) => {
                return {
                    ...group,
                    items: group.items.filter((item) => {
                        if (matchesVariableQuery(item, normalizedSearch)) {
                            return true;
                        }

                        const children = Array.isArray(item.children) ? item.children : [];
                        return children.some((child) => {
                            return matchesVariableQuery(child, normalizedSearch);
                        });
                    }),
                };
            })
            .filter((group) => {
                return group.items.length > 0;
            });
    }, [page, normalizedSearch, groupedTopLevel, shouldResolveGroups, t]);

    const options = useMemo(() => {
        return (groups ?? []).flatMap((group) => {
            return group.items;
        });
    }, [groups]);

    const handleSelect = useCallback((variable, baseVariableOpt) => {
        if (Array.isArray(variable.children) && variable.children.length > 0) {
            setPages((current) => {
                return [...current, variable];
            });
            setSearch('');
            return;
        }

        const baseVariable = baseVariableOpt ?? page ?? variable;
        onApply(baseVariable, variable, getDefaultIfEmpty?.());
    }, [getDefaultIfEmpty, onApply, page]);

    const handleBack = useCallback(() => {
        setPages((current) => {
            return current.slice(0, -1);
        });
    }, []);

    const reset = useCallback(() => {
        setPages([]);
        setSearch('');
    }, []);

    useEffect(() => {
        if (isOpen) {
            if (initialPage && Array.isArray(initialPage.children) && initialPage.children.length > 0) {
                setPages([initialPage]);
                setSearch('');
            } else {
                reset();
            }
        }
    }, [initialPage, isOpen, reset]);

    return {
        groups,
        options,
        search,
        setSearch,
        page,
        handleSelect,
        handleBack,
        reset,
    };
}
