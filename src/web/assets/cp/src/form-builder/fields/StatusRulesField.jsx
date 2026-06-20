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

const STATUS_RULE_CARD_CLASSNAME = 'relative rounded-sm border border-[rgba(96,125,159,0.25)] bg-[rgba(96,125,159,0.03)] p-4';

const createDefaultRule = (statusOptions) => ({
    ...createItem({}),
    statusId: statusOptions[0]?.value ?? '',
    trigger: 'finalSubmit',
    enableConditions: false,
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
        instructions: t('Choose whether to evaluate this rule on every page submission or only when the form is fully submitted.'),
    };
    const enableConditionsField = {
        name: `${baseName}.${index}.enableConditions`,
        label: t('Enable Conditions'),
        instructions: t('Whether to enable conditional logic to control when this status rule is applied.'),
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
        <div className={STATUS_RULE_CARD_CLASSNAME}>
            <div className="absolute top-3 right-3">
                <Button
                    type="button"
                    variant="none"
                    size="xs"
                    onClick={onRemove}
                    aria-label={t('Remove status rule')}
                    className="p-2 text-gray-500 hover:text-red-500"
                >
                    <FontAwesomeIcon icon={faXmark} className="size-[14px]" />
                </Button>
            </div>

            <div className="space-y-4 pr-8">
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
                    instructions={triggerField.instructions}
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

                <FieldLayout
                    name={enableConditionsField.name}
                    label={enableConditionsField.label}
                    instructions={enableConditionsField.instructions}
                >
                    <Lightswitch
                        checked={Boolean(enableConditions)}
                        onCheckedChange={(checked) => {
                            setEnableConditions(checked);
                            setEnableConditionsTouched();
                        }}
                    />
                </FieldLayout>

                {enableConditions ? (
                    <StatusRuleConditionsField field={conditionsField} form={form} />
                ) : null}
            </div>
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

                <Button type="button" variant="default" onClick={addRule}>
                    <FontAwesomeIcon icon={faPlus} className="mr-1 size-3" />
                    {t('Add status rule')}
                </Button>
            </div>
        </FieldLayout>
    );
}

export { StatusRulesField };
