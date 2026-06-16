import {
    useCallback,
    useEffect,
    useMemo,
    useRef,
    startTransition,
    useState,
} from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faArrowDown, faArrowUp, faEllipsis, faPlus, faTrash,
} from '@fortawesome/pro-solid-svg-icons';

import {
    Button,
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
    Input,
    Lightswitch,
} from '@verbb/plugin-kit-react/components';
import { cn } from '@verbb/plugin-kit-react/utils';

import {
    DragDropProvider,
    DragOverlay,
    KeyboardSensor,
    PointerSensor,
    useDroppable,
} from '@dnd-kit/react';
import { CollisionPriority } from '@dnd-kit/abstract';
import { RestrictToVerticalAxis } from '@dnd-kit/abstract/modifiers';
import { useSortable } from '@dnd-kit/react/sortable';

import { SortableKeyboardPlugin, OptimisticSortingPlugin } from '@dnd-kit/dom/sortable';

import { DragHandle } from '@field-palette/components/DragHandle';
import {
    createGroup,
    fieldId,
    fieldEnabledInputId,
    fieldLabelInputId,
    findFieldLocation,
    getFieldListForLocation,
    groupNameInputId,
    moveFieldByOffset,
    moveFieldToGroup,
    moveGroupByOffset,
    serializePaletteForSave,
    sortableFieldGroupKey,
    UNASSIGNED_SORTABLE_GROUP,
} from '@field-palette/utils/paletteState';
import { applyMoveEventToPalette, sortableDropZoneId } from '@field-palette/utils/paletteMove';
import { useCpFormPayloadSync } from '@utils';

const FIELD_DRAG_MODIFIERS = [RestrictToVerticalAxis];
const FIELD_SORTABLE_PLUGINS = [SortableKeyboardPlugin, OptimisticSortingPlugin];

const resolveFieldFromSource = (palette, source) => {
    const data = source?.data?.current ?? source?.data;

    if (!data || data.type !== 'field') {
        return null;
    }

    const location = findFieldLocation(palette, data.fieldClass);

    if (!location) {
        return null;
    }

    return getFieldListForLocation(palette, location)[location.index] ?? null;
};

function FieldRowDragPreview({ field }) {
    if (!field) {
        return null;
    }

    return (
        <div className="formie-field-palette-drag-ghost">
            <DragHandle
                handleRef={() => {}}
                disabled
                ariaLabel={Craft.t('formie', 'Dragging field')}
            />
            <div className="formie-field-palette-field-label">
                <span className="formie-field-palette-field-name">{field.defaultLabel}</span>
                <span className="formie-field-palette-field-default">{field.fieldClass}</span>
            </div>
        </div>
    );
}

function FieldPaletteDragGhost({ activeDrag }) {
    if (!activeDrag || activeDrag.type !== 'field') {
        return null;
    }

    return (
        <FieldRowDragPreview field={activeDrag.field} />
    );
}

const clonePalette = (palette) => {
    return {
        groups: (palette.groups || []).map((group) => {
            return {
                ...group,
                fields: (group.fields || []).map((field) => { return { ...field }; }),
            };
        }),
        unassigned: (palette.unassigned || []).map((field) => { return { ...field }; }),
    };
};

