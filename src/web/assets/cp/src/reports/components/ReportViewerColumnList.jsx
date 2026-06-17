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

import { Checkbox } from '@verbb/plugin-kit-react/components';
import { cn } from '@verbb/plugin-kit-react/utils';
import { DragHandle } from '@field-palette/components/DragHandle';

import { columnKey } from '@reports/components/ReportColumnsEditor';

function ReportViewerColumnRowLayout({
    column,
    handleRef,
    onEnabledChange,
    ghost = false,
}) {
    const label = column.label || column.handle;

    return (
        <div className={cn('flex items-center gap-1 py-0', ghost && 'pointer-events-none select-none')}>
            <DragHandle
                handleRef={handleRef ?? (() => {})}
                disabled={ghost}
                ariaLabel={ghost
                    ? Craft.t('formie', 'Dragging column')
                    : Craft.t('formie', 'Drag to reorder {name}', { name: label })}
                className={ghost ? 'invisible' : undefined}
            />
            <Checkbox
                checked={Boolean(column.enabled)}
                disabled={ghost}
                className={ghost ? 'invisible' : undefined}
                onCheckedChange={ghost ? undefined : (enabled) => {
                    onEnabledChange?.(columnKey(column), enabled);
                }}
            />
            <span className="min-w-0 flex-1 truncate text-[13px] leading-tight text-gray-900">
                {label}
            </span>
        </div>
    );
}

function ReportViewerColumnDragGhost({ column }) {
    if (!column) {
        return null;
    }

    return <ReportViewerColumnRowLayout column={column} ghost />;
}

function ReportViewerColumnRow({
    column,
    index,
    listRef,
    onEnabledChange,
    isDragPlaceholder,
}) {
    const {
        ref, handleRef,
    } = useSortable({
        id: columnKey(column),
        index,
        transition: null,
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
            className={cn(isDragPlaceholder && '[&>*]:invisible')}
        >
            <ReportViewerColumnRowLayout
                column={column}
                handleRef={handleRef}
                onEnabledChange={onEnabledChange}
            />
        </div>
    );
}

export function ReportViewerColumnList({ columns, onChange }) {
    const listRef = useRef(null);
    const [activeColumn, setActiveColumn] = useState(null);
    const [draggingKey, setDraggingKey] = useState(null);

    const finishDragSession = useCallback(() => {
        setDraggingKey(null);
        setActiveColumn(null);
    }, []);

    const handleEnabledChange = useCallback((key, enabled) => {
        onChange(columns.map((column) => {
            if (columnKey(column) !== key) {
                return column;
            }

            return { ...column, enabled };
        }));
    }, [columns, onChange]);

    const handleDragEnd = useCallback((event) => {
        finishDragSession();

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
        onChange(nextColumns);
    }, [columns, finishDragSession, onChange]);

    return (
        <DragDropProvider
            onDragStart={(event) => {
                const key = event.operation.source?.id;

                if (!key) {
                    return;
                }

                setDraggingKey(String(key));
                setActiveColumn(columns.find((column) => columnKey(column) === key) || null);
            }}
            onDragEnd={handleDragEnd}
            onDragCancel={finishDragSession}
        >
            <div ref={listRef} className="max-h-64 overflow-y-auto overscroll-contain">
                {columns.map((column, index) => (
                    <ReportViewerColumnRow
                        key={columnKey(column)}
                        column={column}
                        index={index}
                        listRef={listRef}
                        onEnabledChange={handleEnabledChange}
                        isDragPlaceholder={draggingKey === columnKey(column)}
                    />
                ))}
            </div>

            <DragOverlay>
                {activeColumn ? <ReportViewerColumnDragGhost column={activeColumn} /> : null}
            </DragOverlay>
        </DragDropProvider>
    );
}
