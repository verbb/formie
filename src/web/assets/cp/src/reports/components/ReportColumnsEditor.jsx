import { useCallback, useRef, useState } from 'react';

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
}) {
    const {
        ref, handleRef, isDragSource,
    } = useSortable({
        id: columnKey(column),
        index,
        transition: null,
        disabled,
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

    return (
        <div
            ref={ref}
            className={cn(
                'flex items-center gap-3 border-b border-gray-200 py-3',
                isDragSource && 'opacity-40',
            )}
        >
            <DragHandle
                handleRef={handleRef}
                disabled={disabled}
                ariaLabel={Craft.t('formie', 'Drag to reorder {name}', { name: column.label || column.handle })}
            />
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

export function ReportColumnsEditor({
    columns,
    disabled = false,
    onChange,
    scrollable = false,
}) {
    const listRef = useRef(null);
    const [activeColumn, setActiveColumn] = useState(null);

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

        const nextColumns = [...columns];
        const [moved] = nextColumns.splice(fromIndex, 1);
        nextColumns.splice(toIndex, 0, moved);
        updateColumns(nextColumns);
    }, [columns, updateColumns]);

    return (
        <DragDropProvider
            onDragStart={(event) => {
                const key = event.operation.source?.id;

                setActiveColumn(columns.find((column) => columnKey(column) === key) || null);
            }}
            onDragEnd={handleDragEnd}
        >
            <div
                ref={listRef}
                className={cn(
                    'w-full',
                    scrollable && 'max-h-[min(60vh,520px)] overflow-y-auto overscroll-contain px-6 py-1',
                )}
            >
                {columns.map((column, index) => (
                    <ReportColumnRow
                        key={columnKey(column)}
                        column={column}
                        index={index}
                        disabled={disabled}
                        listRef={listRef}
                        onEnabledChange={handleEnabledChange}
                        onLabelChange={handleLabelChange}
                    />
                ))}
            </div>

            <DragOverlay>
                {activeColumn ? <ReportColumnDragGhost column={activeColumn} /> : null}
            </DragOverlay>
        </DragDropProvider>
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
