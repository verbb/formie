import { useEffect, useMemo, useRef, useState } from 'react';

import { Alert, Button, Dialog, Input, SelectInput, Spinner } from '@verbb/plugin-kit-react/components';
import { FieldLayout } from '@verbb/plugin-kit-react/forms';
import { hostRequest } from '@verbb/plugin-kit-react/utils';

const errorMessage = (error, fallback) => {
    return error?.response?.data?.message || error?.message || fallback;
};

const previewRows = (notification) => {
    if (!notification) {
        return [];
    }

    const from = notification.fromName && notification.from
        ? `${notification.fromName} <${notification.from}>`
        : notification.fromName || notification.from;

    return [
        [Craft.t('formie', 'Cc:'), notification.cc],
        [Craft.t('formie', 'Bcc:'), notification.bcc],
        [Craft.t('formie', 'Subject:'), notification.subject],
        [Craft.t('formie', 'Reply To:'), notification.replyTo],
        [Craft.t('formie', 'From:'), from],
        [Craft.t('formie', 'Sender:'), notification.sender],
    ].filter(([, value]) => { return Boolean(value); });
};

function EmailPreview({ notification }) {
    const rows = useMemo(() => {
        return previewRows(notification);
    }, [notification]);

    return (
        <section className="fui-email-preview" aria-label={Craft.t('formie', 'Email preview')}>
            <div className="fui-email-header" />

            {rows.length > 0 ? (
                <dl className="fui-email-meta-list">
                    {rows.map(([label, value]) => {
                        return (
                            <div className="fui-email-meta" key={label}>
                                <dt className="fui-email-meta-label">{label}</dt>
                                <dd className="fui-email-meta-value">{value}</dd>
                            </div>
                        );
                    })}
                </dl>
            ) : null}

            <div className="fui-email-body">
                {notification.htmlBody ? (
                    <iframe
                        className="email-iframe"
                        title={Craft.t('formie', 'Email preview')}
                        srcDoc={notification.htmlBody}
                        sandbox="allow-same-origin"
                        onLoad={(event) => {
                            const bodyHeight = event.currentTarget.contentWindow?.document.body.scrollHeight;

                            if (bodyHeight) {
                                event.currentTarget.style.height = `${bodyHeight + 20}px`;
                            }
                        }}
                    />
                ) : (
                    <pre className="fui-email-text">{notification.body}</pre>
                )}
            </div>

            <div className="fui-email-footer" />
        </section>
    );
}

