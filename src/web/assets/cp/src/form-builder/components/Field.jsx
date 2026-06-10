import React, {
    useState, useEffect, useMemo, useRef,
} from 'react';
import { useDraggable, useDragOperation } from '@dnd-kit/react';

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faClone, faXmark, faAsterisk, faRefresh, faEyeSlash,
    faPencil,
    faEllipsis,
    faEye,
    faTriangleExclamation,
    faArrowUp,
    faArrowDown,
    faArrowLeft,
    faArrowRight,
    faLinkSlash,
} from '@fortawesome/pro-solid-svg-icons';

import {
    Button,
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogDescription,
    Spinner,
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@verbb/plugin-kit-react/components';
import {
    Combobox,
    ComboboxPrimitiveInput,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxList,
    ComboboxItem,
    ComboboxHighlightedText,
} from '@verbb/plugin-kit-react/components/Combobox';

import { SchemaFormEngine, useSchemaFormEngine } from '@verbb/plugin-kit-react/forms';
import { useHandleSyncOnChange } from '@form-builder/hooks/useHandleSyncOnChange';
import { useFormBuilderForm } from '@form-builder/contexts/FormBuilderFormContext';
import { useFormBuilderApp } from '@form-builder/contexts/FormBuilderAppContext';
import useAppStore from '@form-builder/hooks/useAppStore';
import { useFormValues } from '@form-builder/hooks/useFormTools';
import { useBuilderActions } from '@form-builder/builder/useBuilderActions';
import { MAX_FIELDS_PER_ROW } from '@form-builder/builder/constants';
import { getDevToolsConfig } from '@form-builder/dev/config';
import { isPaymentField } from '@form-builder/utils/paymentSubmission';
import {
    collectFieldHandlesFromRows,
    buildDuplicatedFieldData,
    detachSyncedFieldData,
    prepareNewFieldForInsert,
} from '@form-builder/utils/duplicateField';
import {
    injectReservedHandlesIntoSchema,
    injectReservedHandlesIntoSchemaIndex,
    collectTopLevelReservedHandles,
} from '@form-builder/utils/handleValidation';
import {
    cn,
} from '@verbb/plugin-kit-react/utils';
import { focusFirstVisibleInputIfEmpty } from '@form-builder/utils/focus';
import { syncContainerRowsFromVariant } from '@form-builder/utils/containerLayoutVariants';
import { announceFormBuilderStatus, focusFieldActionsTrigger } from '@form-builder/utils/accessibility';
import { submitSchemaFormAfterPendingTableUpdates } from '@form-builder/utils/submitSchemaForm';
import { useFieldEditorDismiss } from '@form-builder/hooks/useFieldEditorDismiss';
import { SnapTopLeftCornerToCursor } from '@utils';

import { FieldPreview } from './FieldPreview';

const resolveCustomFieldAdapterDefinition = (adapters, adapterValue) => {
    return adapters.find((item) => {
        return item.type === adapterValue || item.handle === adapterValue;
    }) || null;
};

const resolveCustomFieldAdapterType = (adapters, adapterValue) => {
    const adapter = resolveCustomFieldAdapterDefinition(adapters, adapterValue);

    return adapter?.type || null;
};

const Field = ({
    field, pageIndex, rowIndex, fieldIndex,
}) => {
    const { getFieldTypeByType } = useFormBuilderApp();
    const globalReservedHandles = useAppStore((state) => { return state.reservedHandles || []; });
    const {
        updateField,
        deleteField,
        addFieldBetweenRows,
        moveFieldToPosition,
        moveFieldToNewRow,
    } = useBuilderActions();
    const formValues = useFormValues();
    const pages = formValues?.pages || [];
    const { errors: formErrors, hasErrorsForPrefix } = useFormBuilderForm();

    const fieldType = getFieldTypeByType(field.type);
    const isBuilderField = Boolean(fieldType?.isBuilderField);
    const shouldUseFieldLabel = !isBuilderField && fieldType?.hasLabel !== false;
    const fieldDisplayLabel = shouldUseFieldLabel
        ? (field?.label || fieldType?.label || Craft.t('formie', 'Field'))
        : (fieldType?.label || Craft.t('formie', 'Field'));
    const isInlineContainerBuilder = Boolean(fieldType?.isContainerParentField || fieldType?.isRepeatableParentField);
    const draggableFieldId = field?._id || `${pageIndex}-${rowIndex}-${fieldIndex}`;
    const [editingField, setEditingField] = useState(null);
    const [isAdapterPickerOpen, setIsAdapterPickerOpen] = useState(false);
    const [isDropdownOpen, setIsDropdownOpen] = useState(false);
    const hasInitialAutoOpenRunRef = useRef(false);
    const isClosingEditorRef = useRef(false);
    const fieldEditorDismissAttemptRef = useRef(null);
    const builderDevSettings = useMemo(() => {
        if (!import.meta.env.DEV) {
            return null;
        }

        return getDevToolsConfig();
    }, []);
    const shouldAutoOpenInitialFieldEditor = Boolean(builderDevSettings?.enabled && builderDevSettings?.autoOpenFirstField);

    const fieldPath = `pages.${pageIndex}.rows.${rowIndex}.fields.${fieldIndex}`;
    const fieldPrefix = `${fieldPath}.`;
    const hasConditions = useMemo(() => {
        if (!field?.enableConditions) {
            return false;
        }

        const conditions = field?.conditions?.conditions;

        if (!Array.isArray(conditions) || !conditions.length) {
            return false;
        }

        return conditions.some((condition) => {
            return Boolean(condition?.condition);
        });
    }, [field?.enableConditions, field?.conditions]);
    const showPaymentPlacementWarning = useMemo(() => {
        if (!isPaymentField(field)) {
            return false;
        }

        // Non-blocking builder warning: payment fields should be on the final page.
        return pages.length > 1 && pageIndex !== pages.length - 1;
    }, [field, pages.length, pageIndex]);
    const isSyncedField = useMemo(() => {
        return Boolean(field?.isSynced || field?.syncId);
    }, [field?.isSynced, field?.syncId]);
    const hasFieldStatusIndicators = isSyncedField
        || hasConditions
        || showPaymentPlacementWarning
        || field.enabled === false
        || field.visibility === 'disabled'
        || field.visibility === 'hidden';
    const hasErrors = useMemo(() => {
        if (formErrors?.[fieldPath]) {
            return true;
        }
        return hasErrorsForPrefix(fieldPrefix);
    }, [formErrors, hasErrorsForPrefix, fieldPath, fieldPrefix]);

    const customFieldAdapters = Array.isArray(fieldType?.data?.customFieldAdapters)
        ? fieldType.data.customFieldAdapters
        : [];
    const resolvedCustomFieldAdapterDefinition = resolveCustomFieldAdapterDefinition(customFieldAdapters, field?.customFieldAdapter);
    const resolvedCustomFieldAdapterType = resolvedCustomFieldAdapterDefinition?.type || null;
    const needsCustomFieldAdapter = customFieldAdapters.length > 0 && !resolvedCustomFieldAdapterType;

    useEffect(() => {
        if (!resolvedCustomFieldAdapterDefinition) {
            return;
        }

        if (field?.customFieldAdapter === resolvedCustomFieldAdapterDefinition.type && field?.customFieldAdapterHandle === resolvedCustomFieldAdapterDefinition.handle) {
            return;
        }

        updateField(pageIndex, rowIndex, fieldIndex, {
            ...field,
            customFieldAdapter: resolvedCustomFieldAdapterDefinition.type,
            customFieldAdapterHandle: resolvedCustomFieldAdapterDefinition.handle,
        });
    }, [resolvedCustomFieldAdapterDefinition, field?.customFieldAdapter, field?.customFieldAdapterHandle, pageIndex, rowIndex, fieldIndex]);

    const openFieldEditor = (targetField = field) => {
        const targetAdapterDefinition = resolveCustomFieldAdapterDefinition(customFieldAdapters, targetField?.customFieldAdapter);
        const targetAdapterType = targetAdapterDefinition?.type || null;

        if (customFieldAdapters.length > 0 && !targetAdapterType) {
            setIsAdapterPickerOpen(true);
            return;
        }

        const normalizedTargetField = targetAdapterType && targetField.customFieldAdapter !== targetAdapterType
            ? {
                ...targetField,
                customFieldAdapter: targetAdapterType,
                customFieldAdapterHandle: targetAdapterDefinition.handle,
            }
            : targetField;

        if (normalizedTargetField !== targetField) {
            updateField(pageIndex, rowIndex, fieldIndex, normalizedTargetField);
        }

        setEditingField(normalizedTargetField);
    };

    // Auto-open edit modal for new fields
    useEffect(() => {
        if (field._isNew) {
            openFieldEditor(field);
        }
    }, [field._isNew]);

    useEffect(() => {
        if (editingField) {
            isClosingEditorRef.current = false;
        }
    }, [editingField]);

    useEffect(() => {
        if (!shouldAutoOpenInitialFieldEditor || hasInitialAutoOpenRunRef.current) {
            return;
        }

        if (pageIndex === 0 && rowIndex === 0 && fieldIndex === 0) {
            openFieldEditor(field);
            hasInitialAutoOpenRunRef.current = true;
        }
    }, [shouldAutoOpenInitialFieldEditor, field, pageIndex, rowIndex, fieldIndex]);

    const {
        ref, handleRef, isDragging: isFieldDragging,
    } = useDraggable({
        id: `draggable-field-${draggableFieldId}`,
        data: {
            source: 'top-level',
            field,
            fieldType,
            pageIndex,
            rowIndex,
            fieldIndex,
        },
        modifiers: [
            SnapTopLeftCornerToCursor,
        ],
    });
    const { source } = useDragOperation();
    const isAnyDragActive = Boolean(source);

    const handleEdit = (e) => {
        e.preventDefault();
        e.stopPropagation();
        openFieldEditor(field);
        setIsDropdownOpen(false);
    };

    const handleDuplicate = (e) => {
        e.preventDefault();
        e.stopPropagation();

        const existingHandles = [];

        pages.forEach((page) => {
            collectFieldHandlesFromRows(page?.rows || [], existingHandles);
        });

        const duplicatedField = buildDuplicatedFieldData(field, existingHandles, { fieldType });

        // Use addFieldBetweenRows to create a new row with just this field
        addFieldBetweenRows(pageIndex, rowIndex, duplicatedField);
        announceFormBuilderStatus(Craft.t('formie', '{label} field duplicated.', {
            label: fieldDisplayLabel,
        }));
        setIsDropdownOpen(false);
    };

    const topLevelReservedHandles = useMemo(() => {
        const siblingHandles = collectTopLevelReservedHandles(pages, {
            pageIndex,
            rowIndex,
            fieldIndex,
        });
        return [...new Set([...(globalReservedHandles || []), ...siblingHandles])];
    }, [pages, pageIndex, rowIndex, fieldIndex, globalReservedHandles]);

    const currentPage = pages[pageIndex];
    const currentRow = currentPage?.rows?.[rowIndex];
    const prevRow = currentPage?.rows?.[rowIndex - 1];
    const nextRow = currentPage?.rows?.[rowIndex + 1];
    const hasRowSiblings = Boolean(currentRow && currentRow.fields.length > 1);
    const prevRowHasRoom = Boolean(prevRow && (prevRow.fields?.length || 0) < MAX_FIELDS_PER_ROW);
    const nextRowHasRoom = Boolean(nextRow && (nextRow.fields?.length || 0) < MAX_FIELDS_PER_ROW);
    const canMoveUp = hasRowSiblings || prevRowHasRoom;
    const canMoveDown = hasRowSiblings || nextRowHasRoom;
    const canMoveLeft = fieldIndex > 0;
    const canMoveRight = Boolean(currentRow && fieldIndex < currentRow.fields.length - 1);
    const announceFieldMove = (targetRowIndex, targetFieldIndex) => {
        announceFormBuilderStatus(Craft.t('formie', '{label} field is now row {row}, column {column}.', {
            label: fieldDisplayLabel,
            row: targetRowIndex + 1,
            column: targetFieldIndex + 1,
        }));

        focusFieldActionsTrigger(field?._id);
    };

    const handleMoveUp = (e) => {
        e.preventDefault();
        e.stopPropagation();

        if (!canMoveUp) {
            return;
        }

        if (hasRowSiblings) {
            moveFieldToNewRow(pageIndex, rowIndex, fieldIndex, pageIndex, rowIndex - 1);
            announceFieldMove(rowIndex, 0);
            setIsDropdownOpen(false);
            return;
        }

        if (prevRow) {
            const targetIndex = Math.max((prevRow.fields?.length || 0) - 1, -1);
            moveFieldToPosition(pageIndex, rowIndex, fieldIndex, pageIndex, rowIndex - 1, targetIndex);
            announceFieldMove(rowIndex - 1, targetIndex + 1);
            setIsDropdownOpen(false);
            return;
        }

        setIsDropdownOpen(false);
    };

    const handleMoveDown = (e) => {
        e.preventDefault();
        e.stopPropagation();

        if (!canMoveDown) {
            return;
        }

        if (hasRowSiblings) {
            moveFieldToNewRow(pageIndex, rowIndex, fieldIndex, pageIndex, rowIndex);
            announceFieldMove(rowIndex + 1, 0);
            setIsDropdownOpen(false);
            return;
        }

        if (nextRow) {
            const targetIndex = Math.max((nextRow.fields?.length || 0) - 1, -1);
            moveFieldToPosition(pageIndex, rowIndex, fieldIndex, pageIndex, rowIndex + 1, targetIndex);
            announceFieldMove(rowIndex, targetIndex + 1);
            setIsDropdownOpen(false);
            return;
        }

        setIsDropdownOpen(false);
    };

    const handleMoveLeft = (e) => {
        e.preventDefault();
        e.stopPropagation();

        if (!canMoveLeft) {
            return;
        }

        moveFieldToPosition(pageIndex, rowIndex, fieldIndex, pageIndex, rowIndex, fieldIndex - 2);
        announceFieldMove(rowIndex, fieldIndex - 1);
        setIsDropdownOpen(false);
    };

    const handleMoveRight = (e) => {
        e.preventDefault();
        e.stopPropagation();

        if (!canMoveRight) {
            return;
        }

        moveFieldToPosition(pageIndex, rowIndex, fieldIndex, pageIndex, rowIndex, fieldIndex + 1);
        announceFieldMove(rowIndex, fieldIndex + 1);
        setIsDropdownOpen(false);
    };

    const handleToggleRequired = (e) => {
        e.preventDefault();
        e.stopPropagation();

        updateField(pageIndex, rowIndex, fieldIndex, {
            ...field,
            required: !field.required,
        });

        setIsDropdownOpen(false);
    };

    const handleDetachSync = (e) => {
        e.preventDefault();
        e.stopPropagation();

        const confirmationMessage = Craft.t('formie', 'Are you sure you want to detach "{name}"? This field will become independent and future changes will no longer sync.', {
            name: fieldDisplayLabel,
        });

        if (!window.confirm(confirmationMessage)) {
            return;
        }

        updateField(pageIndex, rowIndex, fieldIndex, detachSyncedFieldData(field));
        setIsDropdownOpen(false);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        e.stopPropagation();

        const confirmationMessage = Craft.t('formie', 'Are you sure you want to delete "{name}"?', {
            name: fieldDisplayLabel,
        });

        const isConfirmed = window.confirm(confirmationMessage);

        if (!isConfirmed) {
            return;
        }

        deleteField(pageIndex, rowIndex, fieldIndex);
        announceFormBuilderStatus(Craft.t('formie', '{label} field deleted.', {
            label: fieldDisplayLabel,
        }));
        setIsDropdownOpen(false);
    };

    const handleSaveField = (updatedField) => {
        const nextField = syncContainerRowsFromVariant({
            ...field,
            ...updatedField,
            handle: isSyncedField ? field.handle : (updatedField.handle ?? field.handle),
            label: updatedField.label ?? field.label,
            // Field is no longer new after first save
            _isNew: false,
        }, fieldType);

        isClosingEditorRef.current = true;
        updateField(pageIndex, rowIndex, fieldIndex, nextField);
        setEditingField(null);
        setIsAdapterPickerOpen(false);
    };

    const closeFieldEditor = ({ deleteIfNew = false, deleteAlways = false } = {}) => {
        if (isClosingEditorRef.current) {
            return;
        }

        isClosingEditorRef.current = true;

        if (deleteAlways || (deleteIfNew && field._isNew)) {
            deleteField(pageIndex, rowIndex, fieldIndex);
            announceFormBuilderStatus(Craft.t('formie', '{label} field deleted.', {
                label: fieldDisplayLabel,
            }));
        }

        setEditingField(null);
        setIsAdapterPickerOpen(false);
    };

    const handleFieldClick = (e) => {
        if (isInlineContainerBuilder) {
            return;
        }

        openFieldEditor(field);
    };

    const handleCustomFieldAdapterSelect = (adapterType) => {
        const adapterDefinition = customFieldAdapters.find((adapter) => {
            return adapter.type === adapterType;
        });
        const nextField = {
            ...field,
            customFieldAdapter: adapterType,
            customFieldAdapterHandle: adapterDefinition?.handle,
            customFieldAdapterSettings: {
                ...(adapterDefinition?.defaultSettings || {}),
                ...(field.customFieldAdapterSettings || {}),
            },
        };

        updateField(pageIndex, rowIndex, fieldIndex, nextField);
        setIsAdapterPickerOpen(false);
        setEditingField(nextField);
    };

    const handleCustomFieldAdapterCancel = () => {
        setIsAdapterPickerOpen(false);

        if (field._isNew) {
            deleteField(pageIndex, rowIndex, fieldIndex);
        }
    };

    const fieldModalErrors = useMemo(() => {
        const prefix = `pages.${pageIndex}.rows.${rowIndex}.fields.${fieldIndex}.`;
        const entries = Object.entries(formErrors || {});
        const modalErrors = {};

        entries.forEach(([key, value]) => {
            if (!key.startsWith(prefix)) {
                return;
            }

            const modalKey = key.slice(prefix.length);
            if (modalKey) {
                modalErrors[modalKey] = value;
            }
        });

        return modalErrors;
    }, [formErrors, pageIndex, rowIndex, fieldIndex]);

    return (
        <>
            <div
                ref={ref}
                data-id={field?.id ?? undefined}
                className={cn(
                    'w-full min-w-0 max-w-full',
                    'outline-none',
                    isBuilderField ? 'p-2' : 'p-3',
                    'text-sm',
                    'rounded-lg',
                    'text-[13px]',
                    'text-[#33475b]',
                    'transition-colors duration-200',
                    !isBuilderField && !isAnyDragActive && 'hover:bg-[#f1f5f8]',
                    'relative group',
                    isInlineContainerBuilder ? 'cursor-default' : 'cursor-pointer',

                    (hasErrors)
                        ? 'bg-[#fff6f6] shadow-[0_0_1px_1px_#ef9898]'
                        : '',

                    isFieldDragging ? 'opacity-50 bg-slate-100' : '',
                )}
            >
                {!isInlineContainerBuilder && (
                    <button
                        ref={handleRef}
                        type="button"
                        className="absolute inset-0 z-1 cursor-pointer rounded-lg border-0 bg-transparent p-0 focus-visible:outline-none focus-visible:shadow-[inset_0_0_0_2px_var(--color-sky-600),inset_0_0_0_4px_#ffffff]"
                        aria-label={Craft.t('formie', 'Edit {label}', { label: fieldDisplayLabel })}
                        onMouseDown={(e) => {
                            // Prevent first click from being consumed by focus move when coming from an input.
                            e.preventDefault();
                        }}
                        onClick={handleFieldClick}
                    />
                )}

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
                                    className={cn(
                                        'w-7 h-7 p-0',
                                        'rounded-lg',
                                    )}
                                    data-dropdown-trigger
                                    data-form-builder-field-actions-trigger={field?._id}
                                    aria-label={Craft.t('formie', 'Actions for {label}', { label: fieldDisplayLabel })}
                                />
                            )}
                        >
                            <FontAwesomeIcon
                                icon={faEllipsis}
                                className="size-3.5"
                            />
                        </DropdownMenuTrigger>

                        <DropdownMenuContent align="end" className="min-w-[140px]">
                            <DropdownMenuItem onClick={handleEdit}>
                                <FontAwesomeIcon icon={faPencil} />
                                {Craft.t('formie', 'Edit')}
                            </DropdownMenuItem>

                            {!isBuilderField && (
                                <DropdownMenuItem onClick={handleToggleRequired}>
                                    <FontAwesomeIcon icon={faAsterisk} />
                                    {field.required
                                        ? Craft.t('formie', 'Make optional')
                                        : Craft.t('formie', 'Make required')
                                    }
                                </DropdownMenuItem>
                            )}

                            <DropdownMenuItem onClick={handleDuplicate}>
                                <FontAwesomeIcon icon={faClone} />
                                {Craft.t('formie', 'Duplicate')}
                            </DropdownMenuItem>

                            {isSyncedField && (
                                <DropdownMenuItem onClick={handleDetachSync}>
                                    <FontAwesomeIcon icon={faLinkSlash} />
                                    {Craft.t('formie', 'Detach sync')}
                                </DropdownMenuItem>
                            )}

                            <DropdownMenuSeparator />

                            <DropdownMenuItem
                                onClick={handleMoveUp}
                                disabled={!canMoveUp}
                                className={cn(!canMoveUp && 'opacity-50 pointer-events-none')}
                            >
                                <FontAwesomeIcon icon={faArrowUp} />
                                {Craft.t('formie', 'Move up')}
                            </DropdownMenuItem>

                            <DropdownMenuItem
                                onClick={handleMoveDown}
                                disabled={!canMoveDown}
                                className={cn(!canMoveDown && 'opacity-50 pointer-events-none')}
                            >
                                <FontAwesomeIcon icon={faArrowDown} />
                                {Craft.t('formie', 'Move down')}
                            </DropdownMenuItem>

                            <DropdownMenuItem
                                onClick={handleMoveLeft}
                                disabled={!canMoveLeft}
                                className={cn(!canMoveLeft && 'opacity-50 pointer-events-none')}
                            >
                                <FontAwesomeIcon icon={faArrowLeft} />
                                {Craft.t('formie', 'Move left')}
                            </DropdownMenuItem>

                            <DropdownMenuItem
                                onClick={handleMoveRight}
                                disabled={!canMoveRight}
                                className={cn(!canMoveRight && 'opacity-50 pointer-events-none')}
                            >
                                <FontAwesomeIcon icon={faArrowRight} />
                                {Craft.t('formie', 'Move right')}
                            </DropdownMenuItem>

                            <DropdownMenuSeparator />

                            <DropdownMenuItem
                                onClick={handleDelete}
                                className="text-error focus:text-error"
                            >
                                <FontAwesomeIcon icon={faXmark} />
                                {Craft.t('formie', 'Delete')}
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>

                {/* Field Content */}
                <div className={cn(
                    isInlineContainerBuilder ? 'pointer-events-auto select-auto' : 'pointer-events-none select-none',
                    isBuilderField ? 'space-y-0' : 'space-y-2',
                )}>
                    {!isBuilderField && (shouldUseFieldLabel || field.instructions || hasFieldStatusIndicators) && (
                        <div className={cn(
                            'space-y-1 leading-none pr-8',
                        )}>
                            {(shouldUseFieldLabel || hasFieldStatusIndicators) && (
                                <div className={cn(
                                    'font-medium',
                                    'flex items-center gap-1 min-w-0',
                                )}>
                                    {shouldUseFieldLabel && (
                                        <>
                                            <span
                                                className={cn('truncate')}
                                                title={fieldDisplayLabel}
                                            >
                                                {fieldDisplayLabel}
                                            </span>

                                            {field.required && (
                                                <span className={cn('text-error')}>*</span>
                                            )}
                                        </>
                                    )}

                                    {isSyncedField && (
                                        <div className={cn(
                                            'inline-flex items-center gap-1',
                                            'rounded-[10px] border border-[#f6ad55] bg-[#fffaf0]',
                                            'px-[6px] py-[3px]',
                                            'text-[10px] font-medium text-[#b45309]',
                                            shouldUseFieldLabel && 'ml-2',
                                        )}>
                                            <FontAwesomeIcon icon={faRefresh} className="size-2.5" />
                                            <span>{Craft.t('formie', 'Synced')}</span>
                                        </div>
                                    )}

                                    {hasConditions && (
                                        <div className={cn(
                                            'inline-flex items-center gap-1',
                                            'rounded-[10px] border border-[#0ea5e9] bg-[#f0faff]',
                                            'px-[6px] py-[3px]',
                                            'text-[10px] font-medium text-[#0077b6]',
                                            shouldUseFieldLabel && 'ml-2',
                                        )}>
                                            <FontAwesomeIcon icon={faEye} className="size-3" />
                                            <span>{Craft.t('formie', 'Conditions')}</span>
                                        </div>
                                    )}

                                    {showPaymentPlacementWarning && (
                                        <div
                                            className={cn(
                                                'inline-flex items-center gap-1',
                                                'rounded-[10px] border border-[#f6ad55] bg-[#fffaf0]',
                                                'px-[6px] py-[3px]',
                                                'text-[10px] font-medium text-[#b45309]',
                                                shouldUseFieldLabel && 'ml-2',
                                            )}
                                            title={Craft.t('formie', 'Payment fields should be placed on the final page to avoid incomplete paid submissions.')}
                                        >
                                            <FontAwesomeIcon icon={faTriangleExclamation} className="size-3" />
                                            <span>{Craft.t('formie', 'Payment Placement')}</span>
                                        </div>
                                    )}

                                    {(field.enabled === false || field.visibility === 'disabled') && (
                                        <div className={cn(
                                            'inline-flex items-center gap-1',
                                            'rounded-[10px] border border-[#64748b] bg-[#f1f5f9]',
                                            'px-[6px] py-[3px]',
                                            'text-[10px] font-medium text-[#475569]',
                                            shouldUseFieldLabel && 'ml-2',
                                        )}>
                                            <FontAwesomeIcon icon={faXmark} className="size-3" />
                                            <span>{Craft.t('formie', 'Disabled')}</span>
                                        </div>
                                    )}

                                    {field.visibility === 'hidden' && (
                                        <div className={cn(
                                            'inline-flex items-center gap-1',
                                            'rounded-[10px] border border-[#6366f1] bg-[#eef2ff]',
                                            'px-[6px] py-[3px]',
                                            'text-[10px] font-medium text-[#4f46e5]',
                                            shouldUseFieldLabel && 'ml-2',
                                        )}>
                                            <FontAwesomeIcon icon={faEyeSlash} className="size-3" />
                                            <span>{Craft.t('formie', 'Hidden')}</span>
                                        </div>
                                    )}
                                </div>
                            )}

                            {field.instructions && <div className={cn(
                            'text-gray-500',
                                'text-[12px]',
                            )}>
                                {field.instructions}
                            </div>}
                        </div>
                    )}

                    <FieldPreview
                        field={field}
                        fieldType={fieldType}
                        pageIndex={pageIndex}
                        rowIndex={rowIndex}
                        fieldIndex={fieldIndex}
                    />
                </div>
            </div >

            {isAdapterPickerOpen && (
                <Dialog open={true} onOpenChange={(open) => {
                    if (!open) {
                        handleCustomFieldAdapterCancel();
                    }
                }}>
                    <CustomFieldAdapterPickerModal
                        adapters={customFieldAdapters}
                        onSelect={handleCustomFieldAdapterSelect}
                        onCancel={handleCustomFieldAdapterCancel}
                    />
                </Dialog>
            )}

            {editingField !== null && (
                <Dialog open={true} onOpenChange={(open) => {
                    if (!open) {
                        fieldEditorDismissAttemptRef.current?.();
                    }
                }}>
                    <FieldEditModal
                        field={editingField}
                        fieldType={fieldType}
                        errors={fieldModalErrors}
                        reservedHandles={topLevelReservedHandles}
                        dismissAttemptRef={fieldEditorDismissAttemptRef}
                        onSave={handleSaveField}
                        onCancel={() => {
                            closeFieldEditor({ deleteIfNew: true });
                        }}
                        onDismiss={({ deleteIfNew = false } = {}) => {
                            closeFieldEditor({ deleteIfNew });
                        }}
                        onDelete={() => {
                            closeFieldEditor({ deleteAlways: true });
                        }}
                    />
                </Dialog>
            )
            }
        </>
    );
};

