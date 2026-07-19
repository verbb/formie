import { memo, startTransition, useCallback, useMemo, useRef, useState } from 'react';
import { flushSync } from 'react-dom';

import {
    DragDropProvider,
    DragOverlay,
    PointerSensor,
} from '@dnd-kit/react';
import { PointerActivationConstraints } from '@dnd-kit/dom';
import { RestrictToVerticalAxis } from '@dnd-kit/abstract/modifiers';
import { isSortable, useSortable } from '@dnd-kit/react/sortable';

import { cn } from '@verbb/plugin-kit-react/utils';
import { Input, Lightswitch } from '@verbb/plugin-kit-react/components';
import { DragHandle } from '@field-palette/components/DragHandle';
import { ReportAvailableColumnsSidebar } from '@reports/components/ReportAvailableColumnsSidebar';
import {
    ReportColumnFormLabel,
    ReportColumnTypeBadge,
    getEnabledColumnRowClassName,
} from '@reports/components/ReportColumnMeta';
import { isAllFieldColumnsMode } from '@reports/utils/reportColumnModes';
import {
    buildFormGroupLookups,
    enrichColumnWithFormContext,
    withEnabledFormContext,
} from '@reports/utils/reportColumnFormContext';

const COLUMN_DRAG_SENSORS = [
    PointerSensor.configure({
        activationConstraints: [
            new PointerActivationConstraints.Distance({
                value: 4,
            }),
        ],
    }),
];

const COLUMN_DRAG_MODIFIERS = [RestrictToVerticalAxis];

export const columnKey = (column) => `${column.type || 'attribute'}:${column.handle}`;

function ReportColumnDragGhost({ column, useFieldHandles = false }) {
    if (!column) {
        return null;
    }

    const title = useFieldHandles ? column.handle : (column.label || column.handle);
    const subtitle = useFieldHandles
        ? (column.label && column.label !== column.handle ? column.label : null)
        : column.handle;

    return (
        <div className={getEnabledColumnRowClassName(column, { variant: 'ghost' })}>
            <div className="w-6 shrink-0" aria-hidden="true" />
            <ReportColumnTypeBadge column={column} />
            <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                    <div className={cn('truncate font-medium', useFieldHandles && 'code')}>{title}</div>
                    <ReportColumnFormLabel formTitle={column.formTitle} />
                </div>
                {subtitle ? (
                    <div className={cn('truncate text-xs text-gray-500', !useFieldHandles && 'code')}>{subtitle}</div>
                ) : null}
            </div>
        </div>
    );
}

const ReportColumnRowBody = memo(function ReportColumnRowBody({
    column,
    disabled,
    useFieldHandles = false,
    onEnabledChange,
    onLabelChange,
}) {
    const title = useFieldHandles ? column.handle : (column.label || column.handle);
    const subtitle = useFieldHandles
        ? (column.label && column.label !== column.handle ? column.label : null)
        : column.handle;

    return (
        <>
            <ReportColumnTypeBadge column={column} />
            <Lightswitch
                checked={Boolean(column.enabled)}
                disabled={disabled}
                onCheckedChange={(enabled) => { onEnabledChange(columnKey(column), enabled); }}
            />
            <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                    <div className={cn('truncate font-medium text-gray-900', useFieldHandles && 'code')}>{title}</div>
                    <ReportColumnFormLabel formTitle={column.formTitle} />
                </div>
                {subtitle ? (
                    <div className={cn('truncate text-xs text-gray-500', !useFieldHandles && 'code')}>{subtitle}</div>
                ) : null}
            </div>
            {!useFieldHandles ? (
                <Input
                    className="w-48 shrink-0"
                    placeholder={Craft.t('formie', 'Custom Label')}
                    value={column.label || ''}
                    disabled={disabled}
                    onChange={(event) => { onLabelChange(columnKey(column), event.target.value); }}
                />
            ) : null}
        </>
    );
});