function GroupBlock({
    group,
    groupIndex,
    groupCount,
    canEdit,
    useDnd,
    onRename,
    onDelete,
    onMoveGroup,
    onMoveField,
    onMoveFieldToGroup,
    onToggleEnabled,
    onLabelChange,
    groups,
}) {
    const canMoveUp = groupIndex > 0;
    const canMoveDown = groupIndex < groupCount - 1;

    return (
        <section
            data-palette-group={group.uid}
            className="formie-field-palette-group"
        >
            <div className="formie-field-palette-group-header">
                <span className="formie-field-palette-field-handle-spacer" aria-hidden="true" />

                <div className="formie-field-palette-group-name">
                    {canEdit ? (
                        <Input
                            id={groupNameInputId(group.uid)}
                            name={groupNameInputId(group.uid)}
                            autoComplete="off"
                            value={group.name}
                            onChange={(event) => { onRename(group.uid, String(event.target.value ?? '')); }}
                        />
                    ) : (
                        <strong>{group.name}</strong>
                    )}
                </div>

                <span className="formie-field-palette-header-spacer" aria-hidden="true" />

                {canEdit ? (
                    <div className="formie-field-palette-row-actions">
                        <DropdownMenu size="sm">
                            <DropdownMenuTrigger
                                render={(
                                    <Button
                                        type="button"
                                        variant="none"
                                        size="xs"
                                        className="formie-field-palette-menu-trigger"
                                        aria-label={Craft.t('formie', 'Actions for {name}', { name: group.name })}
                                    />
                                )}
                            >
                                <FontAwesomeIcon icon={faEllipsis} className="size-3.5" />
                            </DropdownMenuTrigger>

                            <DropdownMenuContent align="end" className="min-w-[160px]">
                                <DropdownMenuItem
                                    disabled={!canMoveUp}
                                    onClick={() => { onMoveGroup(group.uid, -1); }}
                                >
                                    <FontAwesomeIcon icon={faArrowUp} />
                                    {Craft.t('formie', 'Move up')}
                                </DropdownMenuItem>

                                <DropdownMenuItem
                                    disabled={!canMoveDown}
                                    onClick={() => { onMoveGroup(group.uid, 1); }}
                                >
                                    <FontAwesomeIcon icon={faArrowDown} />
                                    {Craft.t('formie', 'Move down')}
                                </DropdownMenuItem>

                                <DropdownMenuSeparator />

                                <DropdownMenuItem
                                    className="text-error focus:text-error"
                                    onClick={() => { onDelete(group.uid); }}
                                >
                                    <FontAwesomeIcon icon={faTrash} />
                                    {Craft.t('formie', 'Delete')}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                ) : (
                    <span className="formie-field-palette-header-spacer" aria-hidden="true" />
                )}
            </div>

            <FieldDropZone
                id={sortableDropZoneId(group.uid)}
                canEdit={canEdit && useDnd}
                isEmpty={!(group.fields || []).length}
                emptyLabel={Craft.t('formie', 'Drag fields here or move them from another group.')}
            >
                {(group.fields || []).map((field, fieldIndex) => {
                    return (
                        <SortableFieldRow
                            key={field.fieldClass}
                            field={field}
                            fieldIndex={fieldIndex}
                            groupUid={group.uid}
                            fieldCount={(group.fields || []).length}
                            groups={groups}
                            canEdit={canEdit}
                            useDnd={useDnd}
                            onToggleEnabled={onToggleEnabled}
                            onLabelChange={onLabelChange}
                            onMoveField={onMoveField}
                            onMoveFieldToGroup={onMoveFieldToGroup}
                        />
                    );
                })}
            </FieldDropZone>
        </section>
    );
}

