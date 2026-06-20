import { useMemo, useState } from 'react';
import { cloneDeep } from 'lodash-es';

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPlus, faXmark } from '@fortawesome/pro-solid-svg-icons';

import {
    Button,
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
    EditableTable,
    Input,
    Lightswitch,
    TiptapInput,
} from '@verbb/plugin-kit-react/components';
import { FieldLayout } from '@verbb/plugin-kit-react/forms/Field';
import { useEngineField } from '@verbb/plugin-kit-react/forms/useEngineField';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';
import { cn, createItem } from '@verbb/plugin-kit-react/utils';

import { useFormBuilderForm } from '@form-builder/contexts/FormBuilderFormContext';
import { useVariableCategories } from '@form-builder/hooks/useVariableCategories';
import useAppStore from '@form-builder/hooks/useAppStore';
import { ClientEventConditionsField } from '@form-builder/fields/ClientEventConditionsField';
import { ClientEventTemplateDialog } from '@form-builder/fields/ClientEventTemplateDialog';
import {
    getPageContext,
    getPageIndexFromFieldName,
    getSuggestedTemplates,
    materializeClientEventTemplate,
} from '@form-builder/utils/clientEventTemplates';

const CLIENT_EVENT_VARIABLE_CONFIG = {
    content: 'singleLine',
    types: ['text', 'date', 'number'],
    groupFieldsByPage: true,
    groups: [
        'fieldsVariables',
        'staticFormVariables',
        'staticGeneralVariables',
        'staticSiteVariables',
    ],
};

const CLIENT_EVENT_CARD_CLASSNAME = 'relative rounded-sm border border-[rgba(96,125,159,0.25)] bg-[rgba(96,125,159,0.03)] p-4';

const EDITABLE_TIPTAP_CLASSNAME = [
    'w-full',
    '[&_.ProseMirror]:min-h-[30px]',
    '[&_.ProseMirror]:h-full',
    '[&_.ProseMirror]:border-none',
    '[&_.ProseMirror]:rounded-none',
    '[&_.ProseMirror]:bg-transparent',
    '[&_.ProseMirror]:px-2',
    '[&_.ProseMirror]:pr-10',
    '[&_.ProseMirror]:py-[6px]',
    '[&_.ProseMirror]:text-xs',
    '[&_.ProseMirror]:shadow-none',
    '[&_.ProseMirror]:focus-visible:border-none',
    '[&_.ProseMirror]:focus-visible:shadow-none',
    '[&_.ProseMirror]:focus-visible:inset-ring-1',
    '[&_.ProseMirror]:focus-visible:inset-ring-gray-200',
];

const createDefaultPayloadRow = () => ({
    ...createItem({}),
    key: '',
    value: '',
});

const createDefaultEvent = () => ({
    ...createItem({}),
    event: 'formPageSubmission',
    payload: [],
    enableConditions: false,
    conditions: {
        applyRule: 'apply',
        conditionRule: 'all',
        conditions: [],
    },
});

