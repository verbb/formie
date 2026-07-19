import React, {
    useState, useEffect, useMemo, useRef,
} from 'react';
import { useDraggable, useDragOperation } from '@dnd-kit/react';

import {
    Button, Combobox, Dialog, DropdownItem, DropdownMenu, DropdownSeparator, Icon, Option, Spinner, TiptapContent,
} from '@verbb/plugin-kit-react/components';

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
import { cn } from '@verbb/plugin-kit-react/utils';
import { focusFirstVisibleInputIfEmpty } from '@form-builder/utils/focus';
import { syncContainerRowsFromVariant } from '@form-builder/utils/containerLayoutVariants';
import { announceFormBuilderStatus, focusFieldActionsTrigger } from '@form-builder/utils/accessibility';
import { submitSchemaFormAfterPendingTableUpdates } from '@form-builder/utils/submitSchemaForm';
import { useFieldEditorDismiss } from '@form-builder/hooks/useFieldEditorDismiss';
import { useResetDialogBodyScrollOnTabChange } from '@form-builder/hooks/useResetDialogBodyScrollOnTabChange';
import { normalizeFieldEditorValues, normalizeRichTextValue, hasRichTextValue } from '@form-builder/utils/richTextValue';
import {
    getFieldDisplayLabel,
    shouldShowFieldDisplayLabel,
    hasQuestionFieldLabelContent,
} from '@form-builder/utils/fieldDisplayLabel';
import { getFieldEditorConditionContext } from '@form-builder/utils/fieldEditorConditionContext';
import { SnapTopLeftCornerToCursor } from '@utils';

