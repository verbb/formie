import { useEffect, useRef, useState } from 'react';

import { Alert, Button, Dialog, SelectInput, Spinner } from '@verbb/plugin-kit-react/components';
import { FieldLayout } from '@verbb/plugin-kit-react/forms';
import { hostRequest } from '@verbb/plugin-kit-react/utils';

const requestErrorMessage = (error, fallback) => {
    return error?.response?.data?.message || error?.message || fallback;
};

export function SendNotificationDialog({ submissionId, onClose }) {
    const [open, setOpen] = useState(true);
    const [notifications, setNotifications] = useState([]);
    const [notificationId, setNotificationId] = useState('');
    const [notificationErrors, setNotificationErrors] = useState([]);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState('');
    const submitInFlight = useRef(false);

    useEffect(() => {
        let active = true;

        hostRequest('POST', 'formie/submissions/get-send-notification-modal-content', {
            data: { id: submissionId },
        })
            .then(({ data }) => {
                if (active) {
                    setNotifications(data.notifications || []);
                }
            })
            .catch((requestError) => {
                if (active) {
                    setError(requestErrorMessage(
                        requestError,
                        Craft.t('formie', 'Unable to load the email notifications.'),
                    ));
                }
            })
            .finally(() => {
                if (active) {
                    setLoading(false);
                }
            });

        return () => {
            active = false;
        };
    }, [submissionId]);

    const close = () => {
        setOpen(false);
        onClose?.();
    };

    const submit = async(event) => {
        event.preventDefault();

        if (submitInFlight.current) {
            return;
        }

        if (!notificationId) {
            setNotificationErrors([Craft.t('formie', 'Select an email notification.')]);
            return;
        }

        submitInFlight.current = true;
        setSubmitting(true);
        setError('');
        setNotificationErrors([]);

        try {
            await hostRequest('POST', 'formie/submissions/send-notification', {
                data: {
                    notificationId,
                    submissionId,
                },
            });
            window.location.reload();
        } catch (requestError) {
            setError(requestErrorMessage(
                requestError,
                Craft.t('formie', 'Unable to send the email notification.'),
            ));
            submitInFlight.current = false;
            setSubmitting(false);
        }
    };

    const formId = `formie-send-notification-${submissionId}`;
    const hasNotifications = notifications.length > 0;

    return (
        <Dialog
            open={open}
            label={Craft.t('formie', 'Send Email Notification')}
            description={Craft.t('formie', 'Choose an email notification to send for this submission.')}
            style={{
                '--pk-dialog-min-height': 'min(16rem, calc(100dvh - 2rem))',
            }}
            onPkOpenChange={(event) => {
                if (!(event.detail?.open ?? event.target?.open ?? false)) {
                    close();
                }
            }}
        >
            <form id={formId} onSubmit={submit}>
                {loading ? (
                    <Spinner centered />
                ) : null}

                {!loading && hasNotifications ? (
                    <FieldLayout
                        name="notificationId"
                        label={Craft.t('formie', 'Notification')}
                        instructions={Craft.t('formie', 'Select the email notification you’d like to send.')}
                        required
                        errors={notificationErrors}
                    >
                        <SelectInput
                            name="notificationId"
                            aria-label={Craft.t('formie', 'Notification')}
                            options={notifications}
                            placeholder={Craft.t('formie', 'Select an option')}
                            value={notificationId}
                            onChange={(value) => {
                                setNotificationId(String(value || ''));
                                setNotificationErrors([]);
                                setError('');
                            }}
                        />
                    </FieldLayout>
                ) : null}

                {!loading && !hasNotifications && !error ? (
                    <Alert variant="warning" size="sm">
                        {Craft.t('formie', 'No email notifications are configured for this form.')}
                    </Alert>
                ) : null}

                {error ? (
                    <Alert variant="error" announce="assertive" size="sm">
                        {error}
                    </Alert>
                ) : null}
            </form>

            <Button slot="footer" type="button" disabled={submitting} onClick={close}>
                {Craft.t('formie', 'Cancel')}
            </Button>
            <Button
                slot="footer"
                type="submit"
                form={formId}
                variant="primary"
                loading={submitting}
                disabled={loading || !hasNotifications || Boolean(error)}
                onClick={submit}
            >
                {Craft.t('formie', 'Send Email Notification')}
            </Button>
        </Dialog>
    );
}
