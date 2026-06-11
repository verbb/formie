import {
    useState, useEffect, useLayoutEffect, useMemo, useRef, useCallback, memo,
} from 'react';
import React from 'react';
import { get, cloneDeep } from 'lodash-es';

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faGripDotsVertical, faPlus, faChevronDown, faXmark } from '@fortawesome/pro-solid-svg-icons';

import {
    DragDropProvider,
    DragOverlay,
    useDraggable,
    useDroppable,
    useDragOperation,
    PointerSensor,
    KeyboardSensor,
} from '@dnd-kit/react';
import { PointerActivationConstraints } from '@dnd-kit/dom';
import { AutoScroller, Cursor } from '@dnd-kit/dom';
import { CollisionPriority, CollisionType } from '@dnd-kit/abstract';

import { useFormValue } from '@form-builder/hooks/useFormTools';
import { useBuilderActions } from '@form-builder/builder/useBuilderActions';
import { MAX_FIELDS_PER_ROW } from '@form-builder/builder/constants';
import { canDropInNestedContainer, canDropToTopLevel, isAllowedNestedTargetDrop } from '@form-builder/builder/nestedMoveUtils';
import { useFormBuilderApp } from '@form-builder/contexts/FormBuilderAppContext';
import useUrlRouter from '@form-builder/hooks/useUrlRouter';
import { getDevToolsConfig } from '@form-builder/dev/config';
import { assignFieldReferences } from '@form-builder/utils/fieldReferences';
import {
    collectFieldHandlesFromRows,
    prepareNewFieldForInsert,
} from '@form-builder/utils/duplicateField';
import {
    Button,
    Combobox,
    ComboboxPrimitiveInput,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxList,
    ComboboxCollection,
    ComboboxGroup,
    ComboboxLabel,
    ComboboxItem,
    ComboboxSeparator,
    ComboboxTrigger,
} from '@verbb/plugin-kit-react/components';
import { Field } from './Field';
import { PageTabs } from './PageTabs';
import { ExistingFields } from './ExistingFields';
import { PageButtons } from './PageButtons';
import { FieldBuilderDnDDebugPanel } from '../dev/FieldBuilderDnDDebugPanel';

import { cn } from '@verbb/plugin-kit-react/utils';
import { announceFormBuilderStatus } from '@form-builder/utils/accessibility';
import { SnapTopLeftCornerToCursor } from '@utils';

const getDropzoneHitboxPadding = (id) => {
    const stringId = String(id);

    if (stringId.startsWith('field-') || stringId.startsWith('nested-field|')) {
        return { x: 15, y: 20 };
    }

    if (stringId.startsWith('row-') || stringId.startsWith('nested-row|')) {
        return { x: 0, y: 20 };
    }

    return { x: 0, y: 0 };
};

