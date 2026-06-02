import { ConditionsFieldBase } from './ConditionsFieldBase';

function FieldConditionsField({ field, form }) {
    return (
        <ConditionsFieldBase
            field={field}
            form={form}
            ruleKey="showRule"
            defaultRuleValue="show"
            fieldSelectionPageScope="currentAndPrevious"
            excludeSelfInFieldOptions={true}
            referenceContext="client"
            ruleOptions={[
                { label: Craft.t('formie', 'Show'), value: 'show' },
                { label: Craft.t('formie', 'Hide'), value: 'hide' },
            ]}
            subjectLabel="this field if"
        />
    );
}

export { FieldConditionsField };
