import { useCallback, useMemo, useRef, useState } from 'react';

import {
    DragDropProvider,
    DragOverlay,
    KeyboardSensor,
    PointerSensor,
} from '@dnd-kit/react';
import { RestrictToVerticalAxis } from '@dnd-kit/abstract/modifiers';
import { RestrictToElement } from '@dnd-kit/dom/modifiers';
import { isSortable, useSortable } from '@dnd-kit/react/sortable';

import {
    Input,
    Lightswitch,
} from '@verbb/plugin-kit-react/components';
import { cn } from '@verbb/plugin-kit-react/utils';
import { DragHandle } from '@field-palette/components/DragHandle';

const AVAILABLE_COLUMN_LIMIT = 150;

export const columnKey = (column) => `${column.type || 'attribute'}:${column.handle}`;

function ReportColumnDragGhost({ column }) {
    if (!column) {
        return null;
    }

    return (
        <div className="flex items-center gap-3 rounded border border-gray-200 bg-white px-3 py-2 shadow-lg">
            <DragHandle
                handleRef={() => {}}
                disabled
                ariaLabel={Craft.t('formie', 'Dragging column')}
            />
            <Lightswitch checked={Boolean(column.enabled)} disabled />
            <div className="flex-1">
                <div className="font-medium">{column.label || column.handle}</div>
                <div className="light code text-xs">{column.handle}</div>
            </div>
        </div>
    );
}

function ReportColumnRow({
    column,
    index,
    disabled,
    listRef,
    onEnabledChange,
    onLabelChange,
    sortable = true,
}) {
    const sortableConfig = useSortable({
        id: columnKey(column),
        index,
        transition: null,
        disabled: disabled || !sortable,
        sensors: [
            PointerSensor.configure({ activationConstraint: { delay: 0, tolerance: 5 } }),
            KeyboardSensor,
        ],
        modifiers: [
            RestrictToVerticalAxis,
            RestrictToElement.configure({
                element: () => listRef.current,
            }),
        ],
    });

    const {
        ref, handleRef, isDragSource,
    } = sortable ? sortableConfig : {
        ref: null,
        handleRef: () => {},
        isDragSource: false,
    };

    return (
        <div
            ref={sortable ? ref : undefined}
            className={cn(
                'flex items-center gap-3 border-b border-gray-200 py-3',
                isDragSource && 'opacity-40',
            )}
        >
            {sortable ? (
                <DragHandle
                    handleRef={handleRef}
                    disabled={disabled}
                    ariaLabel={Craft.t('formie', 'Drag to reorder {name}', { name: column.label || column.handle })}
                />
            ) : (
                <div className="w-6 shrink-0" aria-hidden="true" />
            )}
            <Lightswitch
                checked={Boolean(column.enabled)}
                disabled={disabled}
                onCheckedChange={(enabled) => { onEnabledChange(columnKey(column), enabled); }}
            />
            <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2">
                    <div className="font-medium truncate">{column.label || column.handle}</div>
                    <span className="light code text-xs shrink-0">
                        {column.type === 'field'
                            ? Craft.t('formie', 'Field')
                            : Craft.t('formie', 'Attribute')}
                    </span>
                </div>
                <div className="light code text-xs">{column.handle}</div>
            </div>
            <Input
                className="w-48 shrink-0"
                placeholder={Craft.t('formie', 'Custom Label')}
                value={column.label || ''}
                disabled={disabled}
                onChange={(event) => { onLabelChange(columnKey(column), event.target.value); }}
            />
        </div>
    );
}

const columnMatchesSearch = (column, query) => {
    if (!query) {
        return true;
    }

    const label = String(column.label || column.handle || '').toLowerCase();
    const handle = String(column.handle || '').toLowerCase();

    return label.includes(query) || handle.includes(query);
};

