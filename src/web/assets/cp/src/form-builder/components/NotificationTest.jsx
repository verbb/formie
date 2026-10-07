import { useState, useEffect } from 'react';
import { cn } from '@verbb/plugin-kit-react/utils';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';
import { Alert, Button, Input } from '@verbb/plugin-kit-react/components';
import { FieldLayout, useSchemaEngineContext } from '@verbb/plugin-kit-react/forms';
import { useFormValues } from '@form-builder/hooks/useFormTools';
import { takeAtLeast, getErrorMessage } from '@verbb/plugin-kit-core';

function NotificationTest({ userEmail }) {
    const form = useSchemaEngineContext();
    const formValues = useFormValues();
    const [to, setTo] = useState('');
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(false);
    const [loading, setLoading] = useState(false);
    const [successMessage, setSuccessMessage] = useState('');

    const t = useTranslation();

    // Populate the current email on mount
    useEffect(() => {
        if (userEmail) {
            setTo(userEmail);
        }
    }, [userEmail]);

    const sendTestEmail = async() => {
        setError(null);
        setSuccess(false);
        setLoading(true);
        setSuccessMessage('');

        const data = {
            formId: formValues?.id,
            handle: formValues?.handle,
            isStencil: formValues?.isStencil,
            notification: form?.store?.state?.values ?? {},
            to,
        };

        try {
            const response = await takeAtLeast(500)(
                Craft.sendActionRequest('POST', 'formie/email/send-test-email', { data }),
            );

            if (response.data.success) {
                setSuccess(true);
                setSuccessMessage(t('Email sent successfully. Please check your email.'));
            }

            if (response.data.error) {
                throw response.data.error;
            }
        } catch (error) {
            setError(getErrorMessage(error));
        }

        setLoading(false);
    };

    return (
        <FieldLayout
            name="to"
            label={t('Send Test Email')}
            instructions={t('Use the form below to send a test email to the nominated email address.')}
        >
            <div className={cn('flex items-center gap-4')}>
                <Input
                    id="to"
                    value={to}
                    onChange={(e) => { return setTo(e.target.value); }}
                    type="text"
                    placeholder="Enter email address"
                />

                <Button
                    variant="primary"
                    onClick={sendTestEmail}
                    loading={loading}
                >
                    {t('Send Test Email')}
                </Button>
            </div>

            {error && (
                <Alert
                    variant="error"
                    size="sm"
                    heading={error.heading}
                    announce="assertive"
                    detailsLabel={t('Show error details')}
                    copyLabel={t('Copy error details')}
                    copyable={Boolean(error.traceAsArray?.length)}
                    className="mt-2.5"
                >
                    {error.text}
                    {error.traceAsArray?.length ? <pre slot="details">{error.traceAsArray.join('\n\n')}</pre> : null}
                </Alert>
            )}

            {success && (
                <Alert variant="success" size="sm" announce="polite" className="mt-2.5">
                    {successMessage}
                </Alert>
            )}
        </FieldLayout>
    );
}

export { NotificationTest };
