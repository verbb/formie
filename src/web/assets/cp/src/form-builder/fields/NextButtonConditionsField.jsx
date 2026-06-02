import { ConditionsFieldBase } from './ConditionsFieldBase';

function NextButtonConditionsField({ field, form }) {
    return (
        <ConditionsFieldBase
            field={field}
            form={form}
            ruleKey="showRule"
            defaultRuleValue="show"
            fieldSelectionPageScope="currentAndPrevious"
            referenceContext="client"
            ruleOptions={[
                { label: Craft.t('formie', 'Show'), value: 'show' },
                { label: Craft.t('formie', 'Hide'), value: 'hide' },
            ]}
            subjectLabel="the next button if"
        />
    );
}

export { NextButtonConditionsField };
