import {
    useCallback, useEffect, useMemo, useRef, useState,
} from 'react';
import { cloneDeep, isEqual } from 'lodash-es';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';

import {
    faAsterisk,
    faArrowDown,
    faArrowLeft,
    faArrowRight,
    faArrowUp,
    faEllipsis,
    faPencil,
    faPlus,
    faTrash,
} from '@fortawesome/pro-solid-svg-icons';

import {
    DragDropProvider,
    DragOverlay,
    useDragDropManager,
    useDragOperation,
    useDraggable,
    useDroppable,
    PointerSensor,
    KeyboardSensor,
} from '@dnd-kit/react';
import { PointerActivationConstraints, Cursor } from '@dnd-kit/dom';
import { CollisionPriority, CollisionType } from '@dnd-kit/abstract';

import {
    Button,
    Select,
    SelectTrigger,
    SelectValue,
    SelectContent,
    SelectGroup,
    SelectLabel,
    SelectItem,
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogDescription,
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuSeparator,
    Lightswitch,
} from '@verbb/plugin-kit-react/components';

import {
    FieldControl,
    FieldLayout,
    SchemaFormEngine,
    useSchemaFormEngine,
    useEngineField,
} from '@verbb/plugin-kit-react/forms';

import { cn, createItem } from '@verbb/plugin-kit-react/utils';

import { useFormBuilderApp } from '@form-builder/contexts/FormBuilderAppContext';
import { useHandleSyncOnChange } from '@form-builder/hooks/useHandleSyncOnChange';
import { useNestedLayoutVariant } from '@form-builder/hooks/useNestedLayoutVariant';
import { getDevToolsConfig } from '@form-builder/dev/config';
import { SnapTopLeftCornerToCursor } from '@utils';
import { focusFirstVisibleInputIfEmpty } from '@form-builder/utils/focus';
import { submitSchemaFormAfterPendingTableUpdates } from '@form-builder/utils/submitSchemaForm';
import { assignFieldReferences } from '@form-builder/utils/fieldReferences';
import {
    collectFieldHandlesFromRows,
    prepareNewFieldForInsert,
} from '@form-builder/utils/duplicateField';

const EXCLUDED_SUB_FIELD_SETTING_NAMES = [
    'matchField',
    'includeInEmailFieldSummaries',
    'includeInEmail',
    'uniqueValue',
    'handle',
    'enableContentEncryption',
];

const toKeyedMap = (value) => {
    return value && typeof value === 'object' ? value : {};
};

const getByPath = (obj, path) => {
    if (!obj || typeof obj !== 'object' || !path) {
        return undefined;
    }

    if (Object.prototype.hasOwnProperty.call(obj, path)) {
        return obj[path];
    }

    return path.split('.').reduce((acc, key) => {
        if (!acc || typeof acc !== 'object') {
            return undefined;
        }

        return acc[key];
    }, obj);
};

const getFieldHandle = (field) => {
    return field?.handle ?? field?.settings?.handle ?? null;
};

const getFieldLabel = (field) => {
    return field?.label ?? field?.settings?.label ?? getFieldHandle(field) ?? Craft.t('formie', 'Field');
};

const getFieldEnabled = (field) => {
    if (typeof field?.enabled === 'boolean') {
        return field.enabled;
    }

    if (typeof field?.settings?.enabled === 'boolean') {
        return field.settings.enabled;
    }

    return true;
};

const getFieldRequired = (field) => {
    if (typeof field?.required === 'boolean') {
        return field.required;
    }

    if (typeof field?.settings?.required === 'boolean') {
        return field.settings.required;
    }

    return false;
};

const setFieldProp = (field, key, value) => {
    const nextField = {
        ...field,
        [key]: value,
    };

    if (nextField.settings && typeof nextField.settings === 'object') {
        nextField.settings = {
            ...nextField.settings,
            [key]: value,
        };
    }

    return nextField;
};

const sanitizeSubFieldSchema = (input, shouldSanitizeSettings = true) => {
    if (Array.isArray(input)) {
        return input.map((item) => {
            return sanitizeSubFieldSchema(item, shouldSanitizeSettings);
        }).filter(Boolean);
    }

    if (!input || typeof input !== 'object') {
        return input;
    }

    if (
        shouldSanitizeSettings
        && (
            (input.name && EXCLUDED_SUB_FIELD_SETTING_NAMES.includes(input.name))
            || (input.$field && EXCLUDED_SUB_FIELD_SETTING_NAMES.includes(input.$field))
        )
    ) {
        return null;
    }

    if (
        shouldSanitizeSettings
        && (
            (input.$cmp === 'ModalTabsTrigger' || input.$cmp === 'ModalTabsContent')
            && input?.props?.value === 'conditions'
        )
    ) {
        return null;
    }

    if (shouldSanitizeSettings && Array.isArray(input.fields) && input.fields.includes('conditions')) {
        return null;
    }

    const next = { ...input };

    if (next.children) {
        next.children = sanitizeSubFieldSchema(next.children, shouldSanitizeSettings);
    }

    if (next.schema) {
        next.schema = sanitizeSubFieldSchema(next.schema, shouldSanitizeSettings);
    }

    return next;
};

