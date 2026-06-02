import { ConditionsFieldBase } from './ConditionsFieldBase';

function PageConditionsField({ field, form }) {
    return (
        <ConditionsFieldBase
            field={field}
            form={form}
            ruleKey="showRule"
            defaultRuleValue="show"
            fieldSelectionPageScope="previousOnly"
            referenceContext="client"
            ruleOptions={[
                { label: Craft.t('formie', 'Show'), value: 'show' },
                { label: Craft.t('formie', 'Hide'), value: 'hide' },
            ]}
            subjectLabel="this page if"
        />
    );
}

export { PageConditionsField };
