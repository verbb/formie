import { useRef, useState } from 'react';

import { Alert, Button, Dialog, Lightswitch } from '@verbb/plugin-kit-react/components';
import { FieldLayout } from '@verbb/plugin-kit-react/forms';

export function UnmarkSpamDialog({ onClose, onSubmit }) {
    const [open, setOpen] = useState(true);
    const [sendNotifications, setSendNotifications] = useState(false);
    const [triggerIntegrations, setTriggerIntegrations] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState('');
    const submitInFlight = useRef(false);

    const close = () => {
        setOpen(false);
        onClose?.();
    };

    const submit = async(event) => {
        event.preventDefault();

        if (submitInFlight.current) {
            return;
        }

        submitInFlight.current = true;
        setSubmitting(true);
        setError('');

        try {
            await onSubmit?.({ sendNotifications, triggerIntegrations });
            close();
        } catch (submitError) {
            submitInFlight.current = false;
            setSubmitting(false);
            setError(
                submitError?.message ||
                    Craft.t('formie', 'Unable to update the selected submissions.'),
            );
        }
    };

    const formId = 'formie-unmark-spam';

    return (
        <Dialog
            open={open}
            label={Craft.t('formie', 'Unmark as Spam')}
            description={Craft.t('formie', 'Choose whether any additional actions should be performed.')}
            onPkOpenChange={(event) => {
                if (!(event.detail?.open ?? event.target?.open ?? false)) {
                    close();
                }
            }}
        >
            <form
                id={formId}
                style={{ display: 'grid', gap: '16px' }}
                onSubmit={submit}
            >
                <FieldLayout
                    name="sendNotifications"
                    label={Craft.t('formie', 'Send Notifications')}
                    instructions={Craft.t('formie', 'Send the form’s email notifications after restoring the submissions.')}
                >
                    <Lightswitch
                        name="sendNotifications"
                        checked={sendNotifications}
                        disabled={submitting}
                        onCheckedChange={setSendNotifications}
                    />
                </FieldLayout>

                <FieldLayout
                    name="triggerIntegrations"
                    label={Craft.t('formie', 'Trigger Integrations')}
                    instructions={Craft.t('formie', 'Run the form’s integrations after restoring the submissions.')}
                >
                    <Lightswitch
                        name="triggerIntegrations"
                        checked={triggerIntegrations}
                        disabled={submitting}
                        onCheckedChange={setTriggerIntegrations}
                    />
                </FieldLayout>

                {error ? (
                    <Alert variant="error" size="sm" announce="assertive">
                        {error}
                    </Alert>
                ) : null}
            </form>

            <Button slot="footer" type="button" disabled={submitting} onClick={close}>
                {Craft.t('app', 'Cancel')}
            </Button>
            <Button
                slot="footer"
                type="submit"
                form={formId}
                variant="primary"
                loading={submitting}
                onClick={submit}
            >
                {Craft.t('formie', 'Unmark as Spam')}
            </Button>
        </Dialog>
    );
}