const sanitizeSubFieldSchemaIndex = (schemaIndex, shouldSanitizeSettings = true) => {
    if (!schemaIndex || typeof schemaIndex !== 'object') {
        return schemaIndex;
    }

    if (!shouldSanitizeSettings) {
        return schemaIndex;
    }

    return {
        ...schemaIndex,
        schema: sanitizeSubFieldSchema(schemaIndex.schema || []),
        fieldEntries: (schemaIndex.fieldEntries || []).filter((entry) => {
            const field = entry?.field || {};

            return !(
                (field.name && EXCLUDED_SUB_FIELD_SETTING_NAMES.includes(field.name))
                || (field.$field && EXCLUDED_SUB_FIELD_SETTING_NAMES.includes(field.$field))
            );
        }),
    };
};

const hasSchemaNodes = (schema) => {
    if (Array.isArray(schema)) {
        return schema.length > 0;
    }

    if (!schema || typeof schema !== 'object') {
        return false;
    }

    return Object.keys(schema).length > 0;
};

const POINTER_DRAG_ACTIVATION_DISTANCE = 4;

const normalizeRows = (rows, layoutConfig = {}, options = {}) => {
    const sourceRows = Array.isArray(rows) ? rows : [];
    const strictIdentity = Boolean(options.strictIdentity);
    const allowedFieldTypesList = Array.isArray(options.allowedFieldTypes) ? options.allowedFieldTypes : [];
    const allowedHandlesList = Array.isArray(layoutConfig?.allowedHandles) ? layoutConfig.allowedHandles : [];
    const allowedTypesList = Array.isArray(layoutConfig?.allowedTypes) ? layoutConfig.allowedTypes : [];
    const allowedHandles = strictIdentity && allowedHandlesList.length ? new Set(allowedHandlesList) : null;
    const allowedTypes = strictIdentity && allowedTypesList.length ? new Set(allowedTypesList) : null;
    const allowedFieldTypes = allowedFieldTypesList.length ? new Set(allowedFieldTypesList) : null;

    const normalizedRows = sourceRows.map((row) => {
        const fields = Array.isArray(row?.fields) ? row.fields : [];
        const nextFields = fields.filter((field) => {
            const handle = getFieldHandle(field);
            const type = field?.type ?? null;

            if (!handle) {
                return false;
            }

            if (allowedHandles && !allowedHandles.has(handle)) {
                return false;
            }

            if (allowedTypes && type && !allowedTypes.has(type)) {
                return false;
            }

            if (allowedFieldTypes && type && !allowedFieldTypes.has(type)) {
                return false;
            }

            return true;
        }).map((field) => {
            return createItem(assignFieldReferences(field));
        });

        if (!nextFields.length) {
            return null;
        }

        return {
            ...createItem(row),
            fields: nextFields,
        };
    }).filter(Boolean);

    return normalizedRows;
};

const removeFieldAt = (rows, rowIndex, fieldIndex) => {
    const nextRows = cloneDeep(rows);
    const row = nextRows[rowIndex];

    if (!row?.fields?.[fieldIndex]) {
        return rows;
    }

    row.fields.splice(fieldIndex, 1);

    if (!row.fields.length) {
        nextRows.splice(rowIndex, 1);
    }

    return nextRows;
};

const moveFieldIntoRow = (rows, sourceRowIndex, sourceFieldIndex, rowIndex, fieldIndex) => {
    if (sourceRowIndex === rowIndex && sourceFieldIndex === fieldIndex) {
        return rows;
    }

    if (sourceRowIndex === rowIndex && sourceFieldIndex === (fieldIndex - 1)) {
        return rows;
    }

    if (sourceRowIndex === rowIndex && rows[sourceRowIndex]?.fields?.length === 1) {
        return rows;
    }

    const nextRows = cloneDeep(rows);
    const sourceRow = nextRows[sourceRowIndex];

    if (!sourceRow?.fields?.[sourceFieldIndex]) {
        return rows;
    }

    const [fieldData] = sourceRow.fields.splice(sourceFieldIndex, 1);

    if (!sourceRow.fields.length) {
        nextRows.splice(sourceRowIndex, 1);

        if (sourceRowIndex < rowIndex) {
            rowIndex = rowIndex - 1;
        }
    }

    const targetRow = nextRows[rowIndex];

    if (!targetRow?.fields) {
        return rows;
    }

    targetRow.fields.splice(fieldIndex, 0, fieldData);

    return nextRows;
};

const moveFieldToOwnRow = (rows, sourceRowIndex, sourceFieldIndex, rowIndex) => {
    if (sourceRowIndex === rowIndex || sourceRowIndex === (rowIndex - 1)) {
        if (rows[sourceRowIndex]?.fields?.length === 1) {
            return rows;
        }
    }

    const nextRows = cloneDeep(rows);
    const sourceRow = nextRows[sourceRowIndex];

    if (!sourceRow?.fields?.[sourceFieldIndex]) {
        return rows;
    }

    const movedFields = sourceRow.fields.splice(sourceFieldIndex, 1);

    if (!sourceRow.fields.length) {
        nextRows.splice(sourceRowIndex, 1);

        if (sourceRowIndex < rowIndex) {
            rowIndex = rowIndex - 1;
        }
    }

    const newRow = {
        ...createItem({}),
        fields: movedFields,
    };

    nextRows.splice(rowIndex, 0, newRow);

    return nextRows;
};

