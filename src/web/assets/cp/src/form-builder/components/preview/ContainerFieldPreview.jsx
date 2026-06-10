import React, {
    useEffect, useMemo, useRef, useState,
} from 'react';
import { useDragOperation, useDraggable, useDroppable } from '@dnd-kit/react';
import { CollisionPriority, CollisionType } from '@dnd-kit/abstract';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';

import {
    faArrowDown,
    faArrowLeft,
    faArrowRight,
    faArrowUp,
    faAsterisk,
    faClone,
    faEllipsis,
    faEye,
    faLinkSlash,
    faPencil,
    faPlus,
    faTrash,
} from '@fortawesome/pro-solid-svg-icons';

import {
    faPlusSquare,
} from '@fortawesome/pro-regular-svg-icons';

import {
    Button,
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@verbb/plugin-kit-react/components';

import { SchemaFormEngine, useSchemaFormEngine } from '@verbb/plugin-kit-react/forms';
import { cn } from '@verbb/plugin-kit-react/utils';
import { useFormBuilderApp } from '@form-builder/contexts/FormBuilderAppContext';
import { useFormBuilderForm } from '@form-builder/contexts/FormBuilderFormContext';
import useAppStore from '@form-builder/hooks/useAppStore';
import { useBuilderActions } from '@form-builder/builder/useBuilderActions';
import { MAX_FIELDS_PER_ROW } from '@form-builder/builder/constants';
import { useHandleSyncOnChange } from '@form-builder/hooks/useHandleSyncOnChange';
import { useFieldEditorDismiss } from '@form-builder/hooks/useFieldEditorDismiss';
import {
    collectFieldHandlesFromRows,
    buildDuplicatedFieldData,
    detachSyncedFieldData,
} from '@form-builder/utils/duplicateField';
import {
    injectReservedHandlesIntoSchema,
    injectReservedHandlesIntoSchemaIndex,
    collectNestedReservedHandles,
} from '@form-builder/utils/handleValidation';
import { renderFieldPreviewSchema } from './renderFieldPreviewTemplate';
import { canDropInNestedContainer } from '@form-builder/builder/nestedMoveUtils';
import { getDevToolsConfig } from '@form-builder/dev/config';
import { focusFirstVisibleInputIfEmpty } from '@form-builder/utils/focus';
import { resolveContainerRows } from '@form-builder/utils/containerLayoutVariants';
import { announceFormBuilderStatus, focusFieldActionsTrigger } from '@form-builder/utils/accessibility';
import { submitSchemaFormAfterPendingTableUpdates } from '@form-builder/utils/submitSchemaForm';
import { SnapTopLeftCornerToCursor } from '@utils';

const EXCLUDED_SUB_FIELD_SETTING_NAMES = [
    'matchField',
    'includeInEmailFieldSummaries',
    'includeInEmail',
    'uniqueValue',
    'handle',
    'enableContentEncryption',
];

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

const getContainerNestedDropzoneHitboxPadding = (id) => {
    const stringId = String(id);

    if (stringId.startsWith('nested-field|') || stringId.includes('|field-before|') || stringId.includes('|field-after|')) {
        return { x: 10, y: 14 };
    }

    if (stringId.startsWith('nested-row|') || stringId.includes('|row-before|') || stringId.includes('|row-after|')) {
        return { x: 0, y: 10 };
    }

    if (stringId.startsWith('nested-empty|') || stringId.includes('|empty|')) {
        return { x: 8, y: 8 };
    }

    return { x: 0, y: 0 };
};

const expandedContainerNestedPointerIntersection = ({ dragOperation, droppable }) => {
    const pointerCoordinates = dragOperation?.position?.current;
    const rect = droppable?.shape?.boundingRectangle;

    if (!pointerCoordinates || !rect) {
        return null;
    }

    const { x: padX, y: padY } = getContainerNestedDropzoneHitboxPadding(droppable.id);
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

const NestedDropzone = ({
    id, data, kind = 'field', disabled = false, alwaysVisible = false,
}) => {
    const { ref, isDropTarget } = useDroppable({
        id,
        data,
        collisionDetector: expandedContainerNestedPointerIntersection,
    });
    const { source } = useDragOperation();

    const isVisible = alwaysVisible || (source && !disabled);

    if (kind === 'empty') {
        return (
            <div
                ref={ref}
                className={cn(
                    'w-full rounded-md border border-dashed px-3 py-6 text-center transition-opacity',
                    isDropTarget ? 'border-[#0d99f2] bg-[#e5f5f8]' : 'border-[#9fb4cb] bg-white',
                    isVisible ? 'opacity-100 z-10' : 'opacity-0 -z-1',
                )}
            >
                <span className="text-[#60758a] text-[14px] font-medium">
                    {Craft.t('formie', 'Drag and drop a field here')}
                </span>
            </div>
        );
    }

    if (kind === 'row') {
        return (
            <div className={cn(
                'relative h-0 w-full transform translate-y-[-4px] transition-opacity',
                'px-[12px]',
                isVisible ? 'opacity-100 z-10' : 'opacity-0 -z-1',
            )}>
                <div className={cn(
                    'w-full h-[8px] border rounded-sm',
                    isDropTarget ? 'border-[#0d99f2] bg-[#0d99f2]' : 'border-[#6ec2f7] bg-[#e5f5f8]',
                )} ref={ref} />
            </div>
        );
    }

    return (
        <div className={cn(
            'relative w-0 transform translate-x-[-4px] transition-opacity',
            'pb-[12px] pt-[34px]',
            isVisible ? 'opacity-100 z-10' : 'opacity-0 -z-1',
        )}>
            <div className={cn(
                'w-[8px] h-full border rounded-sm',
                isDropTarget ? 'border-[#0d99f2] bg-[#0d99f2]' : 'border-[#6ec2f7] bg-[#e5f5f8]',
            )} ref={ref} />
        </div>
    );
};

const NestedFieldEditModal = ({
    field,
    fieldType,
    reservedHandles = [],
    shouldSanitizeSettings = true,
    dismissAttemptRef = null,
    onSave,
    onCancel,
    onDismiss,
    onDelete,
}) => {
    const contentRef = useRef(null);
    const hasAutofocusedRef = useRef(false);
    const activeFieldType = fieldType;
    const resolvedFieldTypeLabel = typeof activeFieldType?.label === 'string'
        ? activeFieldType.label.trim()
        : '';
    const showFieldTypePill = resolvedFieldTypeLabel !== '';
    const isSyncedField = Boolean(field?.isSynced || field?.syncId);
    const shouldUseFieldLabel = activeFieldType?.hasLabel !== false;
    const fieldDisplayLabel = shouldUseFieldLabel
        ? (field?.label || activeFieldType?.label || Craft.t('formie', 'Field'))
        : (activeFieldType?.label || Craft.t('formie', 'Field'));
    const hasSchemaConfig = Boolean(activeFieldType?.schemaIndex || activeFieldType?.schema);
    const rawFieldSchema = activeFieldType?.schemaIndex?.schema ?? activeFieldType?.schema ?? [];
    const sanitizedSchemaIndex = useMemo(() => {
        return sanitizeSubFieldSchemaIndex(activeFieldType?.schemaIndex, shouldSanitizeSettings);
    }, [activeFieldType?.schemaIndex, shouldSanitizeSettings]);
    const fieldSchema = useMemo(() => {
        return sanitizeSubFieldSchema(rawFieldSchema, shouldSanitizeSettings);
    }, [rawFieldSchema, shouldSanitizeSettings]);
    const handleFieldOverrides = useMemo(() => {
        if (!isSyncedField) {
            return null;
        }

        return {
            disabled: true,
            readOnly: true,
            source: null,
            syncFromSource: false,
            instructions: Craft.t('formie', 'This handle is locked because the field is synced. Detach the field if you need an independent handle.'),
            warning: null,
        };
    }, [isSyncedField]);
    const schemaWithReservedHandles = useMemo(() => {
        return injectReservedHandlesIntoSchema(fieldSchema, reservedHandles, handleFieldOverrides);
    }, [fieldSchema, reservedHandles, handleFieldOverrides]);
    const schemaIndexWithReservedHandles = useMemo(() => {
        return injectReservedHandlesIntoSchemaIndex(sanitizedSchemaIndex, reservedHandles, handleFieldOverrides);
    }, [sanitizedSchemaIndex, reservedHandles, handleFieldOverrides]);
    const fallbackSchemaIndex = useMemo(() => {
        return {
            schema: schemaWithReservedHandles,
            fieldEntries: [],
        };
    }, [schemaWithReservedHandles]);
    const handleSyncOnChange = useHandleSyncOnChange(schemaWithReservedHandles);
    const form = useSchemaFormEngine({
        schema: schemaWithReservedHandles,
        schemaIndex: schemaIndexWithReservedHandles || fallbackSchemaIndex,
        defaultValues: field,
        onChange: (values, schemaForm) => {
            handleSyncOnChange(values, schemaForm);
        },
    });

    form.onSuccess((data) => {
        onSave(data);
    });

    useFieldEditorDismiss({
        field,
        fieldDisplayLabel,
        form,
        onDismiss,
        dismissAttemptRef,
        isBaselineReady: hasSchemaConfig,
    });

    useEffect(() => {
        if (hasAutofocusedRef.current) {
            return;
        }

        if (!hasSchemaConfig) {
            return;
        }

        hasAutofocusedRef.current = true;

        return focusFirstVisibleInputIfEmpty({
            root: contentRef.current,
        });
    }, [hasSchemaConfig]);

    const canDeleteFromModal = Boolean(field?.id);

    return (
        <DialogContent
            className="w-[66%] h-[66%] max-w-auto min-w-[600px] min-h-[400px]"
        >
            <DialogHeader>
                <DialogTitle className="flex flex-row items-center">
                    {Craft.t('formie', 'Edit Field')}

                    {showFieldTypePill && (
                        <div className="rounded-[20px] bg-[#d8e2ea] px-[10px] py-[6px] text-[10px] text-[#526176] ml-[10px] font-normal">
                            {resolvedFieldTypeLabel}
                        </div>
                    )}
                </DialogTitle>

                <DialogDescription className="hidden">
                    {Craft.t('formie', 'Edit the field for this form.')}
                </DialogDescription>
            </DialogHeader>

            <div ref={contentRef} className="flex-1 min-h-0 overflow-hidden">
                {hasSchemaConfig ? (
                    <SchemaFormEngine form={form} className="h-full" />
                ) : (
                    <div className="flex h-full items-center justify-center">
                        <div className="flex max-w-[640px] flex-col items-center gap-3 text-sm text-rose-600">
                            <div>{Craft.t('formie', 'Field settings are unavailable. Please reload the builder.')}</div>
                        </div>
                    </div>
                )}
            </div>

            <DialogFooter className={cn(
                'flex flex-row gap-2',
                canDeleteFromModal ? 'justify-between' : 'justify-end',
            )}
            >
                {canDeleteFromModal && (
                    <Button type="button" onClick={onDelete}>
                        {Craft.t('formie', 'Delete')}
                    </Button>
                )}

                <div className="flex flex-row justify-end gap-2">
                    <Button type="button" onClick={onCancel}>
                        {Craft.t('formie', 'Cancel')}
                    </Button>

                    <Button type="button" variant="primary" disabled={!hasSchemaConfig} onClick={(e) => {
                        e.preventDefault();
                        submitSchemaFormAfterPendingTableUpdates(form);
                    }}>
                        {Craft.t('formie', 'Apply')}
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    );
};

const NestedFieldCard = ({
    nestedField,
    nestedFieldType,
    parentMeta,
    parentRows,
    shouldSanitizeSettings = true,
}) => {
    const globalReservedHandles = useAppStore((state) => { return state.reservedHandles || []; });
    const { source } = useDragOperation();
    const isAnyDragActive = Boolean(source);
    const {
        pageIndex, rowIndex, fieldIndex, nestedRowIndex, nestedFieldIndex, isRepeatable, containerInstanceId,
    } = parentMeta;
    const {
        updateNestedField,
        deleteNestedField,
        moveNestedFieldWithinParent,
        addFieldBetweenNestedRows,
    } = useBuilderActions();
    const { errors: formErrors, hasErrorsForPrefix } = useFormBuilderForm();
    const [editing, setEditing] = useState(false);
    const [isDropdownOpen, setIsDropdownOpen] = useState(false);
    const nestedFieldEditorDismissAttemptRef = useRef(null);

    const {
        ref, handleRef, isDragging,
    } = useDraggable({
        id: `nested-draggable|${pageIndex}|${rowIndex}|${fieldIndex}|${nestedField?._id || nestedFieldIndex}|${containerInstanceId}`,
        data: {
            source: 'nested',
            pageIndex,
            rowIndex,
            fieldIndex,
            nestedRowIndex,
            nestedFieldIndex,
            isRepeatableParentField: isRepeatable,
            isContainerParentField: !isRepeatable,
            field: nestedField,
            fieldType: nestedFieldType,
            cursorNudge: { x: 1, y: 1 },
        },
        modifiers: [
            SnapTopLeftCornerToCursor,
        ],
    });

    useEffect(() => {
        if (nestedField?._isNew) {
            setEditing(true);
        }
    }, [nestedField?._isNew]);

    const previewContent = useMemo(() => {
        return renderFieldPreviewSchema(nestedFieldType?.preview, nestedField, nestedFieldType);
    }, [nestedFieldType?.preview, nestedFieldType?.icon, nestedField]);
    const shouldUseNestedFieldLabel = nestedFieldType?.hasLabel !== false;
    const nestedFieldDisplayLabel = shouldUseNestedFieldLabel
        ? (nestedField?.label || nestedFieldType?.label || Craft.t('formie', 'Field'))
        : (nestedFieldType?.label || Craft.t('formie', 'Field'));

    const closeNestedFieldEditor = ({ deleteIfNew = false } = {}) => {
        if (deleteIfNew && nestedField?._isNew) {
            deleteNestedField(pageIndex, rowIndex, fieldIndex, nestedRowIndex, nestedFieldIndex);
            announceFormBuilderStatus(Craft.t('formie', '{label} field deleted.', {
                label: nestedFieldDisplayLabel,
            }));
        }

        setEditing(false);
    };

    const hasConditions = useMemo(() => {
        if (!nestedField?.enableConditions) {
            return false;
        }

        const conditions = nestedField?.conditions?.conditions;

        if (!Array.isArray(conditions) || !conditions.length) {
            return false;
        }

        return conditions.some((condition) => {
            return Boolean(condition?.condition);
        });
    }, [nestedField?.enableConditions, nestedField?.conditions]);
    const nestedFieldPath = `pages.${pageIndex}.rows.${rowIndex}.fields.${fieldIndex}.rows.${nestedRowIndex}.fields.${nestedFieldIndex}`;
    const nestedFieldPrefix = `${nestedFieldPath}.`;
    const hasErrors = useMemo(() => {
        if (formErrors?.[nestedFieldPath]) {
            return true;
        }
        return hasErrorsForPrefix(nestedFieldPrefix);
    }, [formErrors, hasErrorsForPrefix, nestedFieldPath, nestedFieldPrefix]);
    const hasNestedFieldStatusIndicators = hasConditions;
    const currentRow = parentRows?.[nestedRowIndex];
    const prevRow = parentRows?.[nestedRowIndex - 1];
    const nextRow = parentRows?.[nestedRowIndex + 1];
    const hasRowSiblings = Boolean(currentRow && currentRow.fields.length > 1);
    const prevRowHasRoom = Boolean(prevRow && (prevRow.fields?.length || 0) < MAX_FIELDS_PER_ROW);
    const nextRowHasRoom = Boolean(nextRow && (nextRow.fields?.length || 0) < MAX_FIELDS_PER_ROW);
    const canMoveUp = hasRowSiblings || prevRowHasRoom;
    const canMoveDown = hasRowSiblings || nextRowHasRoom;
    const canMoveLeft = nestedFieldIndex > 0;
    const canMoveRight = Boolean(currentRow && nestedFieldIndex < currentRow.fields.length - 1);
    const isSyncedField = Boolean(nestedField?.isSynced || nestedField?.syncId);
    const nestedReservedHandles = useMemo(() => {
        const siblingHandles = collectNestedReservedHandles(parentRows, {
            rowIndex: nestedRowIndex,
            fieldIndex: nestedFieldIndex,
        });
        return [...new Set([...(globalReservedHandles || []), ...siblingHandles])];
    }, [parentRows, nestedRowIndex, nestedFieldIndex, globalReservedHandles]);

    const moveField = (toNestedRowIndex, toNestedFieldIndex, asNewRow) => {
        moveNestedFieldWithinParent(
            pageIndex,
            rowIndex,
            fieldIndex,
            nestedRowIndex,
            nestedFieldIndex,
            toNestedRowIndex,
            toNestedFieldIndex,
            asNewRow,
        );
    };
    const announceNestedFieldMove = (targetNestedRowIndex, targetNestedFieldIndex) => {
        announceFormBuilderStatus(Craft.t('formie', '{label} field is now row {row}, column {column}.', {
            label: nestedFieldDisplayLabel,
            row: targetNestedRowIndex + 1,
            column: targetNestedFieldIndex + 1,
        }));

        focusFieldActionsTrigger(nestedField?._id);
    };

    const duplicateField = () => {
        const existingHandles = [];
        collectFieldHandlesFromRows(parentRows || [], existingHandles);

        const duplicatedField = buildDuplicatedFieldData(nestedField, existingHandles, {
            fieldType: nestedFieldType,
        });

        addFieldBetweenNestedRows(pageIndex, rowIndex, fieldIndex, nestedRowIndex, {
            ...duplicatedField,
        });
        announceFormBuilderStatus(Craft.t('formie', '{label} field duplicated.', {
            label: nestedFieldDisplayLabel,
        }));
    };

    return (
        <>
            <div
                ref={ref}
                data-id={nestedField?.id ?? undefined}
                className={cn(
                    'flex-1 min-w-0 max-w-full',
                    'outline-none',
                    'p-3',
                    'rounded-lg',
                    'text-[13px]',
                    'text-[#33475b]',
                    'transition-colors duration-200',
                    !isAnyDragActive && 'hover:bg-[#f1f5f8]',
                    'relative group cursor-pointer',
                    hasErrors ? 'bg-[#fff6f6] shadow-[0_0_1px_1px_#ef9898]' : '',
                    isDragging && 'opacity-40 !cursor-default',
                )}
            >
                <button
                    ref={handleRef}
                    type="button"
                    className="absolute inset-0 z-1 cursor-pointer rounded-lg border-0 bg-transparent p-0 focus-visible:outline-none focus-visible:shadow-[inset_0_0_0_2px_var(--color-sky-600),inset_0_0_0_4px_#ffffff]"
                    aria-label={Craft.t('formie', 'Edit {label}', { label: nestedFieldDisplayLabel })}
                    onClick={() => {
                        setEditing(true);
                    }}
                />

                <div className={cn(
                    'absolute top-1 right-1',
                    'form-builder-field-actions',
                    !isAnyDragActive && 'opacity-0 group-hover:opacity-100',
                    isAnyDragActive && 'opacity-0',
                    'transition-opacity',
                    'z-10',
                    isDropdownOpen && 'opacity-100',
                )}>
                    <DropdownMenu size="sm" open={isDropdownOpen} onOpenChange={setIsDropdownOpen}>
                        <DropdownMenuTrigger
                            render={(
                                <Button
                                    variant="transparent"
                                    size="sm"
                                    className="w-7 h-7 p-0 rounded-lg"
                                    data-form-builder-field-actions-trigger={nestedField?._id}
                                    aria-label={Craft.t('formie', 'Actions for {label}', { label: nestedFieldDisplayLabel })}
                                    onClick={(event) => {
                                        event.stopPropagation();
                                    }}
                                />
                            )}
                        >
                            <FontAwesomeIcon icon={faEllipsis} className="size-3.5" />
                        </DropdownMenuTrigger>

                        <DropdownMenuContent align="end" className="min-w-[140px]">
                            <DropdownMenuItem onClick={(event) => {
                                event.preventDefault();
                                event.stopPropagation();
                                setEditing(true);
                                setIsDropdownOpen(false);
                            }}>
                                <FontAwesomeIcon icon={faPencil} />
                                {Craft.t('formie', 'Edit')}
                            </DropdownMenuItem>

                            <DropdownMenuItem onClick={(event) => {
                                event.preventDefault();
                                event.stopPropagation();

                                updateNestedField(pageIndex, rowIndex, fieldIndex, nestedRowIndex, nestedFieldIndex, {
                                    required: !nestedField?.required,
                                });

                                setIsDropdownOpen(false);
                            }}>
                                <FontAwesomeIcon icon={faAsterisk} />
                                {nestedField?.required
                                    ? Craft.t('formie', 'Make optional')
                                    : Craft.t('formie', 'Make required')}
                            </DropdownMenuItem>

                            <DropdownMenuItem onClick={(event) => {
                                event.preventDefault();
                                event.stopPropagation();

                                duplicateField();
                                setIsDropdownOpen(false);
                            }}>
                                <FontAwesomeIcon icon={faClone} />
                                {Craft.t('formie', 'Duplicate')}
                            </DropdownMenuItem>

                            {isSyncedField && (
                                <DropdownMenuItem onClick={(event) => {
                                    event.preventDefault();
                                    event.stopPropagation();

                                    const confirmationMessage = Craft.t('formie', 'Are you sure you want to detach "{name}"? This field will become independent and future changes will no longer sync.', {
                                        name: nestedFieldDisplayLabel,
                                    });

                                    if (!window.confirm(confirmationMessage)) {
                                        return;
                                    }

                                    updateNestedField(
                                        pageIndex,
                                        rowIndex,
                                        fieldIndex,
                                        nestedRowIndex,
                                        nestedFieldIndex,
                                        detachSyncedFieldData(nestedField),
                                    );

                                    setIsDropdownOpen(false);
                                }}>
                                    <FontAwesomeIcon icon={faLinkSlash} />
                                    {Craft.t('formie', 'Detach sync')}
                                </DropdownMenuItem>
                            )}

                            <DropdownMenuSeparator />

                            <DropdownMenuItem
                                disabled={!canMoveUp}
                                className={cn(!canMoveUp && 'opacity-50 pointer-events-none')}
                                onClick={(event) => {
                                    event.preventDefault();
                                    event.stopPropagation();
                                    if (!canMoveUp) {
                                        return;
                                    }

                                    if (hasRowSiblings) {
                                        moveField(nestedRowIndex - 1, -1, true);
                                        announceNestedFieldMove(nestedRowIndex, 0);
                                    } else if (prevRow) {
                                        const targetIndex = Math.max((prevRow.fields?.length || 0) - 1, -1);
                                        moveField(nestedRowIndex - 1, targetIndex, false);
                                        announceNestedFieldMove(nestedRowIndex - 1, targetIndex + 1);
                                    }

                                    setIsDropdownOpen(false);
                                }}
                            >
                                <FontAwesomeIcon icon={faArrowUp} />
                                {Craft.t('formie', 'Move up')}
                            </DropdownMenuItem>

                            <DropdownMenuItem
                                disabled={!canMoveDown}
                                className={cn(!canMoveDown && 'opacity-50 pointer-events-none')}
                                onClick={(event) => {
                                    event.preventDefault();
                                    event.stopPropagation();
                                    if (!canMoveDown) {
                                        return;
                                    }

                                    if (hasRowSiblings) {
                                        moveField(nestedRowIndex, -1, true);
                                        announceNestedFieldMove(nestedRowIndex + 1, 0);
                                    } else if (nextRow) {
                                        const targetIndex = Math.max((nextRow.fields?.length || 0) - 1, -1);
                                        moveField(nestedRowIndex + 1, targetIndex, false);
                                        announceNestedFieldMove(nestedRowIndex, targetIndex + 1);
                                    }

                                    setIsDropdownOpen(false);
                                }}
                            >
                                <FontAwesomeIcon icon={faArrowDown} />
                                {Craft.t('formie', 'Move down')}
                            </DropdownMenuItem>

                            <DropdownMenuItem
                                disabled={!canMoveLeft}
                                className={cn(!canMoveLeft && 'opacity-50 pointer-events-none')}
                                onClick={(event) => {
                                    event.preventDefault();
                                    event.stopPropagation();
                                    if (!canMoveLeft) {
                                        return;
                                    }

                                    moveField(nestedRowIndex, nestedFieldIndex - 2, false);
                                    announceNestedFieldMove(nestedRowIndex, nestedFieldIndex - 1);
                                    setIsDropdownOpen(false);
                                }}
                            >
                                <FontAwesomeIcon icon={faArrowLeft} />
                                {Craft.t('formie', 'Move left')}
                            </DropdownMenuItem>

                            <DropdownMenuItem
                                disabled={!canMoveRight}
                                className={cn(!canMoveRight && 'opacity-50 pointer-events-none')}
                                onClick={(event) => {
                                    event.preventDefault();
                                    event.stopPropagation();
                                    if (!canMoveRight) {
                                        return;
                                    }

                                    moveField(nestedRowIndex, nestedFieldIndex + 1, false);
                                    announceNestedFieldMove(nestedRowIndex, nestedFieldIndex + 1);
                                    setIsDropdownOpen(false);
                                }}
                            >
                                <FontAwesomeIcon icon={faArrowRight} />
                                {Craft.t('formie', 'Move right')}
                            </DropdownMenuItem>

                            <DropdownMenuSeparator />

                            <DropdownMenuItem
                                className="text-error focus:text-error"
                                onClick={(event) => {
                                    event.preventDefault();
                                    event.stopPropagation();

                                    const confirmationMessage = Craft.t('formie', 'Are you sure you want to delete "{name}"?', {
                                        name: nestedFieldDisplayLabel,
                                    });

                                    if (!window.confirm(confirmationMessage)) {
                                        return;
                                    }

                                    deleteNestedField(pageIndex, rowIndex, fieldIndex, nestedRowIndex, nestedFieldIndex);
                                    announceFormBuilderStatus(Craft.t('formie', '{label} field deleted.', {
                                        label: nestedFieldDisplayLabel,
                                    }));
                                    setIsDropdownOpen(false);
                                }}
                            >
                                <FontAwesomeIcon icon={faTrash} />
                                {Craft.t('formie', 'Delete')}
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>

                <div className="pointer-events-none select-none space-y-2">
                    {(shouldUseNestedFieldLabel || nestedField?.instructions || hasNestedFieldStatusIndicators) && (
                        <div className="space-y-1 leading-none pr-8">
                            {(shouldUseNestedFieldLabel || hasNestedFieldStatusIndicators) && (
                                <div className="font-medium flex items-center gap-1 min-w-0">
                                    {shouldUseNestedFieldLabel && (
                                        <>
                                            <span
                                                className="truncate"
                                                title={nestedFieldDisplayLabel}
                                            >
                                                {nestedFieldDisplayLabel}
                                            </span>

                                            {nestedField?.required && (
                                                <span className="text-error">*</span>
                                            )}
                                        </>
                                    )}

                                    {hasConditions && (
                                        <div className={cn(
                                            'inline-flex items-center gap-1',
                                            'rounded-[10px] border border-[#0ea5e9] bg-[#f0faff]',
                                            'px-[6px] py-[3px]',
                                            'text-[10px] font-medium text-[#0077b6]',
                                            shouldUseNestedFieldLabel && 'ml-2',
                                        )}>
                                            <FontAwesomeIcon icon={faEye} className="size-3" />
                                            <span>{Craft.t('formie', 'Conditions')}</span>
                                        </div>
                                    )}
                                </div>
                            )}

                        {nestedField?.instructions && (
                            <div className="text-gray-500 text-[12px]">
                                {nestedField.instructions}
                            </div>
                        )}
                        </div>
                    )}

                    {previewContent}
                </div>
            </div>

            {editing && (
                <Dialog open={true} onOpenChange={(open) => {
                    if (!open) {
                        nestedFieldEditorDismissAttemptRef.current?.();
                    }
                }}>
                    <NestedFieldEditModal
                        field={nestedField}
                        fieldType={nestedFieldType}
                        reservedHandles={nestedReservedHandles}
                        shouldSanitizeSettings={shouldSanitizeSettings}
                        dismissAttemptRef={nestedFieldEditorDismissAttemptRef}
                        onSave={(updatedField) => {
                            updateNestedField(pageIndex, rowIndex, fieldIndex, nestedRowIndex, nestedFieldIndex, {
                                ...updatedField,
                                handle: isSyncedField ? nestedField.handle : updatedField.handle,
                                _isNew: false,
                            });
                            setEditing(false);
                        }}
                        onCancel={() => {
                            closeNestedFieldEditor({ deleteIfNew: true });
                        }}
                        onDismiss={({ deleteIfNew = false } = {}) => {
                            closeNestedFieldEditor({ deleteIfNew });
                        }}
                        onDelete={() => {
                            const confirmationMessage = Craft.t('formie', 'Are you sure you want to delete "{name}"?', {
                                name: nestedFieldDisplayLabel,
                            });

                            if (!window.confirm(confirmationMessage)) {
                                return;
                            }

                            deleteNestedField(pageIndex, rowIndex, fieldIndex, nestedRowIndex, nestedFieldIndex);
                            announceFormBuilderStatus(Craft.t('formie', '{label} field deleted.', {
                                label: nestedFieldDisplayLabel,
                            }));
                            setEditing(false);
                        }}
                    />
                </Dialog>
            )}
        </>
    );
};

const ContainerFieldPreview = ({
    field, fieldType, pageIndex, rowIndex, fieldIndex,
}) => {
    const instanceIdRef = useRef(`inst-${Math.random().toString(36).slice(2, 10)}`);
    const instanceId = instanceIdRef.current;
    const { getFieldTypeByType } = useFormBuilderApp();
    const builderDevSettings = useMemo(() => {
        if (!import.meta.env.DEV) {
            return null;
        }

        return getDevToolsConfig();
    }, []);
    const showExpandedDropzoneHitboxes = Boolean(
        builderDevSettings?.enabled && builderDevSettings?.showExpandedDropzoneHitboxes,
    );
    const showStructureIds = Boolean(
        builderDevSettings?.enabled && builderDevSettings?.showRowAndFieldIds,
    );
    const rows = resolveContainerRows(field, fieldType);
    const { source } = useDragOperation();
    const activeData = source?.data?.current ?? source?.data;
    const isDragging = Boolean(activeData);
    const isRepeater = Boolean(fieldType?.isRepeatableParentField);
    const shouldSanitizeNestedFieldSettings = Boolean(fieldType?.isFixedParentField);
    const allowedNestedFieldTypes = Array.isArray(fieldType?.data?.nestedLayoutBuilder?.allowedFieldTypes)
        ? fieldType.data.nestedLayoutBuilder.allowedFieldTypes
        : [];
    const canDropInThisContainer = canDropInNestedContainer({
        activeData,
        isRepeater,
        pageIndex,
        rowIndex,
        fieldIndex,
        allowedFieldTypes: allowedNestedFieldTypes,
    });
    const dropzonesDisabled = (!isDragging && !showExpandedDropzoneHitboxes) || !canDropInThisContainer;
    const showDropzones = (isDragging && canDropInThisContainer) || showExpandedDropzoneHitboxes;

    if (rows.length === 0) {
        const emptyDropzoneId = `nested-dropzone|${instanceId}|empty|${pageIndex}|${rowIndex}|${fieldIndex}`;

        return (
            <div className="mt-1">
                <NestedDropzone
                    key={emptyDropzoneId}
                    id={emptyDropzoneId}
                    data={{
                        targetType: 'nested-empty',
                        pageIndex,
                        rowIndex,
                        fieldIndex,
                        nestedRowIndex: -1,
                        nestedFieldIndex: -1,
                        containerFieldId: field?._id || null,
                    }}
                    kind="empty"
                    disabled={dropzonesDisabled}
                    alwaysVisible={!isDragging || canDropInThisContainer}
                />
            </div>
        );
    }

    return (
        <div className="mt-1 border border-gray-100 bg-white rounded-md p-2">
            {rows.map((row, nestedRowIndex) => {
                const nestedFields = Array.isArray(row?.fields) ? row.fields : [];
                const isRowFull = nestedFields.length >= MAX_FIELDS_PER_ROW;
                const nestedRowIdentity = row?._id || `row-${nestedRowIndex}`;
                const firstNestedFieldIdentity = nestedFields[0]?._id || 'none';
                const beforeRowDropzoneId = `nested-dropzone|${instanceId}|row-before|r${nestedRowIndex}|${nestedRowIdentity}`;
                const beforeFieldDropzoneId = `nested-dropzone|${instanceId}|field-before|r${nestedRowIndex}|${nestedRowIdentity}|f-1|${firstNestedFieldIdentity}`;
                const afterRowDropzoneId = `nested-dropzone|${instanceId}|row-after|r${nestedRowIndex}|${nestedRowIdentity}`;

                return (
                    <React.Fragment key={row?._id || nestedRowIndex}>
                        {nestedRowIndex === 0 && (
                            <NestedDropzone
                                key={beforeRowDropzoneId}
                                id={beforeRowDropzoneId}
                                data={{
                                    targetType: 'nested-row',
                                    pageIndex,
                                    rowIndex,
                                    fieldIndex,
                                    nestedRowIndex: -1,
                                    nestedFieldIndex: -1,
                                    containerFieldId: field?._id || null,
                                }}
                                kind="row"
                                disabled={dropzonesDisabled}
                                alwaysVisible={showDropzones}
                            />
                        )}

                        <div className="flex items-stretch min-w-0 relative" data-row-id={row?._id}>
                            {showStructureIds && (
                                <div className="pointer-events-none absolute left-2 top-1 z-20 rounded bg-slate-900/75 px-1.5 py-0.5 text-[10px] leading-none text-white">
                                    {`row:${row?._id || nestedRowIndex}`}
                                </div>
                            )}

                            <NestedDropzone
                                key={beforeFieldDropzoneId}
                                id={beforeFieldDropzoneId}
                                data={{
                                    targetType: 'nested-field',
                                    pageIndex,
                                    rowIndex,
                                    fieldIndex,
                                    nestedRowIndex,
                                    nestedFieldIndex: -1,
                                    containerFieldId: field?._id || null,
                                }}
                                kind="field"
                                disabled={dropzonesDisabled || isRowFull}
                                alwaysVisible={showDropzones}
                            />

                            {nestedFields.map((nestedField, nestedFieldIndex) => {
                                const nestedFieldType = getFieldTypeByType(nestedField?.type) || {};
                                const nestedFieldIdentity = nestedField?._id || `field-${nestedFieldIndex}`;

                                return (
                                    <React.Fragment key={nestedField?._id || nestedFieldIndex}>
                                        <div
                                            className="relative flex-1 min-w-0"
                                            data-id={nestedField?.id ?? undefined}
                                            data-field-id={nestedField?._id}
                                        >
                                            {showStructureIds && (
                                                <div className="pointer-events-none absolute right-2 top-1 z-20 rounded bg-indigo-700/80 px-1.5 py-0.5 text-[10px] leading-none text-white">
                                                    {`field:${nestedField?._id || nestedFieldIndex}`}
                                                </div>
                                            )}

                                            <NestedFieldCard
                                                nestedField={nestedField}
                                                nestedFieldType={nestedFieldType}
                                                parentMeta={{
                                                    pageIndex,
                                                    rowIndex,
                                                    fieldIndex,
                                                    nestedRowIndex,
                                                    nestedFieldIndex,
                                                    isRepeatable: isRepeater,
                                                    containerInstanceId: instanceId,
                                                }}
                                                parentRows={rows}
                                                shouldSanitizeSettings={shouldSanitizeNestedFieldSettings}
                                            />
                                        </div>

                                        <NestedDropzone
                                            key={`nested-dropzone|${instanceId}|field-after|r${nestedRowIndex}|${nestedRowIdentity}|f${nestedFieldIndex}|${nestedFieldIdentity}`}
                                            id={`nested-dropzone|${instanceId}|field-after|r${nestedRowIndex}|${nestedRowIdentity}|f${nestedFieldIndex}|${nestedFieldIdentity}`}
                                            data={{
                                                targetType: 'nested-field',
                                                pageIndex,
                                                rowIndex,
                                                fieldIndex,
                                                nestedRowIndex,
                                                nestedFieldIndex,
                                                containerFieldId: field?._id || null,
                                            }}
                                            kind="field"
                                            disabled={dropzonesDisabled || isRowFull}
                                            alwaysVisible={showDropzones}
                                        />
                                    </React.Fragment>
                                );
                            })}
                        </div>

                        {nestedRowIndex < rows.length - 1 && (
                            <NestedDropzone
                                key={afterRowDropzoneId}
                                id={afterRowDropzoneId}
                                data={{
                                    targetType: 'nested-row',
                                    pageIndex,
                                    rowIndex,
                                    fieldIndex,
                                    nestedRowIndex,
                                    nestedFieldIndex: -1,
                                    containerFieldId: field?._id || null,
                                }}
                                kind="row"
                                disabled={dropzonesDisabled}
                                alwaysVisible={showDropzones}
                            />
                        )}

                        {nestedRowIndex === rows.length - 1 && (
                            <NestedDropzone
                                key={afterRowDropzoneId}
                                id={afterRowDropzoneId}
                                data={{
                                    targetType: 'nested-row',
                                    pageIndex,
                                    rowIndex,
                                    fieldIndex,
                                    nestedRowIndex,
                                    nestedFieldIndex: -1,
                                    containerFieldId: field?._id || null,
                                }}
                                kind="row"
                                disabled={dropzonesDisabled}
                                alwaysVisible={showDropzones}
                            />
                        )}
                    </React.Fragment>
                );
            })}

            {isRepeater && (
                <div className={cn([
                    'flex items-center justify-center text-center',
                    'rounded-md border border-dashed border-gray-200 text-gray-500 bg-white',
                    'gap-2 px-3 py-2 m-3',
                ])}>
                    <FontAwesomeIcon icon={faPlusSquare} className="size-3.5" />
                    {field?.addLabel || field?.settings?.addLabel || Craft.t('formie', 'Add another row')}
                </div>
            )}
        </div>
    );
};

export { ContainerFieldPreview };
