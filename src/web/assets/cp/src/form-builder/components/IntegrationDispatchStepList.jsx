import { useCallback, useRef, useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faArrowDown, faArrowUp, faEllipsis } from '@fortawesome/pro-solid-svg-icons';
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
    Button,
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    SelectInput,
    Status,
} from '@verbb/plugin-kit-react/components';
import { cn } from '@verbb/plugin-kit-react/utils';
import { DragHandle } from '@field-palette/components/DragHandle';

const STEP_MODE_OPTIONS = [
    { value: 'immediate', label: Craft.t('formie', 'Immediate') },
    { value: 'queued', label: Craft.t('formie', 'Queued') },
];

const getStepModeLabel = (mode) => {
    return STEP_MODE_OPTIONS.find((option) => {
        return option.value === mode;
    })?.label || STEP_MODE_OPTIONS[1].label;
};

const formatIntegrationSubLabel = (integration) => {
    if (!integration) {
        return '';
    }

    const displayName = integration.displayName || integration.name || integration.handle;
    const groupLabel = integration.groupLabel;

    if (groupLabel) {
        return `${displayName} (${groupLabel})`;
    }

    return displayName;
};

function IntegrationDispatchStepDragGhost({
    step,
    integration,
    stepEnabled,
}) {
    if (!step || !integration) {
        return null;
    }

    return (
        <div className="formie-integration-dispatch-drag-ghost">
            <DragHandle
                handleRef={() => {}}
                disabled
                ariaLabel={Craft.t('formie', 'Dragging integration step')}
            />

            <div className="formie-integration-dispatch-step-label">
                <div className="formie-integration-dispatch-step-name">
                    <span>{integration.name}</span>
                    <Status
                        className="shrink-0"
                        status={stepEnabled ? 'enabled' : 'disabled'}
                    />
                </div>
                <span className="formie-integration-dispatch-step-handle">
                    {formatIntegrationSubLabel(integration)}
                </span>
            </div>

            <div className="formie-integration-dispatch-step-mode">
                <span className="formie-integration-dispatch-step-mode-label">
                    {getStepModeLabel(step.mode || 'queued')}
                </span>
            </div>

            <span className="formie-integration-dispatch-step-actions" aria-hidden="true" />
        </div>
    );
}

function IntegrationDispatchStepRow({
    step,
    index,
    stepCount,
    integration,
    stepEnabled,
    showImmediateHint,
    listRef,
    onModeChange,
    onMove,
}) {
    const {
        ref, handleRef, isDragSource,
    } = useSortable({
        id: step.handle,
        index,
        transition: null,
        sensors: [
            PointerSensor.configure({ activationConstraint: { delay: 0, tolerance: 5 } }),
            KeyboardSensor,
        ],
        modifiers: [
            RestrictToVerticalAxis,
            RestrictToElement.configure({
                element: () => {
                    return listRef.current;
                },
            }),
        ],
    });

    const canMoveUp = index > 0;
    const canMoveDown = index < stepCount - 1;

    return (
        <div
            ref={ref}
            className={cn(
                'formie-integration-dispatch-step-row',
                !stepEnabled && 'is-disabled',
                isDragSource && 'is-drag-placeholder',
            )}
            style={{
                position: 'relative',
                zIndex: isDragSource ? 10 : undefined,
            }}
        >
            <DragHandle
                handleRef={handleRef}
                ariaLabel={Craft.t('formie', 'Drag to reorder {name}', { name: integration.name })}
            />

            <div className="formie-integration-dispatch-step-label">
                <div className="formie-integration-dispatch-step-name">
                    <span>{integration.name}</span>
                    <Status
                        className="shrink-0"
                        status={stepEnabled ? 'enabled' : 'disabled'}
                    />
                </div>
                <span className="formie-integration-dispatch-step-handle">
                    {formatIntegrationSubLabel(integration)}
                    {showImmediateHint && (
                        <span className="ml-2 text-amber-700">
                            {Craft.t('formie', 'Consider Immediate for element integrations')}
                        </span>
                    )}
                </span>
            </div>

            <div className="formie-integration-dispatch-step-mode">
                {!isDragSource ? (
                    <SelectInput
                        value={step.mode || 'queued'}
                        options={STEP_MODE_OPTIONS}
                        onChange={(value) => {
                            onModeChange(index, String(value || 'queued'));
                        }}
                        aria-label={Craft.t('formie', 'Execution mode for {name}', { name: integration.name })}
                        triggerClassName="w-full"
                    />
                ) : null}
            </div>

            {!isDragSource ? (
                <div className="formie-integration-dispatch-step-actions">
                <DropdownMenu size="sm">
                    <DropdownMenuTrigger
                        render={(
                            <Button
                                type="button"
                                variant="none"
                                size="xs"
                                className="formie-integration-dispatch-menu-trigger"
                                aria-label={Craft.t('formie', 'Actions for {name}', { name: integration.name })}
                            />
                        )}
                    >
                        <FontAwesomeIcon icon={faEllipsis} className="size-3.5" />
                    </DropdownMenuTrigger>

                    <DropdownMenuContent align="end" className="min-w-[180px]">
                        <DropdownMenuItem
                            disabled={!canMoveUp}
                            onClick={() => {
                                onMove(index, -1);
                            }}
                        >
                            <FontAwesomeIcon icon={faArrowUp} />
                            {Craft.t('formie', 'Move up')}
                        </DropdownMenuItem>

                        <DropdownMenuItem
                            disabled={!canMoveDown}
                            onClick={() => {
                                onMove(index, 1);
                            }}
                        >
                            <FontAwesomeIcon icon={faArrowDown} />
                            {Craft.t('formie', 'Move down')}
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
                </div>
            ) : (
                <span className="formie-integration-dispatch-step-actions" aria-hidden="true" />
            )}
        </div>
    );
}

