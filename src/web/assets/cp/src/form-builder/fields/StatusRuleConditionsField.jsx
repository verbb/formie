import { ConditionsFieldBase } from './ConditionsFieldBase';

function StatusRuleConditionsField({ field, form }) {
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

export { StatusRuleConditionsField };
