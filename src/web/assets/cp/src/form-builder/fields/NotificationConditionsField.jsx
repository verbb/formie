import { ConditionsFieldBase } from './ConditionsFieldBase';

function NotificationConditionsField({ field, form }) {
    return (
        <ConditionsFieldBase
            field={field}
            form={form}
            ruleKey="sendRule"
            defaultRuleValue="send"
            ruleOptions={[
                { label: Craft.t('formie', 'Send'), value: 'send' },
                { label: Craft.t('formie', 'Not Send'), value: 'notSend' },
            ]}
            subjectLabel="this notification if"
        />
    );
}

export { NotificationConditionsField };
