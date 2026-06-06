import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPlus, faXmark } from '@fortawesome/pro-solid-svg-icons';

import {
    Button,
    Input,
    SelectInput,
} from '@verbb/plugin-kit-react/components';
import { FieldLayout } from '@verbb/plugin-kit-react/forms/Field';
import { ElementSelectField } from '@verbb/plugin-kit-react/forms/fields/ElementSelectField';
import { useEngineField } from '@verbb/plugin-kit-react/forms/useEngineField';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';
import { createItem } from '@verbb/plugin-kit-react/utils';

import { RedirectRuleConditionsField } from './RedirectRuleConditionsField';

const createDefaultRule = () => ({
    ...createItem({}),
    redirectType: 'url',
    submitActionUrl: '',
    submitActionEntry: [],
    conditions: {
        applyRule: 'apply',
        conditionRule: 'all',
        conditions: [],
    },
});

function RedirectRuleItem({
    form,
    baseName,
    index,
    field,
    onRemove,
}) {
    const t = useTranslation();
    const redirectTypeField = {
        name: `${baseName}.${index}.redirectType`,
        label: t('Redirect To'),
        instructions: t('Choose whether to redirect to a URL or a Craft entry when this rule matches.'),
    };
    const urlField = {
        name: `${baseName}.${index}.submitActionUrl`,
        label: t('Redirect URL'),
        instructions: t('The full URL that the user to be redirected to.'),
    };
    const entryField = {
        name: `${baseName}.${index}.submitActionEntry`,
        label: t('Redirect Entry'),
        instructions: t('Select an entry for the user to be redirected to.'),
        elementType: 'craft\\elements\\Entry',
        limit: 1,
        elementSelectOptionsAction: 'formie/fields/get-element-select-options',
        elementSelectStorageKeyPrefix: 'FormieRedirectRuleEntry',
    };
    const conditionsField = {
        name: `${baseName}.${index}.conditions`,
        fieldOptions: field.fieldOptions,
        conditionOptions: field.conditionOptions,
    };

    const {
        value: redirectType,
        setValue: setRedirectType,
        setTouched: setRedirectTypeTouched,
    } = useEngineField(form, redirectTypeField.name);
    const {
        value: submitActionUrl,
        setValue: setSubmitActionUrl,
        setTouched: setSubmitActionUrlTouched,
    } = useEngineField(form, urlField.name);

    const resolvedRedirectType = redirectType || 'url';

    return (
        <div className="relative rounded border border-gray-300 bg-gray-50 p-4">
            <div className="absolute top-3 right-3">
                <Button
                    type="button"
                    variant="none"
                    size="xs"
                    onClick={onRemove}
                    aria-label={t('Remove redirect rule')}
                    className="p-2 text-gray-500 hover:text-red-500"
                >
                    <FontAwesomeIcon icon={faXmark} className="size-[14px]" />
                </Button>
            </div>

            <div className="space-y-4 pr-8">
                <FieldLayout
                    name={redirectTypeField.name}
                    label={redirectTypeField.label}
                    instructions={redirectTypeField.instructions}
                >
                    <SelectInput
                        value={resolvedRedirectType}
                        options={[
                            { label: t('Redirect to a URL'), value: 'url' },
                            { label: t('Redirect to an entry'), value: 'entry' },
                        ]}
                        onChange={(nextValue) => {
                            setRedirectType(nextValue);
                            setRedirectTypeTouched();
                        }}
                    />
                </FieldLayout>

                {resolvedRedirectType === 'url' ? (
                    <FieldLayout
                        name={urlField.name}
                        label={urlField.label}
                        instructions={urlField.instructions}
                    >
                        <Input
                            value={submitActionUrl ?? ''}
                            onChange={(event) => {
                                setSubmitActionUrl(event.target.value);
                                setSubmitActionUrlTouched();
                            }}
                        />
                    </FieldLayout>
                ) : (
                    <ElementSelectField field={entryField} form={form} />
                )}

                <RedirectRuleConditionsField field={conditionsField} form={form} />
            </div>
        </div>
    );
}

function RedirectRulesField({ field, form }) {
    const {
        value,
        setValue,
        setTouched,
        errors,
    } = useEngineField(form, field.name);
    const t = useTranslation();
    const rules = Array.isArray(value) ? value : [];

    const addRule = () => {
        setValue([...rules, createDefaultRule()]);
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
                    <p className="text-sm text-gray-500">{t('No redirect rules configured yet.')}</p>
                ) : null}

                {rules.map((rule, index) => (
                    <RedirectRuleItem
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
                    {t('Add redirect rule')}
                </Button>
            </div>
        </FieldLayout>
    );
}

export { RedirectRulesField };
