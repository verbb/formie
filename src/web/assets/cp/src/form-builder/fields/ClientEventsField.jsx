import { useMemo } from 'react';

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPlus, faXmark } from '@fortawesome/pro-solid-svg-icons';

import {
    Button,
    EditableTable,
    Input,
    TiptapInput,
} from '@verbb/plugin-kit-react/components';
import { FieldLayout } from '@verbb/plugin-kit-react/forms/Field';
import { useEngineField } from '@verbb/plugin-kit-react/forms/useEngineField';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';
import { cn, createItem } from '@verbb/plugin-kit-react/utils';

import { useVariableCategories } from '@form-builder/hooks/useVariableCategories';
import useAppStore from '@form-builder/hooks/useAppStore';

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
    onRemove,
    variableCategories,
    variableCategoryLabels,
    variableCategoryOrder,
    variableTransformerRegistry,
}) {
    const t = useTranslation();
    const eventField = {
        name: `${baseName}.${index}.event`,
        label: t('Event Name'),
        instructions: t('The analytics event name. For Google Tag Manager, this is usually pushed as the `event` property on the payload.'),
        required: true,
    };

    const {
        value: eventName,
        setValue: setEventName,
        setTouched: setEventTouched,
        errors: eventErrors,
    } = useEngineField(form, eventField.name);

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

    const addEvent = () => {
        setValue([...events, createDefaultEvent()]);
        setTouched();
    };

    const removeEvent = (index) => {
        setValue(events.filter((_, eventIndex) => eventIndex !== index));
        setTouched();
    };

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
                {events.length === 0 ? (
                    <p className="text-sm text-gray-500">{t('No client events configured yet.')}</p>
                ) : null}

                {events.map((event, index) => (
                    <ClientEventItem
                        key={event._uid || event._id || `${field.name}-${index}`}
                        form={form}
                        baseName={field.name}
                        index={index}
                        onRemove={() => removeEvent(index)}
                        variableCategories={variableCategories}
                        variableCategoryLabels={variableCategoryLabels}
                        variableCategoryOrder={variableCategoryOrder}
                        variableTransformerRegistry={variableTransformerRegistry}
                    />
                ))}

                <Button type="button" variant="default" onClick={addEvent}>
                    <FontAwesomeIcon icon={faPlus} className="mr-1 size-3" />
                    {t('Add client event')}
                </Button>
            </div>
        </FieldLayout>
    );
}

export { ClientEventsField };