const moveFieldWithinRow = (rows, rowIndex, fromFieldIndex, toFieldIndex) => {
    if (fromFieldIndex === toFieldIndex) {
        return rows;
    }

    const nextRows = cloneDeep(rows);
    const row = nextRows[rowIndex];

    if (!row?.fields?.[fromFieldIndex] || !row?.fields?.[toFieldIndex]) {
        return rows;
    }

    const [fieldData] = row.fields.splice(fromFieldIndex, 1);
    row.fields.splice(toFieldIndex, 0, fieldData);

    return nextRows;
};

const getNestedDropzoneHitboxPadding = (id) => {
    const stringId = String(id);

    if (stringId.startsWith('subfield-field-dropzone-')) {
        return { x: 8, y: 12 };
    }

    if (stringId.startsWith('subfield-row-dropzone-')) {
        return { x: 0, y: 8 };
    }

    return { x: 0, y: 0 };
};

const expandedNestedPointerIntersection = ({ dragOperation, droppable }) => {
    const pointerCoordinates = dragOperation?.position?.current;
    const rect = droppable?.shape?.boundingRectangle;

    if (!pointerCoordinates || !rect) {
        return null;
    }

    const { x: padX, y: padY } = getNestedDropzoneHitboxPadding(droppable.id);
    const left = rect.left - padX;
    const right = rect.right + padX;
    const top = rect.top - padY;
    const bottom = rect.bottom + padY;

    const isWithinX = pointerCoordinates.x >= left && pointerCoordinates.x <= right;
    const isWithinY = pointerCoordinates.y >= top && pointerCoordinates.y <= bottom;

    if (!isWithinX || !isWithinY) {
        return null;
    }

    const centerX = left + ((right - left) / 2);
    const centerY = top + ((bottom - top) / 2);
    const distance = Math.hypot(pointerCoordinates.x - centerX, pointerCoordinates.y - centerY);

    return {
        id: droppable.id,
        value: 1 / (distance + 1),
        type: CollisionType.PointerIntersection,
        priority: CollisionPriority.High,
    };
};

const ExpandedNestedDropzoneHitboxOverlay = ({ visible, relativeToRef = null }) => {
    const manager = useDragDropManager();

    const containers = useMemo(() => {
        const droppableRegistry = manager?.registry?.droppables;
        if (!droppableRegistry) {
            return [];
        }

        return Array.from(droppableRegistry);
    }, [manager]);

    if (!visible) {
        return null;
    }

    const referenceRect = relativeToRef?.current?.getBoundingClientRect?.();
    if (!referenceRect) {
        return null;
    }

    return (
        <div className="pointer-events-none absolute inset-0 z-[30]">
            {containers.map((container) => {
                const id = String(container.id);
                if (!id.startsWith('subfield-field-dropzone-') && !id.startsWith('subfield-row-dropzone-')) {
                    return null;
                }

                const measuredRect = container?.shape?.current ?? container?.rect?.current;
                const element = container?.element ?? container?.node?.current;
                const fallbackRect = element?.getBoundingClientRect?.();
                const rect = measuredRect || fallbackRect;

                if (!rect || rect.width <= 0 || rect.height <= 0) {
                    return null;
                }

                const { x: padX, y: padY } = getNestedDropzoneHitboxPadding(id);

                if (padX <= 0 && padY <= 0) {
                    return null;
                }

                return (
                    <div
                        key={`nested-expanded-hitbox-${id}`}
                        className="absolute bg-red-500/20"
                        style={{
                            left: `${(rect.left - padX) - referenceRect.left}px`,
                            top: `${(rect.top - padY) - referenceRect.top}px`,
                            width: `${rect.width + (padX * 2)}px`,
                            height: `${rect.height + (padY * 2)}px`,
                        }}
                    />
                );
            })}
        </div>
    );
};

const RowDropzone = ({
    id,
    rowIndex,
    showIndicator = false,
}) => {
    const { ref, isDropTarget } = useDroppable({
        id,
        data: {
            rowIndex,
        },
        collisionDetector: expandedNestedPointerIntersection,
    });
    const { source } = useDragOperation();

    return (
        <div className={cn(
            'relative',
            'h-0 px-2 w-full',
            'transform translate-y-[-4px]',
            'transition-opacity',
            source || showIndicator ? 'opacity-100 z-10' : 'opacity-0 -z-1',
        )}>
            <div className={cn(
                'w-full',
                'h-[8px]',
                'border rounded-sm',
                isDropTarget ? 'border-[#0d99f2] bg-[#0d99f2]' : 'border-[#6ec2f7] bg-[#e5f5f8]',
            )}
            ref={ref}
            />
        </div>
    );
};

const FieldDropzone = ({
    id,
    rowIndex,
    fieldIndex,
    showIndicator = false,
}) => {
    const { ref, isDropTarget } = useDroppable({
        id,
        data: {
            rowIndex,
            fieldIndex,
        },
        collisionDetector: expandedNestedPointerIntersection,
    });
    const { source } = useDragOperation();

    return (
        <div className={cn(
            'relative',
            'w-0 py-2',
            'transform translate-x-[-4px]',
            'transition-opacity',
            source || showIndicator ? 'opacity-100 z-10' : 'opacity-0 -z-1',
        )}>
            <div className={cn(
                'w-[8px]',
                'h-full',
                'border rounded-sm',
                isDropTarget ? 'border-[#0d99f2] bg-[#0d99f2]' : 'border-[#6ec2f7] bg-[#e5f5f8]',
            )}
            ref={ref}
            />
        </div>
    );
};