function SortableReportColumnRow({
    column,
    index,
    disabled,
    useFieldHandles = false,
    isDragPlaceholder = false,
    onEnabledChange,
    onLabelChange,
    onHandlePointerDown,
}) {
    const {
        ref, handleRef,
    } = useSortable({
        id: columnKey(column),
        index,
        transition: null,
        disabled,
        modifiers: COLUMN_DRAG_MODIFIERS,
    });

    return (
        <div
            ref={ref}
            className={cn(
                getEnabledColumnRowClassName(column),
                isDragPlaceholder && '[&>*]:invisible',
            )}
        >
            <span onPointerDown={() => { onHandlePointerDown?.(); }}>
                <DragHandle
                    handleRef={handleRef}
                    disabled={disabled}
                    ariaLabel={Craft.t('formie', 'Drag to reorder {name}', { name: column.label || column.handle })}
                />
            </span>
            <ReportColumnRowBody
                column={column}
                disabled={disabled}
                useFieldHandles={useFieldHandles}
                onEnabledChange={onEnabledChange}
                onLabelChange={onLabelChange}
            />
        </div>
    );
}

function ReportEnabledColumnsList({
    columns,
    disabled = false,
    scrollable = false,
    showFieldPicker = false,
    usesAllFieldColumns = false,
    useFieldHandles = false,
    onEnabledChange,
    onLabelChange,
    onReorder,
    onSidebarSuspendedChange,
}) {
    const listRef = useRef(null);
    const [draggingKey, setDraggingKey] = useState(null);
    const isDraggingRef = useRef(false);

    const activeColumn = useMemo(() => {
        if (!draggingKey) {
            return null;
        }

        return columns.find((column) => columnKey(column) === draggingKey) || null;
    }, [columns, draggingKey]);

    const finishDragSession = useCallback(() => {
        isDraggingRef.current = false;
        setDraggingKey(null);
        onSidebarSuspendedChange?.(false);
    }, [onSidebarSuspendedChange]);

    // Suspend the field-picker sidebar synchronously on handle press so dnd-kit activation
    // does not run against thousands of sibling DOM nodes (see sidebarSuspended below).
    const handleHandlePointerDown = useCallback(() => {
        onSidebarSuspendedChange?.(true);

        const handlePointerUp = () => {
            window.requestAnimationFrame(() => {
                if (!isDraggingRef.current) {
                    onSidebarSuspendedChange?.(false);
                }
            });
            window.removeEventListener('pointerup', handlePointerUp);
        };

        window.addEventListener('pointerup', handlePointerUp);
    }, [onSidebarSuspendedChange]);

    const handleDragEnd = useCallback((event) => {
        finishDragSession();

        if (event.canceled) {
            return;
        }

        const { source } = event.operation;

        if (!isSortable(source)) {
            return;
        }

        const { initialIndex, index } = source;

        if (
            initialIndex === index
            || initialIndex < 0
            || index < 0
            || initialIndex >= columns.length
            || index >= columns.length
        ) {
            return;
        }

        const nextColumns = [...columns];
        const [moved] = nextColumns.splice(initialIndex, 1);
        nextColumns.splice(index, 0, moved);
        startTransition(() => {
            onReorder(nextColumns);
        });
    }, [columns, finishDragSession, onReorder]);

    return (
        <>
            <div className="mb-2">
                <div className="text-sm font-medium text-gray-700">
                    {usesAllFieldColumns
                        ? Craft.t('formie', 'Submission Attributes')
                        : Craft.t('formie', 'Enabled Columns')}
                </div>
                {useFieldHandles ? (
                    <p className="m-0 mt-1 text-xs text-gray-500">
                        {Craft.t('formie', 'Viewer and export headers use field handles. Custom labels are not shown while that display setting is enabled.')}
                    </p>
                ) : null}
            </div>

            <DragDropProvider
                sensors={COLUMN_DRAG_SENSORS}
                onDragStart={(event) => {
                    const key = event.operation.source?.id;

                    if (!key) {
                        return;
                    }

                    setDraggingKey(String(key));
                    isDraggingRef.current = true;
                }}
                onDragEnd={handleDragEnd}
                onDragCancel={finishDragSession}
            >
                <div
                    ref={listRef}
                    className={cn(
                        'w-full',
                        showFieldPicker && 'h-[min(65vh,600px)] overflow-y-auto overscroll-contain',
                        scrollable && !showFieldPicker && 'max-h-[min(40vh,360px)] overflow-y-auto overscroll-contain py-1',
                    )}
                >
                    {columns.length ? columns.map((column, index) => (
                        <SortableReportColumnRow
                            key={columnKey(column)}
                            column={column}
                            index={index}
                            disabled={disabled}
                            useFieldHandles={useFieldHandles}
                            isDragPlaceholder={draggingKey === columnKey(column)}
                            onEnabledChange={onEnabledChange}
                            onLabelChange={onLabelChange}
                            onHandlePointerDown={handleHandlePointerDown}
                        />
                    )) : (
                        <p className="m-0 px-1 py-2 text-sm text-gray-500">
                            {usesAllFieldColumns
                                ? Craft.t('formie', 'Enable the submission attributes you want in this report.')
                                : Craft.t('formie', 'No columns are enabled yet. Use the sidebar to add fields.')}
                        </p>
                    )}
                </div>

                <DragOverlay dropAnimation={null}>
                    {activeColumn ? (
                        <ReportColumnDragGhost column={activeColumn} useFieldHandles={useFieldHandles} />
                    ) : null}
                </DragOverlay>
            </DragDropProvider>
        </>
    );
}