import { FieldPreview } from './FieldPreview';
import { ExistingFields } from './ExistingFields';
import { FieldEditorNotices } from './FieldEditorNotices';
import { FieldBuilderHandle } from './FieldBuilderHandle';
import { FieldBuilderEncryptedBadge } from './FieldBuilderEncryptedBadge';
import { useFieldEditorLockState } from '@form-builder/hooks/useFieldEditorLockState';

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
    const showFieldHandles = useAppStore((state) => { return state.showFieldHandles; });
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
    const shouldUseFieldLabel = !isBuilderField && shouldShowFieldDisplayLabel(field, fieldType);
    const fieldDisplayLabel = getFieldDisplayLabel(field, fieldType);
    const showQuestionRichTextLabel = hasQuestionFieldLabelContent(field, fieldType);
    const isInlineContainerBuilder = Boolean(fieldType?.isContainerParentField || fieldType?.isRepeatableParentField);
    const canAddExistingFieldsToGroup = Boolean(fieldType?.isContainerParentField && !fieldType?.isRepeatableParentField);
    const draggableFieldId = field?._id || `${pageIndex}-${rowIndex}-${fieldIndex}`;
    const [editingField, setEditingField] = useState(null);
    const [isAdapterPickerOpen, setIsAdapterPickerOpen] = useState(false);
    const [isDropdownOpen, setIsDropdownOpen] = useState(false);
    const [showGroupExistingFields, setShowGroupExistingFields] = useState(false);
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
    const isEncryptedField = Boolean(field?.enableContentEncryption);
    const isBuilderLocked = Boolean(field?.builderLocked);
    const hasFieldStatusIndicators = isSyncedField
        || isEncryptedField
        || isBuilderLocked
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

    const groupExistingFieldsPlacement = useMemo(() => {
        if (!canAddExistingFieldsToGroup) {
            return null;
        }

        const allowedFieldTypes = Array.isArray(fieldType?.data?.nestedLayoutBuilder?.allowedFieldTypes)
            ? fieldType.data.nestedLayoutBuilder.allowedFieldTypes
            : [];

        return {
            pageIndex,
            rowIndex,
            fieldIndex,
            allowedFieldTypes,
        };
    }, [canAddExistingFieldsToGroup, fieldType, pageIndex, rowIndex, fieldIndex]);

    const handleOpenGroupExistingFields = () => {
        setShowGroupExistingFields(true);
        setIsDropdownOpen(false);
    };

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

        if (field?.builderLocked) {
            window.alert(Craft.t('formie', 'This field is locked. Open field settings and unlock it before deleting.'));
            return;
        }

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
                    <DropdownMenu
                        size="sm"
                        placement="bottom-end"
                        open={isDropdownOpen}
                        onPkOpenChange={(event) => {
                            setIsDropdownOpen(event.detail?.open ?? false);
                        }}
                    >
                        <Button
                            slot="trigger"
                            variant="transparent"
                            size="sm"
                            className={cn(
                                'w-7 h-7 p-0',
                                'rounded-lg',
                            )}
                            data-dropdown-trigger
                            data-form-builder-field-actions-trigger={field?._id}
                            aria-label={Craft.t('formie', 'Actions for {label}', { label: fieldDisplayLabel })}
                        >
                            <Icon slot="start" icon="ellipsis"
                                className="size-3.5" />
                        </Button>

                        <DropdownItem onPkSelect={handleEdit}>
                            <Icon slot="start" icon="pen" />
                            {Craft.t('formie', 'Edit')}
                        </DropdownItem>

                        {!isBuilderField && (
                            <DropdownItem onPkSelect={handleToggleRequired}>
                                <Icon slot="start" icon="asterisk" />
                                {field.required
                                    ? Craft.t('formie', 'Make optional')
                                    : Craft.t('formie', 'Make required')
                                }
                            </DropdownItem>
                        )}

                        <DropdownItem onPkSelect={handleDuplicate}>
                            <Icon slot="start" icon="clone" />
                            {Craft.t('formie', 'Duplicate')}
                        </DropdownItem>

                        {canAddExistingFieldsToGroup && (
                            <DropdownItem onPkSelect={handleOpenGroupExistingFields}>
                                <Icon slot="start" icon="plus" />
                                {Craft.t('formie', 'Add existing fields')}
                            </DropdownItem>
                        )}

                        {isSyncedField && (
                            <DropdownItem onPkSelect={handleDetachSync}>
                                <Icon slot="start" icon="link-slash" />
                                {Craft.t('formie', 'Detach sync')}
                            </DropdownItem>
                        )}

                        <DropdownSeparator />

                        <DropdownItem
                            onPkSelect={handleMoveUp}
                            disabled={!canMoveUp}
                        >
                            <Icon slot="start" icon="arrow-up" />
                            {Craft.t('formie', 'Move up')}
                        </DropdownItem>

                        <DropdownItem
                            onPkSelect={handleMoveDown}
                            disabled={!canMoveDown}
                        >
                            <Icon slot="start" icon="arrow-down" />
                            {Craft.t('formie', 'Move down')}
                        </DropdownItem>

                        <DropdownItem
                            onPkSelect={handleMoveLeft}
                            disabled={!canMoveLeft}
                        >
                            <Icon slot="start" icon="arrow-left" />
                            {Craft.t('formie', 'Move left')}
                        </DropdownItem>

                        <DropdownItem
                            onPkSelect={handleMoveRight}
                            disabled={!canMoveRight}
                        >
                            <Icon slot="start" icon="arrow-right" />
                            {Craft.t('formie', 'Move right')}
                        </DropdownItem>

                        <DropdownSeparator />

                        <DropdownItem
                            onPkSelect={handleDelete}
                            destructive
                        >
                            <Icon slot="start" icon="xmark" />
                            {Craft.t('formie', 'Delete')}
                        </DropdownItem>
                    </DropdownMenu>
                </div>

                {/* Field Content */}
                <div className={cn(
                    isInlineContainerBuilder ? 'pointer-events-auto select-auto' : 'pointer-events-none select-none',
                    isBuilderField ? 'space-y-0' : 'space-y-2',
                )}>
                    {!isBuilderField && (shouldUseFieldLabel || hasRichTextValue(field.instructions) || hasFieldStatusIndicators || (showFieldHandles && field.handle)) && (
                        <div className={cn(
                            'space-y-1 leading-none pr-8',
                        )}>
                            {(shouldUseFieldLabel || hasFieldStatusIndicators || (showFieldHandles && field.handle)) && (
                                <div className={cn(
                                    'relative font-medium',
                                    'flex items-center gap-1 min-w-0',
                                )}>
                                    <div className="flex min-w-0 flex-1 items-center gap-1">
                                        {shouldUseFieldLabel && (
                                            <>
                                                {showQuestionRichTextLabel ? (
                                                    <div
                                                        className={cn('truncate min-w-0 [&_.ProseMirror]:truncate')}
                                                        title={fieldDisplayLabel}
                                                    >
                                                        <TiptapContent value={normalizeRichTextValue(field.question)} />
                                                    </div>
                                                ) : (
                                                    <span
                                                        className={cn('truncate')}
                                                        title={fieldDisplayLabel}
                                                    >
                                                        {fieldDisplayLabel}
                                                    </span>
                                                )}

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
                                            <Icon icon="refresh" className="size-2.5" />
                                            <span>{Craft.t('formie', 'Synced')}</span>
                                        </div>
                                    )}

                                    <FieldBuilderEncryptedBadge
                                        enabled={isEncryptedField}
                                        className={shouldUseFieldLabel && 'ml-2'}
                                    />

                                    {isBuilderLocked && (
                                        <div
                                            className={cn(
                                                'inline-flex items-center gap-1',
                                                'rounded-[10px] border border-[#64748b] bg-[#f1f5f9]',
                                                'px-[6px] py-[3px]',
                                                'text-[10px] font-medium text-[#475569]',
                                                shouldUseFieldLabel && 'ml-2',
                                            )}
                                            title={field.builderNote || Craft.t('formie', 'Field settings require unlock to edit.')}
                                        >
                                            <Icon icon="lock" className="size-2.5" />
                                            <span>{Craft.t('formie', 'Locked')}</span>
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
                                            <Icon icon="eye" className="size-3" />
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
                                            <Icon icon="triangle-exclamation" className="size-3" />
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
                                            <Icon icon="xmark" className="size-3" />
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
                                            <Icon icon="eye-slash" className="size-3" />
                                            <span>{Craft.t('formie', 'Hidden')}</span>
                                        </div>
                                    )}
                                    </div>

                                    <FieldBuilderHandle handle={field.handle} isAnyDragActive={isAnyDragActive} />
                                </div>
                            )}

                            {hasRichTextValue(field.instructions) && <div className={cn(
                            'text-gray-500',
                                'text-[12px]',
                            )}>
                                <TiptapContent value={normalizeRichTextValue(field.instructions)} />
                            </div>}
                        </div>
                    )}

                    <FieldPreview
                        field={field}
                        fieldType={fieldType}
                        pageIndex={pageIndex}
                        rowIndex={rowIndex}
                        fieldIndex={fieldIndex}
                        canAddExistingFields={canAddExistingFieldsToGroup}
                        onOpenExistingFields={handleOpenGroupExistingFields}
                    />
                </div>
            </div >

            {isAdapterPickerOpen && (
                <CustomFieldAdapterPickerModal
                    adapters={customFieldAdapters}
                    onSelect={handleCustomFieldAdapterSelect}
                    onCancel={handleCustomFieldAdapterCancel}
                />
            )}

            {showGroupExistingFields && groupExistingFieldsPlacement && (
                <ExistingFields
                    nestedPlacement={groupExistingFieldsPlacement}
                    onClose={() => {
                        setShowGroupExistingFields(false);
                    }}
                />
            )}

            {editingField !== null && (
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
            )}
        </>
    );
};

