import { ConditionsFieldBase } from './ConditionsFieldBase';

function RedirectRuleConditionsField({ field, form }) {
    return (
        <ConditionsFieldBase
            field={field}
            form={form}
            ruleKey="applyRule"
            defaultRuleValue="apply"
            hideRuleSelector
            referenceContext="client"
        />
    );
}

export { RedirectRuleConditionsField };