export function ReportColumnsEditor({
    columns,
    disabled = false,
    onChange,
    scrollable = false,
    fieldColumnsMode = 'all',
    fieldColumnGroups = [],
    useFieldHandles = false,
}) {
    const usesAllFieldColumns = isAllFieldColumnsMode(fieldColumnsMode);
    const showFieldPicker = !usesAllFieldColumns;

    const formGroupLookups = useMemo(() => {
        return buildFormGroupLookups(fieldColumnGroups);
    }, [fieldColumnGroups]);

    const enabledColumns = useMemo(() => {
        return (columns || [])
            .filter((column) => column.enabled)
            .map((column) => enrichColumnWithFormContext(column, formGroupLookups));
    }, [columns, formGroupLookups]);

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

    const handleSidebarToggle = useCallback((column, enabled) => {
        const key = columnKey(column);
        const existing = columns.find((item) => columnKey(item) === key);

        if (existing) {
            updateColumns(columns.map((item) => {
                if (columnKey(item) !== key) {
                    return item;
                }

                if (!enabled) {
                    return { ...item, enabled: false };
                }

                return withEnabledFormContext(item, column);
            }));

            return;
        }

        if (!enabled) {
            return;
        }

        updateColumns([
            ...columns,
            withEnabledFormContext(column, column),
        ]);
    }, [columns, updateColumns]);

    const handleSidebarToggleForm = useCallback((group, enabled) => {
        const groupColumns = group?.columns || [];
        const groupKeySet = new Set(groupColumns.map((column) => columnKey(column)));

        if (enabled) {
            const existingByKey = new Map(columns.map((column) => [columnKey(column), column]));
            const nextColumns = [...columns];

            groupColumns.forEach((column) => {
                const key = columnKey(column);
                const existing = existingByKey.get(key);

                if (existing) {
                    const index = nextColumns.findIndex((item) => columnKey(item) === key);

                    if (index !== -1) {
                        nextColumns[index] = enabled
                            ? withEnabledFormContext(nextColumns[index], { formId: group.formId })
                            : { ...nextColumns[index], enabled: false };
                    }
                } else {
                    nextColumns.push(withEnabledFormContext(column, { formId: group.formId }));
                }
            });

            updateColumns(nextColumns);

            return;
        }

        updateColumns(columns.map((column) => {
            if (!groupKeySet.has(columnKey(column))) {
                return column;
            }

            return { ...column, enabled: false };
        }));
    }, [columns, updateColumns]);

    const handleEnabledColumnsReorder = useCallback((nextEnabledColumns) => {
        const enabledKeys = new Set(nextEnabledColumns.map((column) => columnKey(column)));
        const remainingColumns = columns.filter((column) => !enabledKeys.has(columnKey(column)));

        updateColumns([...nextEnabledColumns, ...remainingColumns]);
    }, [columns, updateColumns]);

    // Baseline fix for manual column reorder lag on large sites: unmount the heavy field-picker
    // sidebar before dnd-kit activates (flushSync on pointerdown), show a same-height placeholder
    // while dragging, and restore when the drag ends. Form groups also start collapsed in the
    // sidebar to keep the default DOM small. Do not remove without a replacement (e.g. virtual scroll).
    const [sidebarSuspended, setSidebarSuspended] = useState(false);

    const handleSidebarSuspendedChange = useCallback((suspended) => {
        if (suspended) {
            flushSync(() => {
                setSidebarSuspended(true);
            });

            return;
        }

        setSidebarSuspended(false);
    }, []);

    return (
        <div className="flex flex-col gap-6">
            {usesAllFieldColumns ? (
                <div className="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-gray-700">
                    {Craft.t('formie', 'All non-cosmetic fields from the filtered forms are included automatically. Configure submission attributes below, or switch to manual selection to pick individual fields.')}
                </div>
            ) : null}

            {showFieldPicker ? (
                <div className="grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(320px,400px)] xl:items-start">
                    <div className="isolate min-w-0 [contain:layout]">
                        <ReportEnabledColumnsList
                            columns={enabledColumns}
                            disabled={disabled}
                            scrollable={scrollable}
                            showFieldPicker={showFieldPicker}
                            usesAllFieldColumns={usesAllFieldColumns}
                            useFieldHandles={useFieldHandles}
                            onEnabledChange={handleEnabledChange}
                            onLabelChange={handleLabelChange}
                            onReorder={handleEnabledColumnsReorder}
                            onSidebarSuspendedChange={handleSidebarSuspendedChange}
                        />
                    </div>

                    <div className="isolate min-h-0 min-w-0">
                        {sidebarSuspended ? (
                            <aside
                                aria-hidden
                                className="flex h-[min(65vh,600px)] min-h-0 flex-col items-center justify-center rounded-lg border border-gray-200 bg-gray-50 opacity-60"
                            >
                                <p className="m-0 px-4 text-center text-sm text-gray-500">
                                    {Craft.t('formie', 'Field picker paused while reordering columns.')}
                                </p>
                            </aside>
                        ) : (
                            <ReportAvailableColumnsSidebar
                                groups={fieldColumnGroups}
                                enabledColumns={enabledColumns}
                                disabled={disabled}
                                onToggleColumn={handleSidebarToggle}
                                onToggleFormColumns={handleSidebarToggleForm}
                            />
                        )}
                    </div>
                </div>
            ) : (
                <div>
                    <ReportEnabledColumnsList
                        columns={enabledColumns}
                        disabled={disabled}
                        scrollable={scrollable}
                        showFieldPicker={showFieldPicker}
                        usesAllFieldColumns={usesAllFieldColumns}
                        useFieldHandles={useFieldHandles}
                        onEnabledChange={handleEnabledChange}
                        onLabelChange={handleLabelChange}
                        onReorder={handleEnabledColumnsReorder}
                    />
                </div>
            )}
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

export const compactColumnsForStorage = (columns, fieldColumnsMode = 'all') => {
    const normalized = (columns || []).filter((column) => column?.handle).map((column) => {
        const item = {
            type: column.type || 'attribute',
            handle: column.handle,
            label: column.label ?? null,
            enabled: Boolean(column.enabled),
        };

        if (column.formId) {
            item.formId = column.formId;
        }

        return item;
    });

    if (!normalized.length) {
        return [];
    }

    const compact = normalized.filter((column) => {
        if ((column.type || 'attribute') === 'attribute') {
            return true;
        }

        if (isAllFieldColumnsMode(fieldColumnsMode)) {
            return false;
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

            if (column.formId) {
                existing.formId = column.formId;
            }

            return;
        }

        map.set(key, {
            type,
            handle: column.handle,
            label: column.label ?? null,
            enabled: Boolean(column.enabled),
            ...(column.formId ? { formId: column.formId } : {}),
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