function ClientEventPayloadRows({
    form,
    baseName,
    eventIndex,
    variableCategories,
    variableCategoryLabels,
    variableCategoryOrder,
    variableTransformerRegistry,
}) {
    const t = useTranslation();
    const payloadFieldName = `${baseName}.${eventIndex}.payload`;
    const {
        value,
        setValue,
        setTouched,
    } = useEngineField(form, payloadFieldName);

    const rows = Array.isArray(value) ? value : [];

    const columns = useMemo(() => {
        return [
            {
                name: 'key',
                label: t('Property'),
                type: 'text',
                placeholder: 'formHandle',
                className: 'w-[180px] max-w-[180px]',
            },
            {
                name: 'value',
                label: t('Value'),
                renderCell: ({
                    value: cellValue,
                    isInvalid,
                    updateValue,
                }) => {
                    return (
                        <TiptapInput
                            value={cellValue ?? ''}
                            onChange={(nextValue) => {
                                updateValue(nextValue);
                            }}
                            isInvalid={isInvalid}
                            className={cn(
                                EDITABLE_TIPTAP_CLASSNAME,
                                isInvalid && [
                                    '[&_.ProseMirror]:inset-ring-1',
                                    '[&_.ProseMirror]:inset-ring-rose-600',
                                    '[&_.ProseMirror]:focus-visible:inset-ring-rose-600',
                                ],
                            )}
                            variableCategories={variableCategories}
                            variableCategoryLabels={variableCategoryLabels}
                            variableCategoryOrder={variableCategoryOrder}
                            variableTransformerRegistry={variableTransformerRegistry}
                            variablePickerTriggerCharacters={['@', '{']}
                        />
                    );
                },
            },
        ];
    }, [
        t,
        variableCategories,
        variableCategoryLabels,
        variableCategoryOrder,
        variableTransformerRegistry,
    ]);

    return (
        <div className="space-y-2">
            <p className="text-xs text-gray-500">{t('Payload properties are pushed to `window.dataLayer` and the `formie:client-event` DOM event after a successful page submit.')}</p>

            <EditableTable
                columns={columns}
                rows={rows}
                onChange={(nextRows) => {
                    setValue(nextRows);
                    setTouched();
                }}
                addRowLabel={t('Add property')}
                allowReorder={false}
                allowAdd={true}
                allowDelete={true}
                newRowDefaults={{
                    key: '',
                    value: '',
                }}
            />
        </div>
    );
}

function ClientEventItem({
    form,
    baseName,
    index,
    eventDefinition,
    onRemove,
    variableCategories,
    variableCategoryLabels,
    variableCategoryOrder,
    variableTransformerRegistry,
    field,
}) {
    const t = useTranslation();
    const eventField = {
        name: `${baseName}.${index}.event`,
        label: t('Event Name'),
        instructions: t('The analytics event name. For Google Tag Manager, this is usually pushed as the `event` property on the payload.'),
        required: true,
    };
    const enableConditionsField = {
        name: `${baseName}.${index}.enableConditions`,
        label: t('Enable Conditions'),
        instructions: t('Whether to enable conditional logic to control when this client event is pushed.'),
    };
    const conditionsField = {
        name: `${baseName}.${index}.conditions`,
        fieldOptions: field.fieldOptions,
        conditionOptions: field.conditionOptions,
    };

    const {
        value: eventName,
        setValue: setEventName,
        setTouched: setEventTouched,
        errors: eventErrors,
    } = useEngineField(form, eventField.name);
    const {
        value: enableConditions,
        setValue: setEnableConditions,
        setTouched: setEnableConditionsTouched,
    } = useEngineField(form, enableConditionsField.name);

    return (
        <div className={CLIENT_EVENT_CARD_CLASSNAME}>
            <div className="absolute top-3 right-3">
                <Button
                    type="button"
                    variant="none"
                    size="xs"
                    onClick={onRemove}
                    aria-label={t('Remove client event')}
                    className="p-2 text-gray-500 hover:text-red-500"
                >
                    <FontAwesomeIcon icon={faXmark} className="size-[14px]" />
                </Button>
            </div>

            <div className="space-y-4 pr-8">
                {eventDefinition?.templateLabel ? (
                    <div className="text-xs text-gray-500">
                        {t('Template')}: <span className="font-medium text-gray-700">{eventDefinition.templateLabel}</span>
                    </div>
                ) : null}

                <FieldLayout
                    name={eventField.name}
                    label={eventField.label}
                    instructions={eventField.instructions}
                    required={eventField.required}
                    errors={eventErrors}
                >
                    <Input
                        value={eventName ?? ''}
                        onChange={(event) => {
                            setEventName(event.target.value);
                            setEventTouched();
                        }}
                        placeholder="formPageSubmission"
                    />
                </FieldLayout>

                <ClientEventPayloadRows
                    form={form}
                    baseName={baseName}
                    eventIndex={index}
                    variableCategories={variableCategories}
                    variableCategoryLabels={variableCategoryLabels}
                    variableCategoryOrder={variableCategoryOrder}
                    variableTransformerRegistry={variableTransformerRegistry}
                />

                {field.fieldOptions?.length && field.conditionOptions?.length ? (
                    <div className="space-y-3 border-t border-[rgba(96,125,159,0.2)] pt-4">
                        <FieldLayout
                            name={enableConditionsField.name}
                            label={enableConditionsField.label}
                            instructions={enableConditionsField.instructions}
                        >
                            <Lightswitch
                                checked={Boolean(enableConditions)}
                                onCheckedChange={(checked) => {
                                    setEnableConditions(Boolean(checked));
                                    setEnableConditionsTouched();
                                }}
                            />
                        </FieldLayout>

                        {enableConditions ? (
                            <ClientEventConditionsField
                                field={conditionsField}
                                form={form}
                            />
                        ) : null}
                    </div>
                ) : null}
            </div>
        </div>
    );
}

