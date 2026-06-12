import { ConditionsFieldBase } from './ConditionsFieldBase';

function IntegrationConditionsField({ field, form }) {
    return (
        <ConditionsFieldBase
            field={field}
            form={form}
            ruleKey="triggerRule"
            defaultRuleValue="trigger"
            ruleOptions={[
                { label: Craft.t('formie', 'Trigger'), value: 'trigger' },
                { label: Craft.t('formie', "Don't Trigger"), value: 'notTrigger' },
            ]}
            subjectLabel="this integration if"
        />
    );
}

export { IntegrationConditionsField };