export function ResendNotificationDialog({ mode, ids = [], onClose }) {
    const isBulk = mode === 'bulk';
    const [open, setOpen] = useState(true);
    const [notification, setNotification] = useState(null);
    const [recipientsType, setRecipientsType] = useState('original');
    const [recipients, setRecipients] = useState('');
    const [recipientErrors, setRecipientErrors] = useState([]);
    const [loading, setLoading] = useState(!isBulk);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState('');
    const submitInFlight = useRef(false);

    useEffect(() => {
        if (isBulk) {
            return undefined;
        }

        let active = true;
        setLoading(true);
        setError('');

        hostRequest('POST', 'formie/sent-notifications/get-resend-modal-data', {
            data: { id: ids[0] },
        })
            .then(({ data }) => {
                if (!active) {
                    return;
                }

                setNotification(data.notification);
                setRecipients(data.notification?.to || '');
            })
            .catch((requestError) => {
                if (active) {
                    setError(errorMessage(requestError, Craft.t('formie', 'Unable to load the sent notification.')));
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
    }, [ids, isBulk]);

    const close = () => {
        setOpen(false);
        onClose?.();
    };

    const submit = async(event) => {
        event.preventDefault();

        if (submitInFlight.current) {
            return;
        }

        const requiresRecipients = !isBulk || recipientsType === 'custom';
        if (requiresRecipients && !recipients.trim()) {
            setRecipientErrors([Craft.t('formie', 'No recipients provided.')]);
            return;
        }

        submitInFlight.current = true;
        setSubmitting(true);
        setError('');
        setRecipientErrors([]);

        const action = isBulk
            ? 'formie/sent-notifications/bulk-resend'
            : 'formie/sent-notifications/resend';
        const data = isBulk
            ? { ids, recipientsType, to: recipients.trim() }
            : { id: ids[0], to: recipients.trim() };

        try {
            await hostRequest('POST', action, { data });
            window.location.reload();
        } catch (requestError) {
            setError(errorMessage(requestError, Craft.t('formie', 'Unable to resend the notification.')));
            submitInFlight.current = false;
            setSubmitting(false);
        }
    };

    const dialogLabel = isBulk
        ? Craft.t('formie', 'Bulk Resend Email Notifications')
        : Craft.t('formie', 'Resend Email Notification');
    const bulkDescription = ids.length === 1
        ? Craft.t('formie', 'You are about to resend 1 notification email. Send it to the original recipients, or choose custom recipients.')
        : Craft.t('formie', 'You are about to resend {count} notification emails. Send each email to its original recipients, or choose custom recipients.', { count: ids.length });
    const description = isBulk
        ? bulkDescription
        : Craft.t('formie', 'Resend this email to the recipients of your choosing.');
    const formId = `formie-resend-${isBulk ? 'bulk' : ids[0]}`;

    return (
        <Dialog
            open={open}
            label={dialogLabel}
            description={description}
            size="wide"
            style={isBulk ? undefined : { '--pk-dialog-width': 'calc(42rem + 200px)' }}
            data-formie-resend-dialog={isBulk ? 'bulk' : 'single'}
            onPkOpenChange={(event) => {
                if (!(event.detail?.open ?? event.target?.open ?? false)) {
                    close();
                }
            }}
        >
            <form id={formId} className="formie-resend-dialog" onSubmit={submit}>
                {loading ? (
                    <div className="formie-resend-dialog__loading">
                        <Spinner centered />
                        <span>{Craft.t('formie', 'Loading sent notification…')}</span>
                    </div>
                ) : null}

                {!loading && isBulk ? (
                    <>
                        <FieldLayout
                            name="recipientsType"
                            label={Craft.t('formie', 'Recipients')}
                        >
                            <SelectInput
                                name="recipientsType"
                                aria-label={Craft.t('formie', 'Recipients')}
                                options={[
                                    { label: Craft.t('formie', 'Original Recipients'), value: 'original' },
                                    { label: Craft.t('formie', 'Custom Recipients'), value: 'custom' },
                                ]}
                                value={recipientsType}
                                onChange={(value) => {
                                    setRecipientsType(String(value || 'original'));
                                    setRecipientErrors([]);
                                    setError('');
                                }}
                            />
                        </FieldLayout>

                        {recipientsType === 'custom' ? (
                            <FieldLayout
                                name="to"
                                label={Craft.t('formie', 'Custom Recipients')}
                                instructions={Craft.t('formie', 'Provide recipients for each email notification. For multiple recipients, separate each with a comma.')}
                                required
                                errors={recipientErrors}
                            >
                                <Input
                                    name="to"
                                    aria-label={Craft.t('formie', 'Custom Recipients')}
                                    value={recipients}
                                    autoFocus
                                    onChange={(event) => {
                                        setRecipients(event.target.value);
                                        setRecipientErrors([]);
                                        setError('');
                                    }}
                                />
                            </FieldLayout>
                        ) : null}
                    </>
                ) : null}

                {!loading && !isBulk && notification ? (
                    <>
                        <FieldLayout
                            name="to"
                            label={Craft.t('formie', 'Recipients')}
                            instructions={Craft.t('formie', 'For multiple recipients, separate each with a comma.')}
                            required
                            errors={recipientErrors}
                        >
                            <Input
                                name="to"
                                aria-label={Craft.t('formie', 'Recipients')}
                                value={recipients}
                                autoFocus
                                onChange={(event) => {
                                    setRecipients(event.target.value);
                                    setRecipientErrors([]);
                                    setError('');
                                }}
                            />
                        </FieldLayout>

                        <EmailPreview notification={notification} />
                    </>
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
                disabled={loading || Boolean(error && !notification && !isBulk)}
                onClick={submit}
            >
                {isBulk
                    ? Craft.t('formie', 'Resend Email Notifications')
                    : Craft.t('formie', 'Resend Email Notification')}
            </Button>
        </Dialog>
    );
}