const CustomFieldAdapterIcon = ({ icon }) => {
    if (!icon || typeof icon !== 'string') {
        return null;
    }

    return (
        <span
            className="flex size-5 shrink-0 items-center justify-center text-[#33475b] [&_svg]:size-5"
            aria-hidden="true"
            dangerouslySetInnerHTML={{ __html: icon }}
        />
    );
};

const CustomFieldAdapterPickerModal = ({ adapters, onSelect, onCancel }) => {
    const [selectedAdapter, setSelectedAdapter] = useState(null);
    const [searchValue, setSearchValue] = useState('');
    const selectedAdapterDefinition = useMemo(() => {
        return adapters.find((adapter) => { return adapter.type === selectedAdapter; }) || null;
    }, [adapters, selectedAdapter]);

    const handleApply = () => {
        if (!selectedAdapter) {
            return;
        }

        onSelect(selectedAdapter);
    };

    return (
        <DialogContent className={cn(
            'w-[calc(100vw-24px)] max-w-[640px]',
            'min-w-0',
        )}
        >
            <DialogHeader>
                <DialogTitle>
                    {Craft.t('formie', 'Choose Custom Field Type')}
                </DialogTitle>

                <DialogDescription>
                    {Craft.t('formie', 'Choose which Craft field adapter should power this Formie field. This cannot be changed later.')}
                </DialogDescription>
            </DialogHeader>

            <div className="flex min-h-[280px] items-center justify-center px-4 py-8">
                <div className="w-full max-w-[500px]">
                    <Combobox
                        items={adapters}
                        value={selectedAdapterDefinition}
                        onValueChange={(adapter) => {
                            setSelectedAdapter(adapter?.type ?? null);
                        }}
                        onInputValueChange={(value) => {
                            setSearchValue(String(value ?? ''));
                        }}
                        itemToStringLabel={(adapter) => {
                            return adapter?.label ?? '';
                        }}
                        itemToStringValue={(adapter) => {
                            return adapter?.type ?? '';
                        }}
                    >
                        <ComboboxPrimitiveInput
                            placeholder={Craft.t('formie', 'Choose a field type…')}
                            className="w-full"
                            showClear={false}
                        />

                        <ComboboxContent className="z-[10000] w-[var(--anchor-width)]">
                            <ComboboxEmpty>{Craft.t('formie', 'No field types found.')}</ComboboxEmpty>

                            <ComboboxList>
                                {(adapter) => {
                                    const sourceLabel = adapter.sourceLabel || Craft.t('formie', 'Custom adapter');

                                    return (
                                        <ComboboxItem
                                            key={adapter.type}
                                            value={adapter}
                                            className="py-2"
                                        >
                                            <span className="flex min-w-0 items-center gap-2.5">
                                                <CustomFieldAdapterIcon icon={adapter.icon} />

                                                <span className="min-w-0 flex-1">
                                                    <span className="block text-sm font-semibold leading-5 text-[#33475b]">
                                                        <ComboboxHighlightedText text={adapter.label} search={searchValue} />
                                                    </span>

                                                    <span className="block text-xs leading-4 text-gray-500">
                                                        {sourceLabel}
                                                    </span>
                                                </span>
                                            </span>
                                        </ComboboxItem>
                                    );
                                }}
                            </ComboboxList>
                        </ComboboxContent>
                    </Combobox>

                    {selectedAdapterDefinition?.craftFieldClasses?.length > 0 && (
                        <div className="mt-3 text-xs text-gray-500">
                            {Craft.t('formie', 'Uses compatible Craft field classes from the selected source.')}
                        </div>
                    )}
                </div>
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
                    onClick={handleApply}
                    disabled={!selectedAdapter}
                >
                    {Craft.t('formie', 'Continue')}
                </Button>
            </DialogFooter>
        </DialogContent>
    );
};