function SortableFieldRow({
    field,
    fieldIndex,
    groupUid,
    fieldCount,
    groups,
    canEdit,
    useDnd,
    onToggleEnabled,
    onLabelChange,
    onMoveField,
    onMoveFieldToGroup,
}) {
    const sortableId = fieldId(field.fieldClass);
    const sortableGroup = sortableFieldGroupKey(groupUid);
    const {
        ref, handleRef, isDragSource,
    } = useSortable({
        id: sortableId,
        index: fieldIndex,
        group: sortableGroup,
        type: 'palette-field',
        disabled: !canEdit || !useDnd,
        accept: (draggable) => { return draggable.type === 'palette-field'; },
        modifiers: FIELD_DRAG_MODIFIERS,
        transition: null,
        plugins: FIELD_SORTABLE_PLUGINS,
        data: {
            type: 'field',
            fieldClass: field.fieldClass,
            groupUid,
        },
    });

    const canMoveUp = fieldIndex > 0;
    const canMoveDown = fieldIndex < fieldCount - 1;
    const moveTargets = [
        ...(groups || []).filter((group) => { return group.uid !== groupUid; }).map((group) => {
            return {
                label: group.name,
                value: group.uid,
            };
        }),
    ];

    if (groupUid !== null) {
        moveTargets.push({
            label: Craft.t('formie', 'Unassigned'),
            value: UNASSIGNED_SORTABLE_GROUP,
        });
    }

    return (
        <div
            ref={ref}
            className={cn(
                'formie-field-palette-field-row',
                isDragSource && 'is-drag-placeholder',
            )}
        >
            {canEdit ? (
                <DragHandle
                    handleRef={handleRef}
                    disabled={!useDnd}
                    ariaLabel={Craft.t('formie', 'Drag to reorder field')}
                />
            ) : (
                <span className="formie-field-palette-field-handle-spacer" aria-hidden="true" />
            )}

            <div className="formie-field-palette-field-label">
                <span className="formie-field-palette-field-name">{field.defaultLabel}</span>
                <span className="formie-field-palette-field-default">{field.fieldClass}</span>
            </div>

            <div className="formie-field-palette-field-alias">
                {canEdit && !isDragSource ? (
                    <Input
                        id={fieldLabelInputId(field.fieldClass)}
                        name={fieldLabelInputId(field.fieldClass)}
                        autoComplete="off"
                        value={field.label || ''}
                        placeholder={field.defaultLabel}
                        onChange={(event) => { onLabelChange(field.fieldClass, String(event.target.value ?? '')); }}
                    />
                ) : (
                    <span>{field.label || field.defaultLabel}</span>
                )}
            </div>

            <div className="formie-field-palette-field-enabled">
                {canEdit && !isDragSource ? (
                    <Lightswitch
                        id={fieldEnabledInputId(field.fieldClass)}
                        name={fieldEnabledInputId(field.fieldClass)}
                        checked={field.enabled !== false}
                        disabled={!canEdit}
                        onCheckedChange={(checked) => { onToggleEnabled(field.fieldClass, checked); }}
                        aria-label={Craft.t('formie', 'Enable {label}', { label: field.defaultLabel })}
                    />
                ) : null}
            </div>

            {canEdit && !isDragSource ? (
                <div className="formie-field-palette-row-actions">
                    <DropdownMenu size="sm">
                        <DropdownMenuTrigger
                            render={(
                                <Button
                                    type="button"
                                    variant="none"
                                    size="xs"
                                    className="formie-field-palette-menu-trigger"
                                    aria-label={Craft.t('formie', 'Actions for {label}', { label: field.defaultLabel })}
                                />
                            )}
                        >
                            <FontAwesomeIcon icon={faEllipsis} className="size-3.5" />
                        </DropdownMenuTrigger>

                        <DropdownMenuContent align="end" className="min-w-[180px]">
                            <DropdownMenuItem
                                disabled={!canMoveUp}
                                onClick={() => { onMoveField(field.fieldClass, -1); }}
                            >
                                <FontAwesomeIcon icon={faArrowUp} />
                                {Craft.t('formie', 'Move up')}
                            </DropdownMenuItem>

                            <DropdownMenuItem
                                disabled={!canMoveDown}
                                onClick={() => { onMoveField(field.fieldClass, 1); }}
                            >
                                <FontAwesomeIcon icon={faArrowDown} />
                                {Craft.t('formie', 'Move down')}
                            </DropdownMenuItem>

                            {moveTargets.length ? (
                                <>
                                    <DropdownMenuSeparator />

                                    {moveTargets.map((target) => {
                                        return (
                                            <DropdownMenuItem
                                                key={target.value}
                                                onClick={() => {
                                                    onMoveFieldToGroup(
                                                        field.fieldClass,
                                                        target.value === UNASSIGNED_SORTABLE_GROUP ? null : target.value,
                                                    );
                                                }}
                                            >
                                                {Craft.t('formie', 'Move to {name}', { name: target.label })}
                                            </DropdownMenuItem>
                                        );
                                    })}
                                </>
                            ) : null}
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            ) : null}
        </div>
    );
}

function FieldDropZone({
    id,
    children,
    canEdit,
    isEmpty,
    emptyLabel,
}) {
    const { ref } = useDroppable({
        id,
        type: 'palette-group-drop',
        collisionPriority: CollisionPriority.Low,
        disabled: !canEdit || !isEmpty,
    });

    if (!isEmpty) {
        return (
            <div className="formie-field-palette-field-list">
                {children}
            </div>
        );
    }

    if (!canEdit) {
        return (
            <div className="formie-field-palette-field-list formie-field-palette-field-list--empty">
                <div className="formie-field-palette-empty">{emptyLabel}</div>
            </div>
        );
    }

    return (
        <div
            ref={ref}
            className={cn(
                'formie-field-palette-field-list',
                'formie-field-palette-field-list--empty',
            )}
        >
            {children}
            <div className="formie-field-palette-empty">{emptyLabel}</div>
        </div>
    );
}