export function ReportColumnsEditor({
    columns,
    disabled = false,
    onChange,
    scrollable = false,
}) {
    const listRef = useRef(null);
    const [activeColumn, setActiveColumn] = useState(null);
    const [searchQuery, setSearchQuery] = useState('');

    const { enabledColumns, availableColumns } = useMemo(() => {
        const enabled = [];
        const available = [];

        (columns || []).forEach((column) => {
            if (column.enabled) {
                enabled.push(column);
            } else {
                available.push(column);
            }
        });

        return {
            enabledColumns: enabled,
            availableColumns: available,
        };
    }, [columns]);

    const normalizedSearch = searchQuery.trim().toLowerCase();

    const filteredAvailableColumns = useMemo(() => {
        const matches = availableColumns.filter((column) => columnMatchesSearch(column, normalizedSearch));

        return matches.slice(0, AVAILABLE_COLUMN_LIMIT);
    }, [availableColumns, normalizedSearch]);

    const matchingAvailableCount = useMemo(() => {
        if (!normalizedSearch) {
            return availableColumns.length;
        }

        return availableColumns.filter((column) => columnMatchesSearch(column, normalizedSearch)).length;
    }, [availableColumns, normalizedSearch]);

    const hiddenAvailableCount = Math.max(0, matchingAvailableCount - filteredAvailableColumns.length);

    const updateColumns = useCallback((nextColumns) => {
        onChange(nextColumns);
    }, [onChange]);

    const handleEnabledChange = useCallback((key, enabled) => {
        updateColumns(columns.map((column) => {
            if (columnKey(column) !== key) {
                return column;
            }

            return { ...column, enabled };
        }));
    }, [columns, updateColumns]);

    const handleLabelChange = useCallback((key, label) => {
        updateColumns(columns.map((column) => {
            if (columnKey(column) !== key) {
                return column;
            }

            return { ...column, label };
        }));
    }, [columns, updateColumns]);

    const handleDragEnd = useCallback((event) => {
        setActiveColumn(null);

        if (event.canceled) {
            return;
        }

        const { source, target } = event.operation;

        if (!isSortable(source) || !isSortable(target)) {
            return;
        }

        const fromIndex = source.sortable.index;
        const toIndex = target.sortable.index;

        if (fromIndex === toIndex) {
            return;
        }

        const nextEnabledColumns = [...enabledColumns];
        const [moved] = nextEnabledColumns.splice(fromIndex, 1);
        nextEnabledColumns.splice(toIndex, 0, moved);

        const enabledKeys = new Set(nextEnabledColumns.map((column) => columnKey(column)));
        const remainingColumns = columns.filter((column) => !enabledKeys.has(columnKey(column)));

        updateColumns([...nextEnabledColumns, ...remainingColumns]);
    }, [columns, enabledColumns, updateColumns]);

    return (
        <div className="flex flex-col gap-6">
            <div>
                <div className="mb-2 text-sm font-medium text-gray-700">
                    {Craft.t('formie', 'Enabled Columns')}
                </div>

                <DragDropProvider
                    onDragStart={(event) => {
                        const key = event.operation.source?.id;

                        setActiveColumn(enabledColumns.find((column) => columnKey(column) === key) || null);
                    }}
                    onDragEnd={handleDragEnd}
                >
                    <div
                        ref={listRef}
                        className={cn(
                            'w-full',
                            scrollable && 'max-h-[min(40vh,360px)] overflow-y-auto overscroll-contain px-6 py-1',
                        )}
                    >
                        {enabledColumns.length ? enabledColumns.map((column, index) => (
                            <ReportColumnRow
                                key={columnKey(column)}
                                column={column}
                                index={index}
                                disabled={disabled}
                                listRef={listRef}
                                onEnabledChange={handleEnabledChange}
                                onLabelChange={handleLabelChange}
                            />
                        )) : (
                            <p className="m-0 px-1 py-2 text-sm text-gray-500">
                                {Craft.t('formie', 'No columns are enabled yet. Search below to add fields.')}
                            </p>
                        )}
                    </div>

                    <DragOverlay>
                        {activeColumn ? <ReportColumnDragGhost column={activeColumn} /> : null}
                    </DragOverlay>
                </DragDropProvider>
            </div>

            {availableColumns.length ? (
                <div>
                    <div className="mb-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div className="text-sm font-medium text-gray-700">
                            {Craft.t('formie', 'Available Columns')}
                        </div>
                        <Input
                            className="w-full sm:max-w-xs"
                            placeholder={Craft.t('formie', 'Search columns…')}
                            value={searchQuery}
                            onChange={(event) => { setSearchQuery(event.target.value); }}
                        />
                    </div>

                    <p className="m-0 mb-2 text-sm text-gray-500">
                        {Craft.t(
                            'formie',
                            'Showing {shown, number} of {total, number} available columns.',
                            {
                                shown: filteredAvailableColumns.length,
                                total: matchingAvailableCount,
                            },
                        )}
                        {hiddenAvailableCount > 0
                            ? ` ${Craft.t('formie', 'Refine your search to see more.')}`
                            : ''}
                    </p>

                    <div
                        className={cn(
                            'w-full',
                            scrollable && 'max-h-[min(40vh,360px)] overflow-y-auto overscroll-contain px-6 py-1',
                        )}
                    >
                        {filteredAvailableColumns.map((column) => (
                            <ReportColumnRow
                                key={columnKey(column)}
                                column={column}
                                index={0}
                                disabled={disabled}
                                listRef={listRef}
                                onEnabledChange={handleEnabledChange}
                                onLabelChange={handleLabelChange}
                                sortable={false}
                            />
                        ))}
                    </div>
                </div>
            ) : null}
        </div>
    );
}

