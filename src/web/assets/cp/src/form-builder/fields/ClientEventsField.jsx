import { Fragment, useMemo, useState } from 'react';
import { cloneDeep } from 'lodash-es';
import { createItem } from '@verbb/plugin-kit-core';

import { Button, DropdownItem, DropdownLabel, DropdownMenu, EditableTable, Icon, Input, Lightswitch } from '@verbb/plugin-kit-react/components';
import { FieldLayout, useEngineField } from '@verbb/plugin-kit-react/forms';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';

import { useFormBuilderForm } from '@form-builder/contexts/FormBuilderFormContext';
import { useVariableCategories } from '@form-builder/hooks/useVariableCategories';
import useAppStore from '@form-builder/hooks/useAppStore';
import { ClientEventConditionsField } from '@form-builder/fields/ClientEventConditionsField';
import { ClientEventTemplateDialog } from '@form-builder/fields/ClientEventTemplateDialog';
import { VariablePickerInputCell } from '@form-builder/fields/variable-picker/VariablePickerInputCell';
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
                width: '180px',
            },
            {
                name: 'value',
                label: t('Value'),
                // Flush cell padding so TipTap rail + variable + insert sit like v1.
                contentClassName: 'p-0! min-w-[220px]',
                renderCell: ({
                    value: cellValue,
                    isInvalid,
                    updateValue,
                }) => {
                    return (
                        <VariablePickerInputCell
                            value={cellValue ?? ''}
                            onChange={updateValue}
                            isInvalid={isInvalid}
                            variableCategories={variableCategories}
                            variableCategoryLabels={variableCategoryLabels}
                            variableCategoryOrder={variableCategoryOrder}
                            variableTransformerRegistry={variableTransformerRegistry}
                            className="min-h-[32px]"
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
                    <Icon slot="start" icon="xmark" className="size-[14px]" />
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
            {/* Overlays outside space-y / use gap — in-tree pk-dialog steals :last-child. */}
            <div className="flex flex-col gap-3">
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
                        <DropdownMenu size="sm" placement="bottom-start">
                            <Button slot="trigger" type="button" variant="default">
                                <Icon slot="start" icon="plus" className="size-3" />
                                {t('Add event')}
                            </Button>
                            {[...templatesByCategory.entries()].map(([categoryLabel, templates]) => (
                                <Fragment key={categoryLabel}>
                                    <DropdownLabel>{categoryLabel}</DropdownLabel>
                                    {templates.map((template) => (
                                        <DropdownItem
                                            key={template.handle}
                                            value={template.handle}
                                            onPkSelect={() => handleTemplateSelect(template)}
                                        >
                                            <div className="min-w-0">
                                                <div className="truncate text-sm">{template.label}</div>
                                                {template.description ? (
                                                    <div className="truncate text-xs text-gray-500">{template.description}</div>
                                                ) : null}
                                            </div>
                                        </DropdownItem>
                                    ))}
                                </Fragment>
                            ))}
                        </DropdownMenu>
                    ) : (
                        <Button type="button" variant="default" onClick={() => addEvent()}>
                            <Icon slot="start" icon="plus" className="size-3" />
                            {t('Add event')}
                        </Button>
                    )}

                    {isFormDefaultsMode && events.length > 0 ? (
                        <Button type="button" variant="default" onClick={applyDefaultsToAllPages}>
                            {t('Apply defaults to all pages')}
                        </Button>
                    ) : null}
                </div>
            </div>

            <ClientEventTemplateDialog
                open={templateDialogOpen}
                onOpenChange={setTemplateDialogOpen}
                template={pendingTemplate}
                pages={builderPages}
                onConfirm={handleTemplateConfirm}
            />
        </FieldLayout>
    );
}

export { ClientEventsField };