function ClientEventsField({ field, form }) {
    const {
        value,
        setValue,
        setTouched,
        errors,
    } = useEngineField(form, field.name);
    const t = useTranslation();
    const events = Array.isArray(value) ? value : [];
    const variableCategories = useVariableCategories(field.variableConfig || CLIENT_EVENT_VARIABLE_CONFIG);
    const variableCategoryLabels = useAppStore((state) => state.variableCategoryLabels);
    const variableCategoryOrder = useAppStore((state) => state.variableCategoryOrder);
    const variableTransformerRegistry = useAppStore((state) => state.variableCategoriesConfig?.transformerRegistry || {});
    const clientEventTemplates = useAppStore((state) => state.clientEventTemplates || []);
    const builderForm = useFormBuilderForm()?.form;

    const [pendingTemplate, setPendingTemplate] = useState(null);
    const [templateDialogOpen, setTemplateDialogOpen] = useState(false);

    const isFormDefaultsMode = field.mode === 'formDefaults';
    const pageIndex = isFormDefaultsMode ? null : getPageIndexFromFieldName(field.name);
    const builderPages = builderForm?.getFieldValue?.('pages') || form?.getFieldValue?.('pages') || [];
    const pageCount = Array.isArray(builderPages) ? builderPages.length : 0;
    const pageContext = getPageContext(pageIndex, pageCount);

    const suggestedTemplates = useMemo(() => {
        return getSuggestedTemplates(clientEventTemplates, pageContext);
    }, [clientEventTemplates, pageContext]);

    const addEvent = (eventDefinition = createDefaultEvent()) => {
        setValue([...events, eventDefinition]);
        setTouched();
    };

    const removeEvent = (index) => {
        setValue(events.filter((_, eventIndex) => eventIndex !== index));
        setTouched();
    };

    const handleTemplateSelect = (template) => {
        const slots = (template.payload || []).filter((row) => row.kind === 'field');

        if (slots.length > 0) {
            setPendingTemplate(template);
            setTemplateDialogOpen(true);
            return;
        }

        addEvent(materializeClientEventTemplate(template));
    };

    const handleTemplateConfirm = (fieldMappings) => {
        if (!pendingTemplate) {
            return;
        }

        addEvent(materializeClientEventTemplate(pendingTemplate, fieldMappings));
        setPendingTemplate(null);
    };

    const applyDefaultsToAllPages = () => {
        if (!builderForm || !events.length) {
            return;
        }

        const pages = Array.isArray(builderForm.getFieldValue('pages')) ? builderForm.getFieldValue('pages') : [];
        const nextPages = pages.map((page) => ({
            ...page,
            settings: {
                ...(page?.settings || {}),
                enableClientEvents: true,
                clientEvents: cloneDeep(events),
            },
        }));

        builderForm.setFieldValue('pages', nextPages);
    };

    const templatesByCategory = useMemo(() => {
        const grouped = new Map();

        clientEventTemplates.forEach((template) => {
            const categoryLabel = template.categoryLabel || t('General');

            if (!grouped.has(categoryLabel)) {
                grouped.set(categoryLabel, []);
            }

            grouped.get(categoryLabel).push(template);
        });

        return grouped;
    }, [clientEventTemplates, t]);

    return (
        <FieldLayout
            name={field.name}
            label={field.label}
            instructions={field.instructions}
            warning={field.warning}
            required={field.required}
            errors={errors}
        >
            <div className="space-y-3">
                {!isFormDefaultsMode && suggestedTemplates.length > 0 ? (
                    <div className="space-y-2">
                        <p className="text-xs font-medium text-gray-600">{t('Suggested for this page')}</p>
                        <div className="flex flex-wrap gap-2">
                            {suggestedTemplates.map((template) => (
                                <Button
                                    key={template.handle}
                                    type="button"
                                    size="sm"
                                    variant="default"
                                    onClick={() => handleTemplateSelect(template)}
                                >
                                    {template.label}
                                </Button>
                            ))}
                        </div>
                    </div>
                ) : null}

                {events.length === 0 ? (
                    <p className="text-sm text-gray-500">{t('No client events configured yet.')}</p>
                ) : null}

                {events.map((event, index) => (
                    <ClientEventItem
                        key={event._uid || event._id || `${field.name}-${index}`}
                        form={form}
                        baseName={field.name}
                        index={index}
                        eventDefinition={event}
                        onRemove={() => removeEvent(index)}
                        variableCategories={variableCategories}
                        variableCategoryLabels={variableCategoryLabels}
                        variableCategoryOrder={variableCategoryOrder}
                        variableTransformerRegistry={variableTransformerRegistry}
                        field={field}
                    />
                ))}

                <div className="flex flex-wrap items-center gap-2">
                    {clientEventTemplates.length > 0 ? (
                        <DropdownMenu>
                            <DropdownMenuTrigger
                                render={(
                                    <Button type="button" variant="default">
                                        <FontAwesomeIcon icon={faPlus} className="mr-1 size-3" />
                                        {t('Add event')}
                                    </Button>
                                )}
                            />
                            <DropdownMenuContent
                                align="start"
                                className="min-w-[280px] max-h-[min(360px,var(--available-height))] overflow-y-auto"
                            >
                                {[...templatesByCategory.entries()].map(([categoryLabel, templates]) => (
                                    <DropdownMenuGroup key={categoryLabel}>
                                        <DropdownMenuLabel className="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                            {categoryLabel}
                                        </DropdownMenuLabel>
                                        {templates.map((template) => (
                                            <DropdownMenuItem
                                                key={template.handle}
                                                onClick={() => handleTemplateSelect(template)}
                                            >
                                                <div className="min-w-0">
                                                    <div className="truncate text-sm">{template.label}</div>
                                                    {template.description ? (
                                                        <div className="truncate text-xs text-gray-500">{template.description}</div>
                                                    ) : null}
                                                </div>
                                            </DropdownMenuItem>
                                        ))}
                                    </DropdownMenuGroup>
                                ))}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    ) : (
                        <Button type="button" variant="default" onClick={() => addEvent()}>
                            <FontAwesomeIcon icon={faPlus} className="mr-1 size-3" />
                            {t('Add event')}
                        </Button>
                    )}

                    {isFormDefaultsMode && events.length > 0 ? (
                        <Button type="button" variant="default" onClick={applyDefaultsToAllPages}>
                            {t('Apply defaults to all pages')}
                        </Button>
                    ) : null}
                </div>

                <ClientEventTemplateDialog
                    open={templateDialogOpen}
                    onOpenChange={setTemplateDialogOpen}
                    template={pendingTemplate}
                    pages={builderPages}
                    onConfirm={handleTemplateConfirm}
                />
            </div>
        </FieldLayout>
    );
}

export { ClientEventsField };