const SubFieldEditModal = ({
    field,
    schemaDefinition,
    fieldType,
    shouldSanitizeSettings = true,
    onSave,
    onCancel,
}) => {
    const contentRef = useRef(null);
    const hasAutofocusedRef = useRef(false);

    const sanitizedSchema = useMemo(() => {
        const fieldSchema = schemaDefinition?.schema || fieldType?.schemaIndex?.schema || fieldType?.schema || [];
        return sanitizeSubFieldSchema(fieldSchema, shouldSanitizeSettings);
    }, [schemaDefinition, fieldType, shouldSanitizeSettings]);
    const rawSchemaIndex = schemaDefinition?.schema
        ? (schemaDefinition?.schemaIndex || null)
        : (fieldType?.schemaIndex || null);
    const schemaIndex = useMemo(() => {
        return sanitizeSubFieldSchemaIndex(rawSchemaIndex, shouldSanitizeSettings);
    }, [rawSchemaIndex, shouldSanitizeSettings]);
    const hasSchemaConfig = hasSchemaNodes(sanitizedSchema);
    const fallbackSchemaIndex = useMemo(() => {
        return {
            schema: sanitizedSchema,
            fieldEntries: [],
        };
    }, [sanitizedSchema]);
    const handleSyncOnChange = useHandleSyncOnChange(sanitizedSchema || []);

    const form = useSchemaFormEngine({
        schema: sanitizedSchema,
        schemaIndex: schemaIndex || fallbackSchemaIndex,
        defaultValues: field,
        onChange: (values, formApi) => {
            handleSyncOnChange(values, formApi);
        },
    });

    form.onSuccess((data) => {
        onSave(data);
    });

    useEffect(() => {
        if (hasAutofocusedRef.current) {
            return;
        }

        hasAutofocusedRef.current = true;

        return focusFirstVisibleInputIfEmpty({
            root: contentRef.current,
        });
    }, []);

    return (
        <DialogContent className={cn(
            'w-[calc(100vw-56px)] h-[calc(100dvh-56px)]',
            'min-w-0 min-h-0 max-w-none',
            'md:w-[56%] md:h-[56%]',
            'md:min-w-[500px] md:min-h-[300px]',
        )}
        >
            <DialogHeader>
                <DialogTitle className="flex flex-row items-center">
                    {Craft.t('formie', 'Edit Field')}

                    <div className={cn(
                        'rounded-[20px]',
                        'bg-[#d8e2ea]',
                        'px-[10px] py-[6px]',
                        'text-[10px]',
                        'text-[#526176]',
                        'ml-[10px]',
                        'font-normal',
                    )}>{fieldType?.label || Craft.t('formie', 'Sub-Field')}</div>
                </DialogTitle>

                <DialogDescription className="hidden">
                    {Craft.t('formie', 'Edit the sub-field settings.')}
                </DialogDescription>
            </DialogHeader>

            <div ref={contentRef} className="flex-1 min-h-0 overflow-hidden">
                {hasSchemaConfig ? (
                    <SchemaFormEngine
                        form={form}
                        className="h-full"
                    />
                ) : (
                    <div className="flex h-full items-center justify-center">
                        <div className="text-sm text-rose-600">
                            {Craft.t('formie', 'Field settings are unavailable. Please reload the builder.')}
                        </div>
                    </div>
                )}
            </div>

            <DialogFooter className="flex flex-row justify-end gap-2">
                <Button
                    type="button"
                    onClick={onCancel}
                >
                    {Craft.t('formie', 'Cancel')}
                </Button>

                <Button
                    type="button"
                    variant="primary"
                    onClick={() => {
                        submitSchemaFormAfterPendingTableUpdates(form);
                    }}
                    disabled={!hasSchemaConfig}
                >
                    {Craft.t('formie', 'Apply')}
                </Button>
            </DialogFooter>
        </DialogContent>
    );
};

