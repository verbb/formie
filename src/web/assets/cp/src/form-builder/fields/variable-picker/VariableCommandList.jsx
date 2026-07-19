/**
 * Formie searchable variable list (toolbar dropdown + field picker control).
 * Owns keyboard highlight / Enter selection; parents own data filtering when `shouldFilter` is false.
 */

import React, {
    useCallback, useEffect, useLayoutEffect, useMemo, useRef, useState,
} from 'react';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';
import { cn } from '@verbb/plugin-kit-react/utils';
import { Icon } from '@verbb/plugin-kit-react/components';

const itemKey = (item, fallback) => {
    return item.value ?? item.label ?? fallback;
};

const matchesQuery = (item, query, extra = []) => {
    if (!query) {
        return true;
    }

    const haystack = [item.label, String(item.value ?? ''), ...extra]
        .join(' ')
        .toLowerCase();

    return haystack.includes(query);
};

export function VariableCommandList({
    search,
    onSearchChange,
    groups,
    options,
    selectedValue,
    onSelect,
    placeholder,
    showSearch = true,
    shouldFilter = false,
    onBack,
    isChildMode = false,
    selectFirstItem = true,
    autoFocusSearchInput = true,
    /** When the parent popover stays mounted while closed, pass open so search can focus on open. */
    open = true,
    afterSearchContent,
}) {
    const t = useTranslation();
    const listRef = useRef(null);
    const rootRef = useRef(null);
    const searchInputRef = useRef(null);
    const savedScrollTopRef = useRef(null);
    const query = search.trim().toLowerCase();

    const flatEntries = useMemo(() => {
        const entries = [];

        if (!groups) {
            options.forEach((item, index) => {
                if (shouldFilter && !matchesQuery(item, query)) {
                    return;
                }

                entries.push({
                    key: itemKey(item, `opt-${index}`),
                    item,
                    hasChildren: false,
                });
            });

            return entries;
        }

        groups.forEach((group, gi) => {
            group.items.forEach((item, ii) => {
                const hasChildren = Array.isArray(item.children) && item.children.length > 0;
                // Searching nested items: surface matching children only (parent hidden while querying).
                const showParent = !hasChildren || !query;

                if (showParent && (!shouldFilter || matchesQuery(item, query, [group.label]))) {
                    entries.push({
                        key: itemKey(item, `${gi}-${ii}`),
                        item,
                        groupKey: group.value ?? group.label ?? String(gi),
                        groupLabel: group.label,
                        hasChildren,
                    });
                }

                if (hasChildren && query) {
                    item.children.forEach((child, ci) => {
                        if (!matchesQuery(child, query, [item.label, group.label])) {
                            return;
                        }

                        entries.push({
                            key: itemKey(child, `${gi}-${ii}-${ci}`),
                            item: child,
                            baseVariable: item,
                            groupKey: group.value ?? group.label ?? String(gi),
                            groupLabel: group.label,
                            hasChildren: false,
                        });
                    });
                }
            });
        });

        return entries;
    }, [groups, options, query, shouldFilter]);

    const [highlightedKey, setHighlightedKey] = useState('');

    useEffect(() => {
        if (selectedValue) {
            const match = flatEntries.find((entry) => {
                return (entry.item.value ?? entry.item.label) === selectedValue;
            });
            if (match) {
                setHighlightedKey(match.key);
                return;
            }
        }

        if (selectFirstItem && flatEntries[0]) {
            setHighlightedKey(flatEntries[0].key);
            return;
        }

        setHighlightedKey('');
    }, [flatEntries, selectFirstItem, selectedValue]);

    const handleSelectItem = useCallback((item, baseVariable) => {
        if (Array.isArray(item.children) && item.children.length > 0) {
            savedScrollTopRef.current = listRef.current?.scrollTop ?? 0;
        }
        onSelect(item, baseVariable);
    }, [onSelect]);

    const wasChildModeRef = useRef(isChildMode);
    useLayoutEffect(() => {
        const wasChild = wasChildModeRef.current;
        wasChildModeRef.current = isChildMode;
        if (wasChild && !isChildMode && savedScrollTopRef.current != null && listRef.current) {
            listRef.current.scrollTop = savedScrollTopRef.current;
            savedScrollTopRef.current = null;
        }
        if (!wasChild && isChildMode && showSearch && autoFocusSearchInput && open) {
            searchInputRef.current?.focus({ preventScroll: true });
        }
    }, [autoFocusSearchInput, isChildMode, open, showSearch]);

    // Focus search when the picker opens. The list often stays mounted under pk-popover
    // while closed, so mount-only autoFocus / effects never re-run — gate on `open`.
    useLayoutEffect(() => {
        if (!open || !showSearch || !autoFocusSearchInput) {
            return undefined;
        }

        const focusSearch = () => {
            searchInputRef.current?.focus({ preventScroll: true });
        };

        focusSearch();
        // pk-popover finishes placement after updateComplete; one frame is often not enough.
        const frame = requestAnimationFrame(() => {
            requestAnimationFrame(focusSearch);
        });
        const timer = window.setTimeout(focusSearch, 0);

        return () => {
            cancelAnimationFrame(frame);
            window.clearTimeout(timer);
        };
    }, [autoFocusSearchInput, open, showSearch]);

    useLayoutEffect(() => {
        if (!highlightedKey || !listRef.current) {
            return;
        }

        const node = listRef.current.querySelector(`[data-command-key="${CSS.escape(highlightedKey)}"]`);
        node?.scrollIntoView({ block: 'nearest' });
    }, [highlightedKey]);

    const moveHighlight = (delta) => {
        if (flatEntries.length === 0) {
            return;
        }

        const currentIndex = Math.max(0, flatEntries.findIndex((entry) => {
            return entry.key === highlightedKey;
        }));
        const nextIndex = (currentIndex + delta + flatEntries.length) % flatEntries.length;
        setHighlightedKey(flatEntries[nextIndex].key);
    };

    const handleKeyDown = (event) => {
        if (onBack && !search.trim() && event.key === 'Backspace') {
            event.preventDefault();
            event.stopPropagation();
            onBack();
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            moveHighlight(1);
            return;
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            moveHighlight(-1);
            return;
        }

        if (event.key === 'Enter') {
            const entry = flatEntries.find((item) => {
                return item.key === highlightedKey;
            });
            if (!entry) {
                return;
            }

            event.preventDefault();
            handleSelectItem(entry.item, entry.baseVariable);
        }
    };

    const renderItemButton = (entry) => {
        const selected = entry.key === highlightedKey;

        return (
            <button
                key={entry.key}
                type="button"
                data-slot="command-item"
                data-command-key={entry.key}
                data-selected={selected ? 'true' : 'false'}
                className={cn(
                    // Native <button> defaults to text-align:center — left-align like v1 CommandItem.
                    'relative flex w-full gap-2 items-center justify-start px-2 py-1.5 text-left',
                    'text-xs data-[selected=true]:bg-slate-100',
                    'cursor-default select-none outline-hidden',
                    '[&_svg]:pointer-events-none [&_svg]:not([class*="size-"]):size-3 [&_svg]:shrink-0',
                )}
                onMouseEnter={() => {
                    setHighlightedKey(entry.key);
                }}
                onClick={() => {
                    handleSelectItem(entry.item, entry.baseVariable);
                }}
            >
                <span className="truncate flex-1">{entry.item.label}</span>
                {entry.hasChildren && (
                    <Icon icon="chevron-right" className={cn('size-3 shrink-0 text-gray-400 ml-2')} aria-hidden />
                )}
            </button>
        );
    };

    const content = (
        <div
            ref={rootRef}
            data-slot="command"
            className="flex h-full w-full flex-col text-xs rounded shadow-md border-0 shadow-none rounded-none"
            onKeyDown={handleKeyDown}
        >
            {showSearch && (
                <div
                    data-slot="command-input-wrapper"
                    className="flex items-center border-b border-slate-150 px-2"
                >
                    <Icon icon="search" className="mr-2 size-3 shrink-0 opacity-50" />
                    <input
                        ref={searchInputRef}
                        data-slot="command-input"
                        value={search}
                        onChange={(event) => {
                            onSearchChange?.(event.target.value);
                        }}
                        placeholder={placeholder ?? t('Search variables')}
                        className={cn(
                            'flex w-full py-2 text-left',
                            'rounded-md text-xs border-0 bg-transparent',
                            'shadow-none outline-hidden disabled:cursor-not-allowed disabled:opacity-50',
                        )}
                    />
                </div>
            )}

            {afterSearchContent}

            {onBack && (
                // Native button (not pk-button): avoid default size padding / start-slot indent
                // so Back matches v1 and aligns with list/search rows (screen2).
                <button
                    type="button"
                    className={cn(
                        'w-full text-left text-[11px] flex items-center justify-start gap-1',
                        'hover:bg-slate-100 border-b border-slate-150 px-2 py-1.5',
                        'cursor-default select-none outline-hidden bg-transparent',
                    )}
                    onClick={(e) => {
                        e.preventDefault();
                        onBack();
                    }}
                >
                    <Icon icon="chevron-left" className="size-[8px] shrink-0" />
                    {t('Back')}
                </button>
            )}

            <div
                ref={listRef}
                data-slot="command-list"
                className="max-h-[280px] overflow-y-auto"
                role="listbox"
            >
                {flatEntries.length === 0 ? (
                    <div data-slot="command-empty" className="py-3 text-center text-xs">
                        {t('No variables found.')}
                    </div>
                ) : groups ? (
                    groups.map((group, gi) => {
                        const groupKey = group.value ?? group.label ?? String(gi);
                        const groupEntries = flatEntries.filter((entry) => {
                            return entry.groupKey === groupKey;
                        });

                        if (groupEntries.length === 0) {
                            return null;
                        }

                        const showSeparator = groups.slice(0, gi).some((prior, priorIndex) => {
                            const priorKey = prior.value ?? prior.label ?? String(priorIndex);
                            return flatEntries.some((entry) => {
                                return entry.groupKey === priorKey;
                            });
                        });

                        return (
                            <React.Fragment key={groupKey}>
                                {showSeparator && (
                                    <div
                                        data-slot="command-separator"
                                        className="pointer-events-none my-0.5 h-px bg-slate-200"
                                    />
                                )}
                                <div
                                    data-slot="command-group"
                                    className="text-xs text-gray-600 overflow-hidden py-1"
                                >
                                    <div className="px-2 py-0.5 text-[11px] font-medium text-gray-500">
                                        {group.label}
                                    </div>
                                    {groupEntries.map((entry) => {
                                        return renderItemButton(entry);
                                    })}
                                </div>
                            </React.Fragment>
                        );
                    })
                ) : (
                    <div data-slot="command-group" className="text-xs text-gray-600 overflow-hidden py-1">
                        <div className="px-2 py-0.5 text-[11px] font-medium text-gray-500">
                            {t('Selectors')}
                        </div>
                        {flatEntries.map((entry) => {
                            return renderItemButton(entry);
                        })}
                    </div>
                )}
            </div>
        </div>
    );

    return onBack ? (
        <div onKeyDown={handleKeyDown} className="contents">
            {content}
        </div>
    ) : (
        content
    );
}