export function FieldPaletteApp({ settings, onPayloadChange = null }) {
    const canEdit = settings.canEdit !== false;
    const [palette, setPalette] = useState(() => { return clonePalette(settings.palette || { groups: [], unassigned: [] }); });
    const [isDndHydrated, setIsDndHydrated] = useState(false);
    const [activeDrag, setActiveDrag] = useState(null);
    const paletteRef = useRef(palette);
    const previousPaletteRef = useRef(null);
    const groupsContainerRef = useRef(null);
    const useDnd = canEdit && isDndHydrated;

    const sensors = useMemo(() => {
        return [
            PointerSensor.configure({ activationConstraint: { delay: 0, tolerance: 5 } }),
            KeyboardSensor,
        ];
    }, []);

    useEffect(() => {
        paletteRef.current = palette;
    }, [palette]);

    const palettePayload = useMemo(() => {
        return serializePaletteForSave(palette);
    }, [palette]);

    useCpFormPayloadSync({
        inputId: settings.payloadInputId,
        enabled: canEdit && !onPayloadChange,
        payload: palettePayload,
    });

    useEffect(() => {
        if (!onPayloadChange) {
            return;
        }

        onPayloadChange(palettePayload);
    }, [onPayloadChange, palettePayload]);

    useEffect(() => {
        if (!canEdit) {
            setIsDndHydrated(false);
            return undefined;
        }

        let idleId = null;
        let timeoutId = null;
        const enableDnd = () => { setIsDndHydrated(true); };

        if (typeof window !== 'undefined' && typeof window.requestIdleCallback === 'function') {
            idleId = window.requestIdleCallback(enableDnd, { timeout: 1200 });
        } else {
            timeoutId = window.setTimeout(enableDnd, 250);
        }

        return () => {
            if (idleId !== null && typeof window.cancelIdleCallback === 'function') {
                window.cancelIdleCallback(idleId);
            }

            if (timeoutId !== null) {
                window.clearTimeout(timeoutId);
            }
        };
    }, [canEdit]);

    const updateField = useCallback((fieldClass, updater) => {
        setPalette((current) => {
            const next = clonePalette(current);
            const updateList = (fields) => {
                return (fields || []).map((field) => {
                    if (field.fieldClass !== fieldClass) {
                        return field;
                    }

                    return updater(field);
                });
            };

            next.groups = next.groups.map((group) => {
                return {
                    ...group,
                    fields: updateList(group.fields),
                };
            });
            next.unassigned = updateList(next.unassigned);

            return next;
        });
    }, []);

    const handleRenameGroup = useCallback((groupUid, name) => {
        setPalette((current) => {
            return {
                ...current,
                groups: (current.groups || []).map((group) => {
                    return group.uid === groupUid ? { ...group, name } : group;
                }),
            };
        });
    }, []);

    const handleDeleteGroup = useCallback((groupUid) => {
        const group = paletteRef.current.groups.find((item) => { return item.uid === groupUid; });

        if (!group) {
            return;
        }

        const confirmed = window.confirm(
            Craft.t('formie', 'Delete the “{name}” group? Its fields will move to Unassigned.', { name: group.name }),
        );

        if (!confirmed) {
            return;
        }

        setPalette((current) => {
            const target = current.groups.find((item) => { return item.uid === groupUid; });

            if (!target) {
                return current;
            }

            return {
                groups: current.groups.filter((item) => { return item.uid !== groupUid; }),
                unassigned: [...(current.unassigned || []), ...(target.fields || [])],
            };
        });
    }, []);

    const handleAddGroup = useCallback(() => {
        setPalette((current) => {
            const nextGroup = createGroup(Craft.t('formie', 'New Group'), current.groups);

            return {
                ...current,
                groups: [...(current.groups || []), nextGroup],
            };
        });
    }, []);

    const handleToggleEnabled = useCallback((fieldClass, enabled) => {
        updateField(fieldClass, (field) => { return { ...field, enabled }; });
    }, [updateField]);

    const handleLabelChange = useCallback((fieldClass, label) => {
        updateField(fieldClass, (field) => { return { ...field, label }; });
    }, [updateField]);

    const handleMoveGroup = useCallback((groupUid, offset) => {
        setPalette((current) => {
            return moveGroupByOffset(current, groupUid, offset);
        });
    }, []);

    const handleMoveField = useCallback((fieldClass, offset) => {
        setPalette((current) => {
            return moveFieldByOffset(current, fieldClass, offset);
        });
    }, []);

    const handleMoveFieldToGroup = useCallback((fieldClass, targetGroupUid) => {
        setPalette((current) => {
            return moveFieldToGroup(current, fieldClass, targetGroupUid);
        });
    }, []);

    const handleDragStart = useCallback((event) => {
        previousPaletteRef.current = clonePalette(paletteRef.current);

        const source = event.operation?.source;
        const sourceData = source?.data?.current ?? source?.data;

        groupsContainerRef.current?.classList.add('is-dragging');

        if (sourceData?.type === 'field') {
            setActiveDrag({
                type: 'field',
                field: resolveFieldFromSource(paletteRef.current, source),
            });
        }
    }, []);

    const finishDragSession = useCallback(() => {
        groupsContainerRef.current?.classList.remove('is-dragging');
        setActiveDrag(null);
        previousPaletteRef.current = null;
    }, []);

    const handleDragOver = useCallback((event) => {
        const sourceData = event.operation?.source?.data?.current ?? event.operation?.source?.data;

        if (sourceData?.type !== 'field') {
            return;
        }

        startTransition(() => {
            setPalette((current) => {
                return applyMoveEventToPalette(current, event);
            });
        });
    }, []);

    const handleDragEnd = useCallback((event) => {
        if (!canEdit || !useDnd) {
            finishDragSession();
            return;
        }

        if (event.canceled) {
            if (previousPaletteRef.current) {
                setPalette(clonePalette(previousPaletteRef.current));
            }

            finishDragSession();
            return;
        }

        finishDragSession();
    }, [canEdit, finishDragSession, useDnd]);

    return (
        <div className="formie-field-palette-app">
            {!canEdit ? (
                <div className="formie-field-palette-readonly">
                    {Craft.t('formie', 'Field palette settings are read-only when allowAdminChanges is disabled.')}
                </div>
            ) : null}

            <DragDropProvider
                onDragStart={handleDragStart}
                onDragOver={handleDragOver}
                onDragEnd={handleDragEnd}
                sensors={sensors}
            >
                <div
                    ref={groupsContainerRef}
                    className="formie-field-palette-groups"
                >
                    {(palette.groups || []).map((group, groupIndex) => {
                        return (
                            <GroupBlock
                                key={group.uid}
                                group={group}
                                groupIndex={groupIndex}
                                groupCount={(palette.groups || []).length}
                                canEdit={canEdit}
                                useDnd={useDnd}
                                groups={palette.groups}
                                onRename={handleRenameGroup}
                                onDelete={handleDeleteGroup}
                                onMoveGroup={handleMoveGroup}
                                onMoveField={handleMoveField}
                                onMoveFieldToGroup={handleMoveFieldToGroup}
                                onToggleEnabled={handleToggleEnabled}
                                onLabelChange={handleLabelChange}
                            />
                        );
                    })}

                    <section className="formie-field-palette-group" data-palette-group={UNASSIGNED_SORTABLE_GROUP}>
                        <div className="formie-field-palette-group-header">
                            <span className="formie-field-palette-field-handle-spacer" aria-hidden="true" />

                            <div className="formie-field-palette-group-name">
                                <strong>{Craft.t('formie', 'Unassigned')}</strong>
                            </div>

                            <span className="formie-field-palette-header-spacer" aria-hidden="true" />
                            <span className="formie-field-palette-header-spacer" aria-hidden="true" />
                        </div>

                        <FieldDropZone
                            id={sortableDropZoneId(null)}
                            canEdit={canEdit && useDnd}
                            isEmpty={!(palette.unassigned || []).length}
                            emptyLabel={Craft.t('formie', 'New or ungrouped field types appear here.')}
                        >
                            {(palette.unassigned || []).map((field, fieldIndex) => {
                                return (
                                    <SortableFieldRow
                                        key={field.fieldClass}
                                        field={field}
                                        fieldIndex={fieldIndex}
                                        groupUid={null}
                                        fieldCount={(palette.unassigned || []).length}
                                        groups={palette.groups}
                                        canEdit={canEdit}
                                        useDnd={useDnd}
                                        onToggleEnabled={handleToggleEnabled}
                                        onLabelChange={handleLabelChange}
                                        onMoveField={handleMoveField}
                                        onMoveFieldToGroup={handleMoveFieldToGroup}
                                    />
                                );
                            })}
                        </FieldDropZone>
                    </section>
                </div>

                <DragOverlay dropAnimation={null}>
                    <FieldPaletteDragGhost activeDrag={activeDrag} />
                </DragOverlay>
            </DragDropProvider>

            {canEdit ? (
                <div className="formie-field-palette-toolbar">
                    <Button type="button" variant="dashed" onClick={handleAddGroup}>
                        <FontAwesomeIcon icon={faPlus} className="size-3" />
                        {Craft.t('formie', 'Add group')}
                    </Button>
                </div>
            ) : null}
        </div>
    );
}