const SubFieldCard = ({
    field,
    nestedFieldType,
    rowIndex,
    fieldIndex,
    rowFieldCount,
    totalRowCount,
    canRemove,
    onToggleEnabled,
    onToggleRequired,
    onOpenEdit,
    onDelete,
    onMoveUp,
    onMoveDown,
    onMoveLeft,
    onMoveRight,
}) => {
    const [isDropdownOpen, setIsDropdownOpen] = useState(false);
    const draggableFieldId = field?._id || field?.id || `${rowIndex}-${fieldIndex}`;
    const {
        ref,
        isDragging,
    } = useDraggable({
        id: `subfield-card-${draggableFieldId}`,
        data: {
            rowIndex,
            fieldIndex,
            field,
            fieldType: nestedFieldType,
        },
        modifiers: [
            SnapTopLeftCornerToCursor,
        ],
    });

    const canMoveUp = rowIndex > 0 || rowFieldCount > 1;
    const canMoveDown = rowIndex < totalRowCount - 1 || rowFieldCount > 1;
    const canMoveLeft = fieldIndex > 0;
    const canMoveRight = fieldIndex < rowFieldCount - 1;

    return (
        <div
            ref={ref}
            onClick={(event) => {
                if (event.target.closest('[data-no-open-edit]')) {
                    return;
                }

                onOpenEdit();
            }}
            className={cn(
                'w-full min-w-0 max-w-full',
                'rounded-lg',
                'bg-white',
                'shadow-[0_0_0_1px_rgba(31,41,51,0.1),0_2px_5px_-2px_rgba(31,41,51,0.2)]',
                'px-3 py-2.5',
                'cursor-pointer select-none',
                'relative group',
                isDragging ? 'opacity-50' : '',
            )}
        >
            <div className={cn(
                'flex items-center gap-2',
            )}>
                <div
                    data-no-open-edit
                    onPointerDown={(event) => {
                        event.stopPropagation();
                    }}
                    onClick={(event) => {
                        event.stopPropagation();
                    }}
                >
                    <Lightswitch
                        size="xs"
                        checked={getFieldEnabled(field)}
                        onCheckedChange={(checked) => {
                            onToggleEnabled(checked);
                        }}
                    />
                </div>

                <button
                    type="button"
                    className={cn(
                        'text-left truncate text-[#5f6c7b] font-medium text-sm',
                        'flex items-center gap-1 min-w-0',
                        'cursor-pointer',
                        'hover:text-[#33475b]',
                    )}
                    onPointerDown={(event) => {
                        event.stopPropagation();
                    }}
                    onClick={(event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        onOpenEdit();
                    }}
                    data-no-open-edit
                >
                    <span className="truncate">{getFieldLabel(field)}</span>

                    {getFieldRequired(field) && (
                        <span className="text-error">
                            <FontAwesomeIcon icon={faAsterisk} className="size-[10px]" />
                        </span>
                    )}
                </button>

                <div className={cn(
                    'ml-auto',
                    'form-builder-field-actions',
                    'opacity-0 group-hover:opacity-100 transition-opacity',
                    isDropdownOpen ? 'opacity-100' : '',
                )} data-no-open-edit>
                    <DropdownMenu size="sm" open={isDropdownOpen} onOpenChange={setIsDropdownOpen}>
                        <DropdownMenuTrigger
                            render={(
                                <Button
                                    variant="transparent"
                                    size="sm"
                                    className={cn(
                                        'w-7 h-7 p-0 rounded-lg',
                                    )}
                                    data-dropdown-trigger
                                />
                            )}
                        >
                            <FontAwesomeIcon icon={faEllipsis} className="size-3.5" />
                        </DropdownMenuTrigger>

                        <DropdownMenuContent align="end" className="min-w-[150px]">
                            <DropdownMenuItem
                                onClick={(event) => {
                                    event.preventDefault();
                                    event.stopPropagation();
                                    onOpenEdit();
                                    setIsDropdownOpen(false);
                                }}
                            >
                                <FontAwesomeIcon icon={faPencil} />
                                {Craft.t('formie', 'Edit')}
                            </DropdownMenuItem>

                            <DropdownMenuItem
                                onClick={(event) => {
                                    event.preventDefault();
                                    event.stopPropagation();
                                    onToggleRequired();
                                    setIsDropdownOpen(false);
                                }}
                            >
                                <FontAwesomeIcon icon={faAsterisk} />
                                {getFieldRequired(field)
                                    ? Craft.t('formie', 'Make optional')
                                    : Craft.t('formie', 'Make required')}
                            </DropdownMenuItem>

                            <DropdownMenuSeparator />

                            <DropdownMenuItem
                                onClick={(event) => {
                                    event.preventDefault();
                                    event.stopPropagation();
                                    onMoveUp();
                                    setIsDropdownOpen(false);
                                }}
                                disabled={!canMoveUp}
                                className={cn(!canMoveUp && 'opacity-50 pointer-events-none')}
                            >
                                <FontAwesomeIcon icon={faArrowUp} />
                                {Craft.t('formie', 'Move up')}
                            </DropdownMenuItem>

                            <DropdownMenuItem
                                onClick={(event) => {
                                    event.preventDefault();
                                    event.stopPropagation();
                                    onMoveDown();
                                    setIsDropdownOpen(false);
                                }}
                                disabled={!canMoveDown}
                                className={cn(!canMoveDown && 'opacity-50 pointer-events-none')}
                            >
                                <FontAwesomeIcon icon={faArrowDown} />
                                {Craft.t('formie', 'Move down')}
                            </DropdownMenuItem>

                            <DropdownMenuItem
                                onClick={(event) => {
                                    event.preventDefault();
                                    event.stopPropagation();
                                    onMoveLeft();
                                    setIsDropdownOpen(false);
                                }}
                                disabled={!canMoveLeft}
                                className={cn(!canMoveLeft && 'opacity-50 pointer-events-none')}
                            >
                                <FontAwesomeIcon icon={faArrowLeft} />
                                {Craft.t('formie', 'Move left')}
                            </DropdownMenuItem>

                            <DropdownMenuItem
                                onClick={(event) => {
                                    event.preventDefault();
                                    event.stopPropagation();
                                    onMoveRight();
                                    setIsDropdownOpen(false);
                                }}
                                disabled={!canMoveRight}
                                className={cn(!canMoveRight && 'opacity-50 pointer-events-none')}
                            >
                                <FontAwesomeIcon icon={faArrowRight} />
                                {Craft.t('formie', 'Move right')}
                            </DropdownMenuItem>

                            {canRemove && (
                                <DropdownMenuItem
                                    className="text-error focus:text-error"
                                    onClick={(event) => {
                                        event.preventDefault();
                                        event.stopPropagation();
                                        onDelete();
                                        setIsDropdownOpen(false);
                                    }}
                                >
                                    <FontAwesomeIcon icon={faTrash} />
                                    {Craft.t('formie', 'Delete')}
                                </DropdownMenuItem>
                            )}
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>
    );
};

