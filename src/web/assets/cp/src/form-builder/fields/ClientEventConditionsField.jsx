import { ConditionsFieldBase } from './ConditionsFieldBase';

function ClientEventConditionsField({ field, form }) {
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

export { ClientEventConditionsField };
