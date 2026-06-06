import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPlus, faXmark } from '@fortawesome/pro-solid-svg-icons';

import {
    Button,
    Lightswitch,
    SelectInput,
} from '@verbb/plugin-kit-react/components';
import { FieldLayout } from '@verbb/plugin-kit-react/forms/Field';
import { useEngineField } from '@verbb/plugin-kit-react/forms/useEngineField';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';
import { createItem } from '@verbb/plugin-kit-react/utils';

import { StatusRuleConditionsField } from './StatusRuleConditionsField';

const createDefaultRule = (statusOptions) => ({
    ...createItem({}),
    statusId: statusOptions[0]?.value ?? '',
    trigger: 'finalSubmit',
    enableConditions: true,
    conditions: {
        applyRule: 'apply',
        conditionRule: 'all',
        conditions: [],
    },
});

function StatusRuleItem({
    form,
    baseName,
    index,
    field,
    onRemove,
}) {
    const t = useTranslation();
    const statusField = {
        name: `${baseName}.${index}.statusId`,
        label: t('Status'),
        required: true,
    };
    const triggerField = {
        name: `${baseName}.${index}.trigger`,
        label: t('Apply When'),
    };
    const enableConditionsField = {
        name: `${baseName}.${index}.enableConditions`,
        label: t('Enable Conditions'),
    };
    const conditionsField = {
        name: `${baseName}.${index}.conditions`,
        fieldOptions: field.fieldOptions,
        conditionOptions: field.conditionOptions,
    };

    const {
        value: statusId,
        setValue: setStatusId,
        setTouched: setStatusTouched,
        errors: statusErrors,
    } = useEngineField(form, statusField.name);
    const {
        value: trigger,
        setValue: setTrigger,
        setTouched: setTriggerTouched,
    } = useEngineField(form, triggerField.name);
    const {
        value: enableConditions,
        setValue: setEnableConditions,
        setTouched: setEnableConditionsTouched,
    } = useEngineField(form, enableConditionsField.name);

    return (
        <div className="space-y-4 rounded border border-gray-300 bg-gray-50 p-4">
            <div className="flex items-start justify-between gap-3">
                <div className="grid flex-1 gap-4 md:grid-cols-2">
                    <FieldLayout
                        name={statusField.name}
                        label={statusField.label}
                        required={statusField.required}
                        errors={statusErrors}
                    >
                        <SelectInput
                            value={statusId ?? ''}
                            options={field.statusOptions || []}
                            onChange={(nextValue) => {
                                setStatusId(nextValue);
                                setStatusTouched();
                            }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name={triggerField.name}
                        label={triggerField.label}
                        instructions={t('Choose whether to evaluate this rule on every page submission or only when the form is fully submitted.')}
                    >
                        <SelectInput
                            value={trigger || 'finalSubmit'}
                            options={field.triggerOptions || []}
                            onChange={(nextValue) => {
                                setTrigger(nextValue);
                                setTriggerTouched();
                            }}
                        />
                    </FieldLayout>
                </div>

                <Button
                    type="button"
                    variant="secondary"
                    size="small"
                    onClick={onRemove}
                    aria-label={t('Remove status rule')}
                >
                    <FontAwesomeIcon icon={faXmark} />
                </Button>
            </div>

            <div className="flex items-center gap-3">
                <Lightswitch
                    checked={Boolean(enableConditions)}
                    onCheckedChange={(checked) => {
                        setEnableConditions(checked);
                        setEnableConditionsTouched();
                    }}
                />
                <span className="text-sm">{t('Enable Conditions')}</span>
            </div>

            {enableConditions ? (
                <StatusRuleConditionsField field={conditionsField} form={form} />
            ) : null}
        </div>
    );
}

function StatusRulesField({ field, form }) {
    const {
        value,
        setValue,
        setTouched,
        errors,
    } = useEngineField(form, field.name);
    const t = useTranslation();
    const rules = Array.isArray(value) ? value : [];

    const addRule = () => {
        setValue([...rules, createDefaultRule(field.statusOptions || [])]);
        setTouched();
    };

    const removeRule = (index) => {
        setValue(rules.filter((_, ruleIndex) => ruleIndex !== index));
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
                {rules.length === 0 ? (
                    <p className="text-sm text-gray-500">{t('No status rules configured yet.')}</p>
                ) : null}

                {rules.map((rule, index) => (
                    <StatusRuleItem
                        key={rule._uid || rule._id || `${field.name}-${index}`}
                        form={form}
                        baseName={field.name}
                        index={index}
                        field={field}
                        onRemove={() => removeRule(index)}
                    />
                ))}

                <Button type="button" variant="secondary" onClick={addRule}>
                    <FontAwesomeIcon icon={faPlus} className="mr-1 size-3" />
                    {t('Add status rule')}
                </Button>
            </div>
        </FieldLayout>
    );
}

export { StatusRulesField };