export const resolveFieldColumnsForForms = (formIds, fieldColumnsByForm = {}) => {
    if (!fieldColumnsByForm || typeof fieldColumnsByForm !== 'object') {
        return [];
    }

    const mergeColumns = (sourceColumns) => {
        const merged = new Map();

        sourceColumns.forEach((column) => {
            if (!merged.has(column.handle)) {
                merged.set(column.handle, column);
            }
        });

        return [...merged.values()];
    };

    if (formIds === '*' || formIds === ['*']) {
        return mergeColumns(Object.values(fieldColumnsByForm).flat());
    }

    if (formIds === null || formIds === undefined || (Array.isArray(formIds) && formIds.length === 0)) {
        return [];
    }

    const ids = Array.isArray(formIds) ? formIds.map(String) : [String(formIds)];

    return mergeColumns(ids.flatMap((id) => fieldColumnsByForm[id] || []));
};

export const compactColumnsForStorage = (columns) => {
    const normalized = (columns || []).filter((column) => column?.handle).map((column) => ({
        type: column.type || 'attribute',
        handle: column.handle,
        label: column.label ?? null,
        enabled: Boolean(column.enabled),
    }));

    if (!normalized.length) {
        return [];
    }

    const compact = normalized.filter((column) => {
        if ((column.type || 'attribute') === 'attribute') {
            return true;
        }

        return column.enabled;
    });

    return compact.length ? compact : normalized.filter((column) => (column.type || 'attribute') === 'attribute');
};

export const mergeReportColumns = (savedColumns, attributeColumns, fieldColumns) => {
    const map = new Map();

    const addColumn = (column) => {
        if (!column?.handle) {
            return;
        }

        const type = column.type || 'attribute';
        const key = `${type}:${column.handle}`;

        if (map.has(key)) {
            const existing = map.get(key);
            existing.label = column.label ?? existing.label;

            if (Object.prototype.hasOwnProperty.call(column, 'enabled')) {
                existing.enabled = Boolean(column.enabled);
            }

            return;
        }

        map.set(key, {
            type,
            handle: column.handle,
            label: column.label ?? null,
            enabled: Boolean(column.enabled),
        });
    };

    (attributeColumns || []).forEach(addColumn);
    (fieldColumns || []).forEach(addColumn);
    (savedColumns || []).forEach(addColumn);

    if (savedColumns?.length) {
        const savedOrder = savedColumns.map((column) => `${column.type || 'attribute'}:${column.handle}`);
        const remaining = [...map.keys()].filter((key) => !savedOrder.includes(key));

        return [...savedOrder, ...remaining]
            .filter((key) => map.has(key))
            .map((key) => map.get(key));
    }

    return [...map.values()];
};