// Field Edit Modal Component
const FieldEditModal = ({
    field,
    fieldType,
    errors,
    reservedHandles = [],
    dismissAttemptRef = null,
    onSave,
    onCancel,
    onDismiss,
    onDelete,
}) => {
    const contentRef = useRef(null);
    const hasAutofocusedRef = useRef(false);
    const [isSchemaUiReady, setIsSchemaUiReady] = useState(false);
    const activeFieldType = fieldType;
    const customFieldAdapters = Array.isArray(activeFieldType?.data?.customFieldAdapters)
        ? activeFieldType.data.customFieldAdapters
        : [];
    const customFieldAdapterDefinition = customFieldAdapters.find((adapter) => {
        return adapter.type === field?.customFieldAdapter || adapter.handle === field?.customFieldAdapter;
    }) || null;
    const resolvedFieldTypeLabel = typeof activeFieldType?.label === 'string'
        ? activeFieldType.label.trim()
        : '';
    const fieldTypePillLabel = customFieldAdapterDefinition?.label && resolvedFieldTypeLabel
        ? `${resolvedFieldTypeLabel} - ${customFieldAdapterDefinition.label}`
        : resolvedFieldTypeLabel;
    const showFieldTypePill = fieldTypePillLabel !== '';
    const isSyncedField = Boolean(field?.isSynced || field?.syncId);
    const shouldUseFieldLabel = activeFieldType?.hasLabel !== false;
    const fieldDisplayLabel = shouldUseFieldLabel
        ? (field?.label || activeFieldType?.label || Craft.t('formie', 'Field'))
        : (activeFieldType?.label || Craft.t('formie', 'Field'));
    const hasSchemaConfig = Boolean(activeFieldType?.schemaIndex || activeFieldType?.schema);
    const fieldSchema = activeFieldType?.schemaIndex?.schema ?? activeFieldType?.schema ?? [];
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
        return injectReservedHandlesIntoSchemaIndex(activeFieldType?.schemaIndex, reservedHandles, handleFieldOverrides);
    }, [activeFieldType?.schemaIndex, reservedHandles, handleFieldOverrides]);
    const fallbackSchemaIndex = useMemo(() => {
        return {
            schema: schemaWithReservedHandles,
            fieldEntries: [],
        };
    }, [schemaWithReservedHandles]);
    const handleSyncOnChange = useHandleSyncOnChange(schemaWithReservedHandles);
    const handleModalChange = (values, form) => {
        handleSyncOnChange(values, form);
    };

    const form = useSchemaFormEngine({
        schema: schemaWithReservedHandles,
        schemaIndex: schemaIndexWithReservedHandles || fallbackSchemaIndex,
        defaultValues: field,
        errors,
        onChange: handleModalChange,
    });

    form.onSuccess((data) => {
        const previewOptions = Array.isArray(form.__formiePreviewOptions)
            ? form.__formiePreviewOptions
            : null;
        const nextData = String(data?.optionsMode || '') === 'dynamic' && previewOptions?.length > 0
            ? {
                ...data,
                _previewOptions: previewOptions,
            }
            : data;

        onSave(nextData);
    });

    useFieldEditorDismiss({
        field,
        fieldDisplayLabel,
        form,
        onDismiss,
        dismissAttemptRef,
        isBaselineReady: hasSchemaConfig ? isSchemaUiReady : true,
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

    useEffect(() => {
        if (!hasSchemaConfig) {
            setIsSchemaUiReady(false);
            return;
        }

        // Keep the loading state visible until the schema UI has mounted and painted.
        setIsSchemaUiReady(false);

        let rafOne = null;
        let rafTwo = null;

        rafOne = window.requestAnimationFrame(() => {
            rafTwo = window.requestAnimationFrame(() => {
                setIsSchemaUiReady(true);
            });
        });

        return () => {
            if (rafOne !== null) {
                window.cancelAnimationFrame(rafOne);
            }

            if (rafTwo !== null) {
                window.cancelAnimationFrame(rafTwo);
            }
        };
    }, [hasSchemaConfig, field?._id, field?.id, field?.type]);

    const handleSave = (e) => {
        e.preventDefault();

        submitSchemaFormAfterPendingTableUpdates(form);
    };

    const handleCancel = () => {
        onCancel();
    };

    const handleDelete = () => {
        const confirmationMessage = Craft.t('formie', 'Are you sure you want to delete "{name}"?', {
            name: fieldDisplayLabel,
        });

        const isConfirmed = window.confirm(confirmationMessage);

        if (!isConfirmed) {
            return;
        }
        onDelete();
    };

    // Only persisted layout fields have a Craft element id; new/duplicated fields rely on Cancel (deleteIfNew) or the canvas menu.
    const canDeleteFromModal = Boolean(field?.id);

    return (
        <DialogContent className={cn(
            'w-[calc(100vw-24px)] h-[calc(100dvh-24px)]',
            'min-w-0 min-h-0 max-w-none',
            'md:w-[66%] md:h-[66%]',
            'md:min-w-[600px] md:min-h-[400px]',
        )}
        >
            <DialogHeader>
                <DialogTitle className="flex flex-row items-center">
                    {Craft.t('formie', 'Edit Field')}

                    {showFieldTypePill && (
                        <div className={cn(
                            'rounded-[20px]',
                            'bg-[#d8e2ea]',
                            'px-[10px] py-[6px]',
                            'text-[10px]',
                            'text-[#526176]',
                            'ml-[10px]',
                            'font-normal',
                        )}>{fieldTypePillLabel}</div>
                    )}
                </DialogTitle>

                <DialogDescription className="hidden">
                    {Craft.t('formie', 'Edit the field for this form.')}
                </DialogDescription>
            </DialogHeader>

            <div ref={contentRef} className="flex flex-1 min-h-0 flex-col overflow-hidden">
                {isSyncedField && (
                    <div className="m-4 mb-0 flex shrink-0 items-center gap-2 rounded border border-[#f6ad55] bg-[#fffaf0] px-3 py-2 text-[12px] text-[#b45309]">
                        <FontAwesomeIcon icon={faTriangleExclamation} className="size-3 shrink-0" />
                        <span>{Craft.t('formie', 'Warning: Currently editing synced field. Changes to this field will be applied to all instances of this field.')}</span>
                    </div>
                )}

                {hasSchemaConfig ? (
                    <div className="relative min-h-0 flex-1">
                        <div className={cn(
                            'h-full',
                            !isSchemaUiReady && 'invisible pointer-events-none',
                        )}>
                            <SchemaFormEngine
                                form={form}
                                className="h-full"
                            />
                        </div>

                        {!isSchemaUiReady && (
                            <div className="absolute inset-0 flex items-center justify-center">
                                <div className="flex items-center gap-2 text-gray-500">
                                    <div className="size-4"><Spinner size="xs" /></div>
                                    <span className="text-sm">{Craft.t('formie', 'Loading field settings…')}</span>
                                </div>
                            </div>
                        )}
                    </div>
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
                    <Button
                        type="button"
                        onClick={handleDelete}
                    >
                        {Craft.t('formie', 'Delete')}
                    </Button>
                )}

                <div className="flex flex-row justify-end gap-2">
                    <Button
                        type="button"
                        onClick={handleCancel}
                    >
                        {Craft.t('formie', 'Cancel')}
                    </Button>

                    <Button
                        type="button"
                        variant="primary"
                        onClick={handleSave}
                        disabled={!hasSchemaConfig}
                    >
                        {Craft.t('formie', 'Apply')}
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    );
};

export { Field };