function IntegrationDispatchStepList({
    steps,
    integrationLookup,
    isIntegrationEnabled,
    isElementIntegration,
    onStepsChange,
    onStepModeChange,
    onMoveStep,
}) {
    const listRef = useRef(null);
    const [activeDrag, setActiveDrag] = useState(null);

    const finishDragSession = useCallback(() => {
        setActiveDrag(null);
    }, []);

    const handleDragStart = useCallback((event) => {
        const source = event.operation?.source;

        if (!isSortable(source)) {
            return;
        }

        const handle = String(source.id || '');
        const step = steps.find((item) => {
            return item?.handle === handle;
        });
        const integration = integrationLookup[handle];

        if (!step || !integration) {
            return;
        }

        setActiveDrag({
            step,
            integration,
            stepEnabled: isIntegrationEnabled(integration.handle),
        });
    }, [integrationLookup, isIntegrationEnabled, steps]);

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

        if (initialIndex === index || initialIndex < 0 || index < 0 || initialIndex >= steps.length || index >= steps.length) {
            return;
        }

        const nextSteps = [...steps];
        const [movedStep] = nextSteps.splice(initialIndex, 1);
        nextSteps.splice(index, 0, movedStep);
        onStepsChange(nextSteps);
    }, [finishDragSession, onStepsChange, steps]);

    return (
        <div ref={listRef} className="formie-integration-dispatch-steps">
            <DragDropProvider
                onDragStart={handleDragStart}
                onDragEnd={handleDragEnd}
                onDragCancel={finishDragSession}
            >
                {steps.map((step, index) => {
                    const integration = integrationLookup[step.handle];

                    if (!integration) {
                        return null;
                    }

                    const stepEnabled = isIntegrationEnabled(integration.handle);
                    const showImmediateHint = isElementIntegration(integration) && step.mode !== 'immediate';

                    return (
                        <IntegrationDispatchStepRow
                            key={step.handle}
                            step={step}
                            index={index}
                            stepCount={steps.length}
                            integration={integration}
                            stepEnabled={stepEnabled}
                            showImmediateHint={showImmediateHint}
                            listRef={listRef}
                            onModeChange={onStepModeChange}
                            onMove={onMoveStep}
                        />
                    );
                })}

                <DragOverlay dropAnimation={null}>
                    {activeDrag ? (
                        <IntegrationDispatchStepDragGhost
                            step={activeDrag.step}
                            integration={activeDrag.integration}
                            stepEnabled={activeDrag.stepEnabled}
                        />
                    ) : null}
                </DragOverlay>
            </DragDropProvider>
        </div>
    );
}

export { IntegrationDispatchStepList, STEP_MODE_OPTIONS };