const CustomFieldAdapterIcon = ({ icon }) => {
    if (!icon || typeof icon !== 'string') {
        return null;
    }

    return (
        <span
            slot="start"
            className="flex size-4 shrink-0 items-center justify-center text-[#33475b] [&_svg]:size-4"
            aria-hidden="true"
            dangerouslySetInnerHTML={{ __html: icon }}
        />
    );
};

const CustomFieldAdapterPickerModal = ({ adapters, onSelect, onCancel }) => {
    const [selectedAdapter, setSelectedAdapter] = useState('');
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
        <Dialog
            open
            label={Craft.t('formie', 'Choose Custom Field Type')}
            description={Craft.t('formie', 'Choose which Craft field adapter should power this Formie field. This cannot be changed later.')}
            className="formie-custom-field-adapter-dialog"
            onPkOpenChange={(event) => {
                if (!(event.detail?.open ?? event.target?.open ?? false)) {
                    onCancel();
                }
            }}
        >
            {/* v1: min-h-[280px] vertically centers the combobox in a short-content modal. */}
            <div className="flex min-h-[280px] items-center justify-center px-4 py-8">
                <div className="w-full max-w-[500px]">
                    {/* Stock pk-combobox: string values; icon via Option start slot; kit highlights matches. */}
                    <Combobox
                        className="w-full"
                        value={selectedAdapter}
                        placeholder={Craft.t('formie', 'Choose a field type…')}
                        emptyMessage={Craft.t('formie', 'No field types found.')}
                        onPkChange={(event) => {
                            setSelectedAdapter(String(event.detail?.value ?? ''));
                        }}
                    >
                        {adapters.map((adapter) => {
                            const sourceLabel = adapter.sourceLabel || Craft.t('formie', 'Custom adapter');

                            return (
                                <Option
                                    key={adapter.type}
                                    value={adapter.type}
                                    label={adapter.label}
                                >
                                    <CustomFieldAdapterIcon icon={adapter.icon} />
                                    <span className="flex min-w-0 flex-col">
                                        <span className="text-sm font-semibold leading-5 text-[#33475b]">
                                            {adapter.label}
                                        </span>
                                        <span className="text-xs leading-4 text-gray-500">
                                            {sourceLabel}
                                        </span>
                                    </span>
                                </Option>
                            );
                        })}
                    </Combobox>

                    {selectedAdapterDefinition?.craftFieldClasses?.length > 0 && (
                        <div className="mt-3 text-xs text-gray-500">
                            {Craft.t('formie', 'Uses compatible Craft field classes from the selected source.')}
                        </div>
                    )}
                </div>
            </div>

            <Button
                slot="footer"
                type="button"
                onClick={onCancel}
            >
                {Craft.t('formie', 'Cancel')}
            </Button>

            <Button
                slot="footer"
                type="button"
                variant="primary"
                onClick={handleApply}
                disabled={!selectedAdapter}
            >
                {Craft.t('formie', 'Continue')}
            </Button>
        </Dialog>
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
    onCancel: _onCancel,
    onDismiss,
    onDelete,
}) => {
    const contentRef = useRef(null);
    const hasAutofocusedRef = useRef(false);
    // Panel-owned scroll (v1 ModalTabs) — hook retained as no-op for shared imports.
    useResetDialogBodyScrollOnTabChange(contentRef);
    // Controlled open so Cancel/Apply/Delete can close through pk-dialog (exit animation)
    // before the parent unmounts this tree on pk-after-hide.
    const [open, setOpen] = useState(true);
    const pendingCloseRef = useRef(null);
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
    const shouldUseFieldLabel = shouldShowFieldDisplayLabel(field, activeFieldType);
    const fieldDisplayLabel = getFieldDisplayLabel(field, activeFieldType);
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
    const hasSubmissions = useAppStore((state) => { return state.hasSubmissions; });
    const {
        builderNoteLive,
        isSettingsLocked,
        syncFromFormValues,
        unlock,
    } = useFieldEditorLockState(field);
    const handleModalChange = (values, form) => {
        syncFromFormValues(values);
        handleSyncOnChange(values, form);
    };

    const fieldDefaults = useMemo(() => {
        return normalizeFieldEditorValues(field);
    }, [field]);

    const form = useSchemaFormEngine({
        schema: schemaWithReservedHandles,
        schemaIndex: schemaIndexWithReservedHandles || fallbackSchemaIndex,
        defaultValues: fieldDefaults,
        errors,
        getConditionContext: (values) => {
            return getFieldEditorConditionContext(field, values, hasSubmissions);
        },
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

        // Defer parent unmount until after the exit animation.
        pendingCloseRef.current = { type: 'save', data: nextData };
        setOpen(false);
    });

    useFieldEditorDismiss({
        field,
        fieldDisplayLabel,
        form,
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

        if (isSettingsLocked) {
            return;
        }

        submitSchemaFormAfterPendingTableUpdates(form);
    };

    const handleDelete = () => {
        if (field?.builderLocked && isSettingsLocked) {
            window.alert(Craft.t('formie', 'This field is locked. Unlock it before deleting.'));
            return;
        }

        const confirmationMessage = Craft.t('formie', 'Are you sure you want to delete "{name}"?', {
            name: fieldDisplayLabel,
        });

        const isConfirmed = window.confirm(confirmationMessage);

        if (!isConfirmed) {
            return;
        }

        pendingCloseRef.current = { type: 'delete' };
        setOpen(false);
    };

    const handleAfterHide = () => {
        const pending = pendingCloseRef.current;
        pendingCloseRef.current = null;

        if (pending?.type === 'save') {
            onSave(pending.data);
            return;
        }

        if (pending?.type === 'delete') {
            onDelete();
            return;
        }

        onDismiss({ deleteIfNew: Boolean(field?._isNew) });
    };

    // Only persisted layout fields have a Craft element id; new/duplicated fields rely on Cancel (deleteIfNew) or the canvas menu.
    const canDeleteFromModal = Boolean(field?.id);

    return (
        <Dialog
            open={open}
            withoutBodyPadding
            className="formie-field-edit-dialog"
            // Confirm on cancelable pk-hide; side effects run on pk-after-hide so the
            // exit animation is not cut short by unmounting the host mid-flight.
            onPkHide={(event) => {
                if (pendingCloseRef.current) {
                    return;
                }

                if (dismissAttemptRef?.current && !dismissAttemptRef.current()) {
                    event.preventDefault();
                }
            }}
            onPkOpenChange={(event) => {
                setOpen(Boolean(event.detail?.open ?? event.target?.open));
            }}
            onPkAfterHide={handleAfterHide}
        >
            {/* Custom header: lock/type pill + v1 DialogHeader chrome (absolute close). */}
            <div slot="header" className="formie-field-edit-header">
                <h2 className="formie-field-edit-title">
                    {Craft.t('formie', 'Edit Field')}

                    {field?.builderLocked && (
                        <Icon
                            icon="lock"
                            className="ml-2 size-3.5 text-[#64748b]"
                            title={Craft.t('formie', 'Locked field')}
                        />
                    )}

                    {showFieldTypePill && (
                        <div className="formie-field-edit-type-pill">{fieldTypePillLabel}</div>
                    )}
                </h2>
                <Button
                    type="button"
                    variant="none"
                    size="none"
                    icon
                    className="formie-field-edit-close"
                    aria-label={Craft.t('app', 'Close')}
                    data-dialog-close
                >
                    <Icon slot="start" icon="xmark" />
                </Button>
            </div>

            <div ref={contentRef} className="formie-field-edit-dialog-body">
                <FieldEditorNotices
                    field={field}
                    isSyncedField={isSyncedField}
                    builderNote={builderNoteLive}
                    isSettingsLocked={isSettingsLocked}
                    onUnlock={unlock}
                />

                {hasSchemaConfig ? (
                    <div className="relative flex min-h-0 flex-1 flex-col">
                        <div className={cn(
                            'flex h-full min-h-0 flex-col',
                            !isSchemaUiReady && 'invisible pointer-events-none',
                            isSettingsLocked && 'pointer-events-none select-none opacity-60',
                        )}>
                            <SchemaFormEngine
                                form={form}
                                className="flex h-full min-h-0 flex-col"
                            />
                        </div>

                        {isSettingsLocked && isSchemaUiReady && (
                            <div
                                className="absolute inset-0 z-10 cursor-not-allowed"
                                aria-hidden="true"
                            />
                        )}

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

            <div
                slot="footer"
                className={cn(
                    'flex w-full flex-row gap-2',
                    canDeleteFromModal ? 'justify-between' : 'justify-end',
                )}
            >
                {canDeleteFromModal && (
                    <Button
                        type="button"
                        onClick={handleDelete}
                        disabled={isSettingsLocked}
                    >
                        {Craft.t('formie', 'Delete')}
                    </Button>
                )}

                <div className="flex flex-row justify-end gap-2">
                    <Button
                        type="button"
                        data-dialog-close
                    >
                        {Craft.t('formie', 'Cancel')}
                    </Button>

                    <Button
                        type="button"
                        variant="primary"
                        onClick={handleSave}
                        disabled={!hasSchemaConfig || isSettingsLocked}
                    >
                        {Craft.t('formie', 'Apply')}
                    </Button>
                </div>
            </div>
        </Dialog>
    );
};

export { Field };