const expandedPointerIntersection = ({ dragOperation, droppable }) => {
    const pointerCoordinates = dragOperation?.position?.current;
    const rect = droppable?.shape?.boundingRectangle;

    if (!pointerCoordinates || !rect) {
        return null;
    }

    const { x: padX, y: padY } = getDropzoneHitboxPadding(droppable.id);
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

const POINTER_DRAG_ACTIVATION_DISTANCE = 4;
const TOUCH_DRAG_ACTIVATION_DELAY = 250;
const TOUCH_DRAG_ACTIVATION_TOLERANCE = 10;

function FieldBuilder({ fields }) {
    const {
        fieldTypeGroups,
        activePageHandle,
        getFieldTypeByType,
        isFieldTypeSidebarOpen,
        setIsFieldTypeSidebarOpen,
    } = useFormBuilderApp();
    const pages = useFormValue('pages', []);
    const activePageIndex = useMemo(() => {
        const resolvedPageIndex = pages.findIndex((page) => { return page._handle === activePageHandle; });
        return resolvedPageIndex >= 0 ? resolvedPageIndex : 0;
    }, [pages, activePageHandle]);
    const activePage = useMemo(() => { return pages[activePageIndex]; }, [pages, activePageIndex]);
    const router = useUrlRouter();
    const sensors = useMemo(() => {
        return [
            PointerSensor.configure({
                activationConstraints: (event) => {
                    // Touch scrolling should win unless the user clearly long-presses to drag.
                    if (event.pointerType === 'touch') {
                        return [
                            new PointerActivationConstraints.Delay({
                                value: TOUCH_DRAG_ACTIVATION_DELAY,
                                tolerance: TOUCH_DRAG_ACTIVATION_TOLERANCE,
                            }),
                        ];
                    }

                    return [
                        new PointerActivationConstraints.Distance({
                            value: POINTER_DRAG_ACTIVATION_DISTANCE,
                        }),
                    ];
                },
            }),
            KeyboardSensor,
        ];
    }, []);

    const {
        addFieldToPage,
        addFieldBetweenFields,
        addFieldBetweenRows,
        addFieldBetweenNestedRows,
        addFieldBetweenNestedFields,
        moveTopLevelFieldToNested,
        moveNestedFieldToTopLevel,
        moveNestedFieldWithinParent,
        moveFieldToPosition,
        moveFieldToNewRow,
    } = useBuilderActions();

    // Filter out internal field types
    const filteredFieldTypes = useMemo(() => {
        return fieldTypeGroups.filter((fieldType) => {
            return fieldType.handle !== 'internal';
        }).map((fieldType) => {
            // Filter out fields where isPickable is false
            const filteredFields = fieldType.fields.filter((field) => {
                return field.isPickable !== false;
            });

            return {
                ...fieldType,
                fields: filteredFields,
            };
        }).filter((fieldType) => {
            // Only keep field types that have at least one field after filtering
            return fieldType.fields.length > 0;
        });
    }, [fieldTypeGroups]);

    const groupedFieldTypeOptions = useMemo(() => {
        return filteredFieldTypes.map((group) => {
            return {
                value: group.handle,
                label: group.label,
                items: group.fields.map((fieldType) => {
                    return {
                        ...fieldType,
                        groupLabel: group.label,
                    };
                }),
            };
        });
    }, [filteredFieldTypes]);

    // Drag and drop state
    const [activeField, setActiveField] = useState(null);
    const [isDragging, setIsDragging] = useState(false);
    const [showExistingFields, setShowExistingFields] = useState(false);
    const scrollContainerRef = useRef(null);
    const fieldTypeSidebarRef = useRef(null);
    const fieldTypeSidebarScrollTopRef = useRef(0);
    const [scrollHeight, setScrollHeight] = useState(0);
    const builderDevSettings = useMemo(() => {
        if (!import.meta.env.DEV) {
            return null;
        }

        return getDevToolsConfig();
    }, []);
    const showRowAndFieldIds = Boolean(
        builderDevSettings?.enabled && builderDevSettings?.showRowAndFieldIds,
    );
    const showDropzoneRegistryDebugPanel = Boolean(
        builderDevSettings?.enabled && builderDevSettings?.showDropzoneRegistryDebugPanel,
    );

    // Auto-select first page if none selected
    useEffect(() => {
        if (!activePage && pages && pages.length) {
            const firstPage = pages[0];

            router.navigateToPage(firstPage._handle, { replace: true });
        }
    }, [pages, activePage, router]);

    useEffect(() => {
        const node = scrollContainerRef.current;
        if (!node) {
            return undefined;
        }

        const updateSize = () => {
            setScrollHeight(node.clientHeight);
        };

        updateSize();

        let observer;
        if (typeof ResizeObserver !== 'undefined') {
            observer = new ResizeObserver(updateSize);
            observer.observe(node);
        } else {
            window.addEventListener('resize', updateSize);
        }

        return () => {
            if (observer) {
                observer.disconnect();
            } else {
                window.removeEventListener('resize', updateSize);
            }
        };
    }, []);

    const rows = activePage?.rows || [];

    const buildNewFieldForInsert = (fieldType) => {
        if (fieldType?.newField) {
            const existingHandles = [];
            pages.forEach((page) => {
                collectFieldHandlesFromRows(page?.rows || [], existingHandles);
            });

            const newField = prepareNewFieldForInsert({
                ...cloneDeep(fieldType.newField),
                _isNew: true,
            }, existingHandles, fieldType);

            return assignFieldReferences(newField);
        }

        Craft.cp.displayError(Craft.t('formie', 'Field settings are unavailable. Please reload the builder.'));
        return null;
    };
    const announceFieldAdded = (fieldType) => {
        announceFormBuilderStatus(Craft.t('formie', '{label} field added.', {
            label: fieldType?.label || Craft.t('formie', 'Field'),
        }));
    };

    const EmptyDroppableZone = ({ pageIndex }) => {
        const { ref, isDropTarget } = useDroppable({
            id: 'empty-dropzone',
            data: {
                pageIndex,
            },
            collisionDetector: expandedPointerIntersection,
        });
        const [selectedFieldType, setSelectedFieldType] = useState(null);

        const handleAddField = (fieldType) => {
            const newField = buildNewFieldForInsert(fieldType);

            if (!newField) {
                return;
            }

            addFieldToPage(pageIndex, newField);
            announceFieldAdded(fieldType);

            // Reset selection so users can add the same field type again.
            setSelectedFieldType(null);
        };

        return (
            <div
                ref={ref}
                className={cn(
                    'flex items-center justify-center select-none',
                    'rounded-lg border border-dashed',
                    'p-12 m-[10px]',
                    'transition-colors',
                    isDropTarget ? 'border-[#0D99F2] bg-[#e5f5f8]' : 'border-slate-500',
                )}
            >
                <span className={cn('flex flex-col items-center justify-center text-center')}>
                    <div className="size-8 bg-[#60a6fb]/15 rounded-[8px] flex items-center justify-center">
                        <FontAwesomeIcon icon={faPlus} className="text-[#2563eb] size-4" />
                    </div>

                    <p className="my-2 w-full text-[#33475b] truncate text-wrap font-medium text-sm">{Craft.t('formie', 'Add a new field')}</p>
                    <p className="w-full text-gray-500 truncate text-wrap text-xs">{Craft.t('formie', 'Drag and drop a field here, or select one below.')}</p>

                    <div className="form-builder-empty-dropzone-field-select mt-4 flex w-full min-w-0 max-w-[220px] flex-col gap-2">
                        <Combobox
                            items={groupedFieldTypeOptions}
                            value={selectedFieldType}
                            size="sm"
                            onValueChange={handleAddField}
                            itemToStringLabel={(item) => {
                                return item?.groupLabel ? `${item.label} ${item.groupLabel}` : (item?.label ?? '');
                            }}
                            itemToStringValue={(item) => { return item?.type ?? item?.value ?? ''; }}
                        >
                            <ComboboxTrigger
                                render={(
                                    <Button
                                        size="sm"
                                        className="w-full min-w-0 py-1.5 justify-between"
                                    >
                                        <span className="min-w-0 truncate">
                                            {selectedFieldType?.label ?? Craft.t('formie', 'Select a field type')}
                                        </span>
                                        <FontAwesomeIcon icon={faChevronDown} className="size-3 shrink-0 pointer-events-none" />
                                    </Button>
                                )}
                            />

                            <ComboboxContent
                                side="top"
                                className="w-[var(--anchor-width)] min-w-[var(--anchor-width)] max-w-[var(--anchor-width)] overflow-x-clip"
                            >
                                <ComboboxPrimitiveInput
                                    showTrigger={false}
                                    placeholder={Craft.t('formie', 'Search fields')}
                                />
                                <ComboboxEmpty>{Craft.t('formie', 'No fields found.')}</ComboboxEmpty>

                                <ComboboxList>
                                    <ComboboxCollection>
                                        {(group, index) => {
                                            return (
                                                <ComboboxGroup key={group.value}>
                                                    {index > 0 && <ComboboxSeparator />}
                                                    <ComboboxLabel>{group.label}</ComboboxLabel>

                                                    {group.items.map((fieldType) => {
                                                        return (
                                                            <ComboboxItem
                                                                key={fieldType.type}
                                                                value={fieldType}
                                                            >
                                                                <span
                                                                    className={cn(
                                                                        'size-[13px] shrink-0 overflow-hidden',
                                                                        '[&_svg]:size-full!',
                                                                    )}
                                                                    dangerouslySetInnerHTML={{ __html: fieldType.icon }}
                                                                />
                                                                <span className="min-w-0 truncate">{fieldType.label}</span>
                                                            </ComboboxItem>
                                                        );
                                                    })}
                                                </ComboboxGroup>
                                            );
                                        }}
                                    </ComboboxCollection>
                                </ComboboxList>
                            </ComboboxContent>
                        </Combobox>
                    </div>
                </span>
            </div>
        );
    };

    // Rows/fields rendering is defined below to keep component instances stable.
    const Buttons = () => {
        return (
            <div className="py-2">
                <PageButtons page={activePage} pageIndex={activePageIndex} isAnyDragActive={isDragging} />
            </div>
        );
    };

    const handleAddFieldType = (fieldType) => {
        const newField = buildNewFieldForInsert(fieldType);

        if (!newField) {
            return;
        }

        addFieldToPage(activePageIndex, newField);
        announceFieldAdded(fieldType);
        setIsFieldTypeSidebarOpen(false);
    };

    const captureFieldTypeSidebarScroll = () => {
        fieldTypeSidebarScrollTopRef.current = fieldTypeSidebarRef.current?.scrollTop || 0;
    };

    const restoreFieldTypeSidebarScroll = () => {
        const fieldTypeSidebar = fieldTypeSidebarRef.current;

        if (!fieldTypeSidebar) {
            return;
        }

        if (fieldTypeSidebar.scrollTop !== fieldTypeSidebarScrollTopRef.current) {
            fieldTypeSidebar.scrollTop = fieldTypeSidebarScrollTopRef.current;
        }
    };

    useLayoutEffect(() => {
        if (!isDragging) {
            return;
        }

        restoreFieldTypeSidebarScroll();
    }, [isDragging, activeField]);

    const FieldTypeGroup = ({ fieldTypeGroup }) => {
        return (
            <div key={fieldTypeGroup.handle}>
                <h4 className={cn('text-[11px] text-gray-500 uppercase mb-2')}>{fieldTypeGroup.label}</h4>

                <div className={cn('grid grid-cols-2 gap-[5px]')}>
                    {fieldTypeGroup.fields.map((fieldType) => {
                        return (
                            <FieldTypePill
                                key={fieldType.type}
                                fieldType={fieldType}
                                onActivate={handleAddFieldType}
                            />
                        );
                    })}
                </div>
            </div>
        );
    };

    const FieldTypePill = ({ fieldType, onActivate }) => {
        const {
            ref, isDragging: isFieldDragging,
        } = useDraggable({
            id: `field-${fieldType.type}`,
            data: {
                fieldType,
                isNew: true,
            },
            modifiers: [
                SnapTopLeftCornerToCursor,
            ],
        });

        return (
            <div
                ref={ref}
                className={cn(
                    'px-[8px] py-[10px]',
                    'bg-white',
                    'text-[#33475b] text-[13px]',
                    'rounded-sm border border-[#cbd6e2]',
                    'flex items-center h-full',
                    'select-none group outline-none',
                    'transition-opacity',
                    'focus-visible:border-sky-600 focus-visible:shadow-[0_0_0_1px_var(--color-sky-600),0_0_4px_1px_hsl(from_var(--color-sky-600)_h_s_l/0.7)]',
                    isFieldDragging ? 'opacity-60' : '',

                    // Keep grab on hover, but default cursor for the dragged source.
                    isFieldDragging ? '!cursor-default' : 'cursor-grab',
                )}
                role="button"
                tabIndex={0}
                aria-label={Craft.t('formie', 'Add {label}', { label: fieldType.label })}
                onClick={() => {
                    if (!window.matchMedia('(max-width: 1180px)').matches) {
                        return;
                    }

                    if (onActivate) {
                        onActivate(fieldType);
                    }
                }}
                onDoubleClick={() => {
                    if (onActivate) {
                        onActivate(fieldType);
                    }
                }}
                onKeyDown={(event) => {
                    if (event.key !== 'Enter' && event.key !== ' ') {
                        return;
                    }
                    event.preventDefault();
                    if (onActivate) {
                        onActivate(fieldType);
                    }
                }}
            >
                <span className={cn(
                    'size-[16px]',
                    'mr-[5px]',
                    '[&_svg]:size-full',
                )} dangerouslySetInnerHTML={{ __html: fieldType.icon }} />
                <span>{fieldType.label}</span>
                <span className={cn(
                    'ml-auto -mr-[3px]',
                    'text-gray-400',
                    !isDragging && 'opacity-0',
                    !isDragging && 'group-hover:opacity-100',
                    isDragging && 'opacity-0',
                    'transition-opacity',
                    isFieldDragging ? 'opacity-100' : '',
                )}><FontAwesomeIcon icon={faGripDotsVertical} className="size-3" /></span>
            </div>
        );
    };

    const FieldTypePillGhost = () => {
        if (!activeField) { return null; }

        return (
            <div
                className={cn(
                    'w-[150px] h-[42px]',
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
                <span className={cn(
                    'size-[16px]',
                    'mr-[5px]',
                    '[&_svg]:size-full',
                )} dangerouslySetInnerHTML={{ __html: activeField.fieldType.icon }} />

                <span>
                    {activeField.isNew
                        ? activeField.fieldType.label
                        : (activeField.fieldType?.hasLabel === false
                            ? activeField.fieldType.label
                            : (activeField.field?.label || activeField.fieldType.label))}
                </span>

                {activeField.isNew && (
                    <span className={cn(
                        'ml-auto',
                        'text-gray-400',
                    )}><FontAwesomeIcon icon={faGripDotsVertical} /></span>
                )}
            </div>
        );
    };

    const handleDragStart = (event) => {
        const source = event?.operation?.source;
        const fieldData = source?.data?.current ?? source?.data;
        captureFieldTypeSidebarScroll();
        setActiveField(fieldData);
        setIsDragging(true);
    };

    const handleDragEnd = async(event) => {
        const source = event?.operation?.source;
        const target = event?.operation?.target;
        const targetElement = target?.element ?? target?.node?.current ?? null;

        if (target) {
            if (targetElement && targetElement.isConnected === false) {
                setActiveField(null);
                setIsDragging(false);
                return;
            }

            const fieldData = source?.data?.current ?? source?.data;
            const dropData = target?.data?.current ?? target?.data;

            const { fieldType } = fieldData;
            let newField = null;

            if (fieldData.isNew) {
                setActiveField(null);
                setIsDragging(false);
                newField = buildNewFieldForInsert(fieldType);

                if (!newField) {
                    return;
                }
            }

            const overId = String(target.id);
            const nestedTargetType = dropData?.targetType;
            const isNestedTarget = (
                nestedTargetType === 'nested-empty'
                || nestedTargetType === 'nested-row'
                || nestedTargetType === 'nested-field'
                || overId.startsWith('nested-empty|')
                || overId.startsWith('nested-row|')
                || overId.startsWith('nested-field|')
            );

            if (isNestedTarget) {
                let targetType = nestedTargetType;
                let targetPageIndex = Number(dropData?.pageIndex);
                let targetRowIndex = Number(dropData?.rowIndex);
                let targetParentFieldIndex = Number(dropData?.fieldIndex);
                let dropNestedRowIndex = Number(dropData?.nestedRowIndex);
                let dropNestedFieldIndex = Number(dropData?.nestedFieldIndex);

                if (!targetType || Number.isNaN(targetPageIndex) || Number.isNaN(targetRowIndex) || Number.isNaN(targetParentFieldIndex)) {
                    const parts = overId.split('|');
                    targetType = parts[0];
                    const [, toPageIndex, toRowIndex, toFieldIndex, nestedRowIndex, nestedFieldIndex] = parts;
                    targetPageIndex = Number(toPageIndex);
                    targetRowIndex = Number(toRowIndex);
                    targetParentFieldIndex = Number(toFieldIndex);
                    dropNestedRowIndex = Number(nestedRowIndex);
                    dropNestedFieldIndex = Number(nestedFieldIndex);
                }

                if (
                    Number.isNaN(targetPageIndex)
                    || Number.isNaN(targetRowIndex)
                    || Number.isNaN(targetParentFieldIndex)
                ) {
                    setActiveField(null);
                    setIsDragging(false);
                    return;
                }

                const targetNestedRows = get(
                    pages,
                    `${targetPageIndex}.rows.${targetRowIndex}.fields.${targetParentFieldIndex}.rows`,
                ) || [];
                const targetParentField = get(
                    pages,
                    `${targetPageIndex}.rows.${targetRowIndex}.fields.${targetParentFieldIndex}`,
                );
                const targetParentFieldId = targetParentField?._id || null;
                if (dropData?.containerFieldId && targetParentFieldId && dropData.containerFieldId !== targetParentFieldId) {
                    setActiveField(null);
                    setIsDragging(false);
                    return;
                }
                const targetParentType = get(pages, `${targetPageIndex}.rows.${targetRowIndex}.fields.${targetParentFieldIndex}.type`);
                const targetParentFieldType = getFieldTypeByType(targetParentType);
                const targetIsRepeater = Boolean(targetParentFieldType?.isRepeatableParentField);
                const allowedNestedFieldTypes = Array.isArray(targetParentFieldType?.data?.nestedLayoutBuilder?.allowedFieldTypes)
                    ? targetParentFieldType.data.nestedLayoutBuilder.allowedFieldTypes
                    : [];

                const sourceIsNested = fieldData.source === 'nested';
                const sourceIsTopLevel = fieldData.source === 'top-level';
                const sourceIsRepeaterNested = sourceIsNested && Boolean(fieldData.isRepeatableParentField);
                const sameNestedParent = sourceIsNested
                    && fieldData.pageIndex === targetPageIndex
                    && fieldData.rowIndex === targetRowIndex
                    && fieldData.fieldIndex === targetParentFieldIndex;

                if (!canDropInNestedContainer({
                    activeData: fieldData,
                    isRepeater: targetIsRepeater,
                    pageIndex: targetPageIndex,
                    rowIndex: targetRowIndex,
                    fieldIndex: targetParentFieldIndex,
                    allowedFieldTypes: allowedNestedFieldTypes,
                })) {
                    setActiveField(null);
                    setIsDragging(false);
                    return;
                }

                if (!isAllowedNestedTargetDrop({
                    fieldData,
                    targetIsRepeater,
                    isSameNestedParent: sameNestedParent,
                    allowedFieldTypes: allowedNestedFieldTypes,
                })) {
                    setActiveField(null);
                    setIsDragging(false);
                    return;
                }

                if (fieldData.isNew) {
                    if (targetType === 'nested-empty' || targetType === 'nested-row') {
                        const dropRowIndex = dropNestedRowIndex;
                        if (!Number.isNaN(dropRowIndex) && (dropRowIndex < -1 || dropRowIndex >= targetNestedRows.length)) {
                            setActiveField(null);
                            setIsDragging(false);
                            return;
                        }

                        addFieldBetweenNestedRows(
                            targetPageIndex,
                            targetRowIndex,
                            targetParentFieldIndex,
                            Number.isNaN(dropRowIndex) ? -1 : dropRowIndex,
                            newField,
                        );
                        announceFieldAdded(fieldType);
                    }

                    if (targetType === 'nested-field') {
                        const targetNestedRowIndex = dropNestedRowIndex;
                        const targetNestedFieldIndex = dropNestedFieldIndex;
                        const targetNestedFields = get(targetNestedRows, `${targetNestedRowIndex}.fields`) || [];

                        if (
                            !Number.isNaN(targetNestedRowIndex)
                            && !Number.isNaN(targetNestedFieldIndex)
                            && targetNestedRowIndex >= 0
                            && targetNestedRowIndex < targetNestedRows.length
                            && targetNestedFieldIndex >= -1
                            && targetNestedFieldIndex < targetNestedFields.length
                        ) {
                            addFieldBetweenNestedFields(
                                targetPageIndex,
                                targetRowIndex,
                                targetParentFieldIndex,
                                targetNestedRowIndex,
                                targetNestedFieldIndex,
                                newField,
                            );
                            announceFieldAdded(fieldType);
                        }
                    }
                } else if (sourceIsTopLevel) {
                    if (targetType === 'nested-empty' || targetType === 'nested-row') {
                        const dropRowIndex = dropNestedRowIndex;
                        if (!Number.isNaN(dropRowIndex) && (dropRowIndex < -1 || dropRowIndex >= targetNestedRows.length)) {
                            setActiveField(null);
                            setIsDragging(false);
                            return;
                        }

                        moveTopLevelFieldToNested(
                            fieldData.pageIndex,
                            fieldData.rowIndex,
                            fieldData.fieldIndex,
                            targetPageIndex,
                            targetRowIndex,
                            targetParentFieldIndex,
                            Number.isNaN(dropRowIndex) ? -1 : dropRowIndex,
                            -1,
                            true,
                        );
                    }

                    if (targetType === 'nested-field') {
                        const targetNestedRowIndex = dropNestedRowIndex;
                        const targetNestedFieldIndex = dropNestedFieldIndex;
                        const targetNestedFields = get(targetNestedRows, `${targetNestedRowIndex}.fields`) || [];
                        if (!Number.isNaN(targetNestedRowIndex) && !Number.isNaN(targetNestedFieldIndex)) {
                            if (
                                targetNestedRowIndex < 0
                                || targetNestedRowIndex >= targetNestedRows.length
                                || targetNestedFieldIndex < -1
                                || targetNestedFieldIndex >= targetNestedFields.length
                            ) {
                                setActiveField(null);
                                setIsDragging(false);
                                return;
                            }

                            moveTopLevelFieldToNested(
                                fieldData.pageIndex,
                                fieldData.rowIndex,
                                fieldData.fieldIndex,
                                targetPageIndex,
                                targetRowIndex,
                                targetParentFieldIndex,
                                targetNestedRowIndex,
                                targetNestedFieldIndex,
                                false,
                            );
                        }
                    }
                } else if (sourceIsNested && sameNestedParent) {
                    if (targetType === 'nested-empty' || targetType === 'nested-row') {
                        const dropRowIndex = dropNestedRowIndex;
                        if (!Number.isNaN(dropRowIndex) && (dropRowIndex < -1 || dropRowIndex >= targetNestedRows.length)) {
                            setActiveField(null);
                            setIsDragging(false);
                            return;
                        }

                        moveNestedFieldWithinParent(
                            fieldData.pageIndex,
                            fieldData.rowIndex,
                            fieldData.fieldIndex,
                            fieldData.nestedRowIndex,
                            fieldData.nestedFieldIndex,
                            Number.isNaN(dropRowIndex) ? -1 : dropRowIndex,
                            -1,
                            true,
                        );
                    }

                    if (targetType === 'nested-field') {
                        const targetNestedRowIndex = dropNestedRowIndex;
                        const targetNestedFieldIndex = dropNestedFieldIndex;
                        const targetNestedFields = get(targetNestedRows, `${targetNestedRowIndex}.fields`) || [];
                        if (!Number.isNaN(targetNestedRowIndex) && !Number.isNaN(targetNestedFieldIndex)) {
                            if (
                                targetNestedRowIndex < 0
                                || targetNestedRowIndex >= targetNestedRows.length
                                || targetNestedFieldIndex < -1
                                || targetNestedFieldIndex >= targetNestedFields.length
                            ) {
                                setActiveField(null);
                                setIsDragging(false);
                                return;
                            }

                            moveNestedFieldWithinParent(
                                fieldData.pageIndex,
                                fieldData.rowIndex,
                                fieldData.fieldIndex,
                                fieldData.nestedRowIndex,
                                fieldData.nestedFieldIndex,
                                targetNestedRowIndex,
                                targetNestedFieldIndex,
                                false,
                            );
                        }
                    }
                }
            } else if (overId === 'empty-dropzone') {
                if (fieldData.source === 'nested') {
                    if (fieldData.isRepeatableParentField) {
                        setActiveField(null);
                        setIsDragging(false);
                        return;
                    }

                    moveNestedFieldToTopLevel(
                        fieldData.pageIndex,
                        fieldData.rowIndex,
                        fieldData.fieldIndex,
                        fieldData.nestedRowIndex,
                        fieldData.nestedFieldIndex,
                        dropData.pageIndex,
                        -1,
                        -1,
                        true,
                    );
                } else {
                    addFieldToPage(dropData.pageIndex, newField);
                    announceFieldAdded(fieldType);
                }
            } else if (overId.startsWith('page-tab-') && !fieldData.isNew) {
                if (fieldData.source === 'nested') {
                    if (fieldData.isRepeatableParentField) {
                        setActiveField(null);
                        setIsDragging(false);
                        return;
                    }

                    const toPageIndex = dropData.pageIndex;
                    const targetRows = pages[toPageIndex]?.rows || [];
                    const toRowDropzoneIndex = targetRows.length - 1;

                    moveNestedFieldToTopLevel(
                        fieldData.pageIndex,
                        fieldData.rowIndex,
                        fieldData.fieldIndex,
                        fieldData.nestedRowIndex,
                        fieldData.nestedFieldIndex,
                        toPageIndex,
                        toRowDropzoneIndex,
                        -1,
                        true,
                    );
                    setActiveField(null);
                    setIsDragging(false);
                    return;
                }

                const toPageIndex = dropData.pageIndex;
                const targetRows = pages[toPageIndex]?.rows || [];
                const toRowDropzoneIndex = targetRows.length - 1;
                moveFieldToNewRow(
                    fieldData.pageIndex,
                    fieldData.rowIndex,
                    fieldData.fieldIndex,
                    toPageIndex,
                    toRowDropzoneIndex,
                );
            } else if (overId.startsWith('field')) {
                if (fieldData.isNew) {
                    addFieldBetweenFields(dropData.pageIndex, dropData.rowIndex, dropData.fieldIndex, newField);
                    announceFieldAdded(fieldType);
                } else if (fieldData.source === 'nested') {
                    if (fieldData.isRepeatableParentField) {
                        setActiveField(null);
                        setIsDragging(false);
                        return;
                    }

                    moveNestedFieldToTopLevel(
                        fieldData.pageIndex,
                        fieldData.rowIndex,
                        fieldData.fieldIndex,
                        fieldData.nestedRowIndex,
                        fieldData.nestedFieldIndex,
                        dropData.pageIndex,
                        dropData.rowIndex,
                        dropData.fieldIndex,
                        false,
                    );
                } else {
                    moveFieldToPosition(
                        fieldData.pageIndex,
                        fieldData.rowIndex,
                        fieldData.fieldIndex,
                        dropData.pageIndex,
                        dropData.rowIndex,
                        dropData.fieldIndex,
                    );
                }
            } else if (overId.startsWith('row')) {
                if (fieldData.isNew) {
                    addFieldBetweenRows(dropData.pageIndex, dropData.rowIndex, newField);
                    announceFieldAdded(fieldType);
                } else if (fieldData.source === 'nested') {
                    if (fieldData.isRepeatableParentField) {
                        setActiveField(null);
                        setIsDragging(false);
                        return;
                    }

                    moveNestedFieldToTopLevel(
                        fieldData.pageIndex,
                        fieldData.rowIndex,
                        fieldData.fieldIndex,
                        fieldData.nestedRowIndex,
                        fieldData.nestedFieldIndex,
                        dropData.pageIndex,
                        dropData.rowIndex,
                        -1,
                        true,
                    );
                } else {
                    moveFieldToNewRow(
                        fieldData.pageIndex,
                        fieldData.rowIndex,
                        fieldData.fieldIndex,
                        dropData.pageIndex,
                        dropData.rowIndex,
                    );
                }
            }
        }

        setActiveField(null);
        setIsDragging(false);
    };

    const handleDragOver = (event) => {

    };

    const dragDropPlugins = useCallback((defaults) => {
        const plugins = activeField?.isNew
            ? defaults.filter((plugin) => { return plugin !== AutoScroller; })
            : defaults;

        return plugins.map((plugin) => {
            if (plugin === Cursor) {
                return Cursor.configure({
                    cursor: 'default',
                });
            }

            return plugin;
        });
    }, [activeField?.isNew]);

    return (
        <DragDropProvider
            sensors={sensors}
            plugins={dragDropPlugins}
            onDragStart={handleDragStart}
            onDragEnd={handleDragEnd}
            onDragOver={handleDragOver}
        >
            <div className={cn(
                'form-builder-field-builder',
                'h-[calc(100vh-183px)]',
                'overflow-hidden',
            )} data-field-type-sidebar-open={isFieldTypeSidebarOpen ? 'true' : 'false'}>
                <div className={cn(
                    'flex flex-1 h-full',
                )}>
                    <div className={cn(
                        'flex-1 flex flex-col min-h-0 overflow-hidden',
                        'relative',
                    )}>
                        {/* Header: Page Tabs */}
                        <div className={cn(
                            'absolute top-0 left-0 w-full h-[55px] z-1',
                            'bg-white',
                            'shadow-[0_0_0_1px_var(--gray-100),0_1px_5px__hsl(from_var(--gray-200)_h_s_l_/_40%)]',
                        )}>
                            <PageTabs isAnyDragActive={isDragging} />
                        </div>

                        <div
                            ref={scrollContainerRef}
                            className={cn(
                                'absolute inset-0 top-[55px]',
                                'flex flex-col',
                            )}
                        >
                            <div className={cn(
                                'overflow-hidden',
                            )}>
                                {activePage && activePage.rows && activePage.rows.length > 0 ? (

                                    <div className="form-builder-canvas-scroll overflow-y-auto h-full p-[10px]">
                                        <Rows
                                            page={activePage}
                                            pageIndex={activePageIndex}
                                            showStructureIds={showRowAndFieldIds}
                                        />
                                        <Buttons />
                                    </div>
                                ) : (
                                    <EmptyDroppableZone pageIndex={activePageIndex} />
                                )}
                            </div>
                        </div>
                    </div>

                    <button
                        type="button"
                        className="form-builder-field-type-backdrop"
                        aria-label={Craft.t('formie', 'Close field types')}
                        onClick={() => {
                            setIsFieldTypeSidebarOpen(false);
                        }}
                    />

                    <div className={cn(
                        'form-builder-field-type-sidebar',
                        'w-[350px]',
                        'flex-shrink-0 flex flex-col',
                        'min-h-0',
                        'overflow-x-hidden overflow-y-auto',
                        'relative z-1',
                        'bg-gray-50',
                        'p-4',
                        'shadow-[0_0_0_1px_var(--gray-200),inset_10px_0_10px_-10px_hsl(from_var(--gray-200)_h_s_l_/_50%)]',
                    )} ref={fieldTypeSidebarRef}>
                        <div className="form-builder-field-type-sidebar-header">
                            <div>
                                <h3 className="text-sm font-semibold text-[#33475b]">{Craft.t('formie', 'Field types')}</h3>
                                <p className="m-0 text-xs text-gray-500">{Craft.t('formie', 'Add fields to the active page.')}</p>
                            </div>

                            <button
                                type="button"
                                className="rounded-sm p-1 text-gray-400 transition-colors hover:text-[#33475b] focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-600"
                                aria-label={Craft.t('formie', 'Close field types')}
                                onClick={() => {
                                    setIsFieldTypeSidebarOpen(false);
                                }}
                            >
                                <FontAwesomeIcon icon={faXmark} className="size-4" />
                            </button>
                        </div>

                        <h4 className="text-[11px] text-gray-500 uppercase mb-2">Existing Fields</h4>

                        <div className={cn('mb-4 border-b border-gray-150 pb-4')}>
                            <Button
                                variant="dashed"
                                onClick={() => {
                                    setShowExistingFields(true);
                                    setIsFieldTypeSidebarOpen(false);
                                }}
                            >
                                <FontAwesomeIcon icon={faPlus} className="size-3" />
                                {Craft.t('formie', 'Add existing fields')}
                            </Button>
                        </div>

                        <div className={cn('flex flex-col gap-4')}>
                            {filteredFieldTypes.map((fieldTypeGroup) => {
                                return (
                                    <FieldTypeGroup key={fieldTypeGroup.handle} fieldTypeGroup={fieldTypeGroup} />
                                );
                            })}
                        </div>
                    </div>
                </div>
            </div>

            {showExistingFields && (
                <ExistingFields onClose={() => { return setShowExistingFields(false); }} />
            )}

            <DragOverlay dropAnimation={null}>
                <FieldTypePillGhost />
            </DragOverlay>

            {showDropzoneRegistryDebugPanel && (
                <FieldBuilderDnDDebugPanel
                    pageIndex={activePageIndex}
                    page={activePage}
                />
            )}

        </DragDropProvider>
    );
}

const RowDropzone = ({ id, pageIndex, rowIndex }) => {
    const { source } = useDragOperation();
    const activeData = source?.data?.current ?? source?.data;
    const canDropTopLevel = canDropToTopLevel({ activeData });
    const { ref, isDropTarget } = useDroppable({
        id,
        data: {
            pageIndex,
            rowIndex,
        },
        collisionDetector: expandedPointerIntersection,
        disabled: !canDropTopLevel,
    });

    return (
        <div className={cn(
            'relative',
            'h-0 w-full',
            'px-[12px]',
            'transform translate-y-[-4px]',
            'transition-opacity',
            source && canDropTopLevel ? 'opacity-100 z-10' : 'opacity-0 -z-1',
        )}>
            <div
                className={cn(
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

const FieldDropzone = memo(({
    id, pageIndex, rowIndex, fieldIndex, isDisabled,
}) => {
    const { source } = useDragOperation();
    const activeData = source?.data?.current ?? source?.data;
    const canDropTopLevel = canDropToTopLevel({ activeData });
    const resolvedDisabled = isDisabled || !canDropTopLevel;
    const { ref, isDropTarget } = useDroppable({
        id,
        data: {
            pageIndex,
            rowIndex,
            fieldIndex,
        },
        collisionDetector: expandedPointerIntersection,
        disabled: resolvedDisabled,
    });

    return (
        <div className={cn(
            'relative',
            'w-0',
            'pb-[12px] pt-[34px]',
            'transform translate-x-[-4px]',
            'transition-opacity',
            source && !resolvedDisabled ? 'opacity-100 z-10' : 'opacity-0 -z-1',
        )}>
            <div
                className={cn(
                    'w-[8px]',
                    'h-full',
                    'border rounded-sm',
                    isDropTarget ? 'border-[#0d99f2] bg-[#0d99f2]' : 'border-[#6ec2f7] bg-[#e5f5f8]',
                )}
                ref={ref}
            />
        </div>
    );
});

const Rows = memo(({
    page, pageIndex, showStructureIds = false,
}) => {
    const rows = page.rows || [];
    return rows.map((row, rowIndex) => {
        return (
            <React.Fragment key={row._id}>
                {rowIndex === 0 && (
                    <RowDropzone
                        id={`row-${pageIndex}-before-${row._id}`}
                        pageIndex={pageIndex}
                        rowIndex={-1}
                    />
                )}

                <Row
                    pageIndex={pageIndex}
                    rowIndex={rowIndex}
                    row={row}
                    showStructureIds={showStructureIds}
                />

                {rowIndex < rows.length - 1 && (
                    <RowDropzone
                        id={`row-${pageIndex}-after-${row._id}`}
                        pageIndex={pageIndex}
                        rowIndex={rowIndex}
                    />
                )}

                {rowIndex === rows.length - 1 && (
                    <RowDropzone
                        id={`row-${pageIndex}-after-${row._id}`}
                        pageIndex={pageIndex}
                        rowIndex={rows.length - 1}
                    />
                )}
            </React.Fragment>
        );
    });
});

const Row = memo(({
    pageIndex, rowIndex, row, showStructureIds = false,
}) => {
    return (
        <div
            data-row-id={row._id}
            className={cn(
                'flex w-full min-w-0 relative',
            )}>
            {showStructureIds && (
                <div className="pointer-events-none absolute left-2 top-1 z-20 rounded bg-slate-900/75 px-1.5 py-0.5 text-[10px] leading-none text-white">
                    {`row:${row._id || 'unknown'}`}
                </div>
            )}
            <Fields
                pageIndex={pageIndex}
                rowIndex={rowIndex}
                row={row}
                showStructureIds={showStructureIds}
            />
        </div>
    );
});

const Fields = memo(({
    pageIndex, rowIndex, row, showStructureIds = false,
}) => {
    const isRowFull = row.fields.length >= MAX_FIELDS_PER_ROW;

    return row.fields.map((field, fieldIndex) => {
        return (
            <div
                key={field._id}
                data-field-id={field._id}
                className={cn(
                    'flex-1 basis-0 min-w-0 flex min-w-0 relative',
                )}
            >
                {showStructureIds && (
                    <div className="pointer-events-none absolute right-2 top-1 z-20 rounded bg-indigo-700/80 px-1.5 py-0.5 text-[10px] leading-none text-white">
                        {`field:${field._id || 'unknown'}`}
                    </div>
                )}
                {fieldIndex === 0 && (
                    <FieldDropzone
                        id={`field-${pageIndex}-${row._id}-before-${field._id}`}
                        pageIndex={pageIndex}
                        rowIndex={rowIndex}
                        fieldIndex={-1}
                        isDisabled={isRowFull}
                    />
                )}

                <Field field={field} pageIndex={pageIndex} rowIndex={rowIndex} fieldIndex={fieldIndex} />

                {fieldIndex < row.fields.length - 1 && (
                    <FieldDropzone
                        id={`field-${pageIndex}-${row._id}-after-${field._id}`}
                        pageIndex={pageIndex}
                        rowIndex={rowIndex}
                        fieldIndex={fieldIndex}
                        isDisabled={isRowFull}
                    />
                )}

                {fieldIndex === row.fields.length - 1 && (
                    <FieldDropzone
                        id={`field-${pageIndex}-${row._id}-after-${field._id}`}
                        pageIndex={pageIndex}
                        rowIndex={rowIndex}
                        fieldIndex={row.fields.length - 1}
                        isDisabled={isRowFull}
                    />
                )}
            </div>
        );
    });
});

export { FieldBuilder };