const SubFieldPillGhost = ({ activeDragField }) => {
    if (!activeDragField?.field) {
        return null;
    }

    const activeField = activeDragField.field;

    return (
        <div
            className={cn(
                'min-w-[72px] max-w-[130px]',
                'px-[8px] py-[10px]',
                'bg-white',
                'text-[#33475b] text-[13px]',
                'rounded-sm border border-[#cbd6e2]',
                'flex items-center',
                'shadow-lg',
                'opacity-90',
                '!cursor-default',
            )}
        >
            <span className="truncate">
                {getFieldLabel(activeField)}
            </span>
        </div>
    );
};

export const NestedLayoutField = ({ form, field }) => {
    const { getFieldTypeByType } = useFormBuilderApp();
    const [editingFieldState, setEditingFieldState] = useState(null);
    const [activeDragField, setActiveDragField] = useState(null);
    const nestedBuilderRef = useRef(null);
    const { childProps, parentType, layoutKey } = useNestedLayoutVariant({ form, field });
    const parentFieldTypeConfig = getFieldTypeByType(parentType);
    const nestedLayoutBuilderConfig = toKeyedMap(parentFieldTypeConfig?.data?.nestedLayoutBuilder);
    const layoutsConfig = useMemo(() => {
        return toKeyedMap(nestedLayoutBuilderConfig.layouts);
    }, [nestedLayoutBuilderConfig]);
    const layoutConfig = useMemo(() => {
        return getByPath(layoutsConfig, layoutKey) || layoutsConfig.rows || {};
    }, [layoutsConfig, layoutKey]);
    const editorSchemaByType = toKeyedMap(nestedLayoutBuilderConfig.editorSchemaByType);
    const policy = toKeyedMap(nestedLayoutBuilderConfig.policy);
    const operations = toKeyedMap(policy.operations);
    const isFixedMode = policy.mode === 'fixed';
    const canAdd = Boolean(operations.allowAdd);
    const canRemove = Boolean(operations.allowRemove);
    const allowedFieldTypes = useMemo(() => {
        return Array.isArray(nestedLayoutBuilderConfig.allowedFieldTypes) ? nestedLayoutBuilderConfig.allowedFieldTypes : [];
    }, [nestedLayoutBuilderConfig.allowedFieldTypes]);
    const addableFieldTypes = useMemo(() => {
        return allowedFieldTypes.map((type) => {
            return getFieldTypeByType(type);
        }).filter(Boolean);
    }, [allowedFieldTypes, getFieldTypeByType]);

    const {
        value, setValue, errors,
    } = useEngineField(form, layoutKey);

    const rows = useMemo(() => {
        return normalizeRows(value, layoutConfig, {
            strictIdentity: isFixedMode,
            allowedFieldTypes,
        });
    }, [value, layoutConfig, isFixedMode, allowedFieldTypes]);
    const builderDevSettings = useMemo(() => {
        if (!import.meta.env.DEV) {
            return null;
        }

        return getDevToolsConfig();
    }, []);
    const showExpandedDropzoneHitboxes = Boolean(
        builderDevSettings?.enabled && builderDevSettings?.showExpandedNestedDropzoneHitboxes,
    );
    const showStructureIds = Boolean(
        builderDevSettings?.enabled && builderDevSettings?.showNestedRowAndFieldIds,
    );

    const sensors = useMemo(() => {
        return [
            PointerSensor.configure({
                activationConstraints: [
                    new PointerActivationConstraints.Distance({
                        value: POINTER_DRAG_ACTIVATION_DISTANCE,
                    }),
                ],
            }),
            KeyboardSensor,
        ];
    }, []);

    const dragDropPlugins = useCallback((defaults) => {
        return defaults.map((plugin) => {
            if (plugin === Cursor) {
                return Cursor.configure({
                    cursor: 'default',
                });
            }

            return plugin;
        });
    }, []);

    const persistRows = (nextRows) => {
        const normalizedRows = normalizeRows(nextRows, layoutConfig, {
            strictIdentity: isFixedMode,
            allowedFieldTypes,
        });
        if (!isEqual(normalizedRows, rows)) {
            setValue(normalizedRows);
        }
    };

    const handleDragStart = (event) => {
        const source = event?.operation?.source;
        const activeData = source?.data?.current ?? source?.data;
        setActiveDragField(activeData || null);
    };

    const handleDragEnd = (event) => {
        setActiveDragField(null);
        const source = event?.operation?.source;
        const target = event?.operation?.target;

        if (!target || !source?.id) {
            return;
        }

        const activeData = source?.data?.current ?? source?.data;
        if (!activeData) {
            return;
        }

        const sourceRowIndex = Number(activeData.rowIndex);
        const sourceFieldIndex = Number(activeData.fieldIndex);

        if (Number.isNaN(sourceRowIndex) || Number.isNaN(sourceFieldIndex)) {
            return;
        }

        const dropData = target?.data?.current ?? target?.data;

        if (!dropData) {
            return;
        }

        if (String(target.id).startsWith('subfield-field-dropzone-')) {
            const nextRows = moveFieldIntoRow(
                rows,
                sourceRowIndex,
                sourceFieldIndex,
                Number(dropData.rowIndex),
                Number(dropData.fieldIndex),
            );
            persistRows(nextRows);
        }

        if (String(target.id).startsWith('subfield-row-dropzone-')) {
            const nextRows = moveFieldToOwnRow(
                rows,
                sourceRowIndex,
                sourceFieldIndex,
                Number(dropData.rowIndex),
            );
            persistRows(nextRows);
        }
    };

    const updateField = (rowIndex, fieldIndex, updater) => {
        const nextRows = cloneDeep(rows);
        const currentField = nextRows?.[rowIndex]?.fields?.[fieldIndex];

        if (!currentField) {
            return;
        }

        nextRows[rowIndex].fields[fieldIndex] = updater(currentField);
        persistRows(nextRows);
    };

    const moveFieldUp = (rowIndex, fieldIndex) => {
        const currentRow = rows[rowIndex];
        const prevRow = rows[rowIndex - 1];

        if (prevRow) {
            const nextRows = moveFieldIntoRow(rows, rowIndex, fieldIndex, rowIndex - 1, prevRow.fields.length);
            persistRows(nextRows);
            return;
        }

        if (rowIndex === 0 && (currentRow?.fields?.length || 0) > 1) {
            const nextRows = moveFieldToOwnRow(rows, rowIndex, fieldIndex, 0);
            persistRows(nextRows);
        }
    };

    const moveFieldDown = (rowIndex, fieldIndex) => {
        const currentRow = rows[rowIndex];
        const nextRow = rows[rowIndex + 1];

        if (nextRow) {
            const nextRows = moveFieldIntoRow(rows, rowIndex, fieldIndex, rowIndex + 1, nextRow.fields.length);
            persistRows(nextRows);
            return;
        }

        if (rowIndex === rows.length - 1 && (currentRow?.fields?.length || 0) > 1) {
            const nextRows = moveFieldToOwnRow(rows, rowIndex, fieldIndex, rowIndex + 1);
            persistRows(nextRows);
        }
    };

    const moveFieldLeft = (rowIndex, fieldIndex) => {
        if (fieldIndex <= 0) {
            return;
        }

        const nextRows = moveFieldWithinRow(rows, rowIndex, fieldIndex, fieldIndex - 1);
        persistRows(nextRows);
    };

    const moveFieldRight = (rowIndex, fieldIndex) => {
        const rowFields = rows[rowIndex]?.fields || [];

        if (fieldIndex >= rowFields.length - 1) {
            return;
        }

        const nextRows = moveFieldWithinRow(rows, rowIndex, fieldIndex, fieldIndex + 1);
        persistRows(nextRows);
    };

    const handleAddFieldType = (type) => {
        if (!type) {
            return;
        }

        const fieldType = getFieldTypeByType(type);

        if (!fieldType?.newField) {
            return;
        }

        const existingHandles = [];
        collectFieldHandlesFromRows(rows, existingHandles);

        const newField = prepareNewFieldForInsert({
            ...cloneDeep(fieldType.newField),
            _isNew: true,
        }, existingHandles, fieldType);

        const nextRows = [
            ...rows,
            {
                ...createItem({}),
                fields: [createItem(assignFieldReferences(newField))],
            },
        ];

        persistRows(nextRows);
    };

    return (
        <>
            <FieldLayout
                name={layoutKey}
                label={field.label}
                instructions={field.instructions}
                warning={field.warning}
                required={field.required}
                errors={errors}
                withControl={false}
            >
                <DragDropProvider
                    sensors={sensors}
                    plugins={dragDropPlugins}
                    onDragStart={handleDragStart}
                    onDragEnd={handleDragEnd}
                >
                    <FieldControl>
                        <div
                            ref={nestedBuilderRef}
                            role="group"
                            className={cn(
                                'relative',
                                'rounded-[3px] p-4',
                                'bg-gray-50',
                                'shadow-[inset_0_1px_3px_-1px_#acbed2]',
                                'bg-[linear-gradient(to_right,var(--gray-100)_1px,transparent_0),linear-gradient(to_bottom,var(--gray-100)_1px,transparent_1px)]',
                                'bg-[position:-1px_-1px]',
                                'bg-[size:24px_24px]',
                            )}>
                            {canAdd && (
                                <div className={cn(
                                    'mb-3',
                                    'w-full max-w-[220px]',
                                )}>
                                    <Select onValueChange={handleAddFieldType} size="sm">
                                        <SelectTrigger className="w-full p-1.5">
                                            <FontAwesomeIcon icon={faPlus} className="size-3 text-gray-500" />
                                            <SelectValue placeholder={Craft.t('formie', 'Add nested field')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectLabel>{Craft.t('formie', 'Available Fields')}</SelectLabel>
                                                {addableFieldTypes.map((fieldTypeOption) => {
                                                    return (
                                                        <SelectItem key={fieldTypeOption.type} value={fieldTypeOption.type}>
                                                            {fieldTypeOption.label}
                                                        </SelectItem>
                                                    );
                                                })}
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}

                        {rows.map((row, rowIndex) => {
                            const rowFields = row?.fields || [];
                            const rowIdentity = row?._id || row?.id || `row-${rowIndex}`;

                            return (
                                <div
                                    key={row._id || row.id || rowIndex}
                                    data-row-id={row._id || row.id}
                                    className={cn(
                                        'mb-2',
                                        'relative',
                                        rowIndex === rows.length - 1 && 'mb-0',
                                    )}>
                                    {showStructureIds && (
                                        <div className="pointer-events-none absolute left-2 top-1 z-20 rounded bg-slate-900/75 px-1.5 py-0.5 text-[10px] leading-none text-white">
                                            {`row:${row._id || row.id || 'unknown'}`}
                                        </div>
                                    )}

                                    {rowIndex === 0 && (
                                        <RowDropzone
                                            id={`subfield-row-dropzone-before-${rowIdentity}`}
                                            rowIndex={0}
                                            showIndicator={showExpandedDropzoneHitboxes}
                                        />
                                    )}

                                    <div
                                        className="grid gap-2"
                                        style={{
                                            gridTemplateColumns: 'repeat(auto-fit, minmax(min(180px, 100%), 1fr))',
                                        }}
                                    >
                                        {rowFields.map((subField, fieldIndex) => {
                                            const subFieldIdentity = subField?._id || subField?.id || `${rowIndex}-${fieldIndex}`;

                                            return (
                                                <div
                                                    key={subField._id || subField.id || `${rowIndex}-${fieldIndex}`}
                                                    data-field-id={subField._id || subField.id}
                                                    className={cn(
                                                        'relative',
                                                        'min-h-[1px]',
                                                        'flex min-w-0 max-w-full',
                                                    )}
                                                >
                                                    {showStructureIds && (
                                                        <div className="pointer-events-none absolute right-2 top-1 z-20 rounded bg-indigo-700/80 px-1.5 py-0.5 text-[10px] leading-none text-white">
                                                            {`field:${subField._id || subField.id || 'unknown'}`}
                                                        </div>
                                                    )}

                                                    {fieldIndex === 0 && (
                                                        <FieldDropzone
                                                            id={`subfield-field-dropzone-${rowIdentity}-before-${subFieldIdentity}`}
                                                            rowIndex={rowIndex}
                                                            fieldIndex={0}
                                                            showIndicator={showExpandedDropzoneHitboxes}
                                                        />
                                                    )}

                                                    <SubFieldCard
                                                        field={subField}
                                                        nestedFieldType={getFieldTypeByType(subField.type)}
                                                        rowIndex={rowIndex}
                                                        fieldIndex={fieldIndex}
                                                        rowFieldCount={rowFields.length}
                                                        totalRowCount={rows.length}
                                                        canRemove={canRemove}
                                                        onToggleEnabled={(enabled) => {
                                                            updateField(rowIndex, fieldIndex, (currentField) => {
                                                                return setFieldProp(currentField, 'enabled', enabled);
                                                            });
                                                        }}
                                                        onToggleRequired={() => {
                                                            updateField(rowIndex, fieldIndex, (currentField) => {
                                                                return setFieldProp(currentField, 'required', !getFieldRequired(currentField));
                                                            });
                                                        }}
                                                        onOpenEdit={() => {
                                                            setEditingFieldState({
                                                                rowIndex,
                                                                fieldIndex,
                                                                field: subField,
                                                            });
                                                        }}
                                                        onDelete={() => {
                                                            const nextRows = removeFieldAt(rows, rowIndex, fieldIndex);
                                                            persistRows(nextRows);
                                                        }}
                                                        onMoveUp={() => {
                                                            moveFieldUp(rowIndex, fieldIndex);
                                                        }}
                                                        onMoveDown={() => {
                                                            moveFieldDown(rowIndex, fieldIndex);
                                                        }}
                                                        onMoveLeft={() => {
                                                            moveFieldLeft(rowIndex, fieldIndex);
                                                        }}
                                                        onMoveRight={() => {
                                                            moveFieldRight(rowIndex, fieldIndex);
                                                        }}
                                                    />

                                                    <FieldDropzone
                                                        id={`subfield-field-dropzone-${rowIdentity}-after-${subFieldIdentity}`}
                                                        rowIndex={rowIndex}
                                                        fieldIndex={fieldIndex + 1}
                                                        showIndicator={showExpandedDropzoneHitboxes}
                                                    />
                                                </div>
                                            );
                                        })}
                                    </div>

                                    <RowDropzone
                                        id={`subfield-row-dropzone-after-${rowIdentity}`}
                                        rowIndex={rowIndex + 1}
                                        showIndicator={showExpandedDropzoneHitboxes}
                                    />
                                </div>
                            );
                        })}
                            <ExpandedNestedDropzoneHitboxOverlay
                                visible={showExpandedDropzoneHitboxes}
                                relativeToRef={nestedBuilderRef}
                            />
                        </div>
                    </FieldControl>

                    <DragOverlay dropAnimation={null}>
                        <SubFieldPillGhost activeDragField={activeDragField} />
                    </DragOverlay>
                </DragDropProvider>
            </FieldLayout >

            {editingFieldState && (
                <Dialog open={true} onOpenChange={() => { return setEditingFieldState(null); }}>
                    <SubFieldEditModal
                        field={editingFieldState.field}
                        schemaDefinition={editorSchemaByType[editingFieldState.field.type]}
                        fieldType={getFieldTypeByType(editingFieldState.field.type)}
                        shouldSanitizeSettings={isFixedMode}
                        onSave={(updatedField) => {
                            updateField(editingFieldState.rowIndex, editingFieldState.fieldIndex, () => {
                                return updatedField;
                            });
                            setEditingFieldState(null);
                        }}
                        onCancel={() => {
                            setEditingFieldState(null);
                        }}
                    />
                </Dialog>
            )
            }
        </>
    );
};
