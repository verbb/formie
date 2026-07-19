import { useState, useEffect } from 'react';
import { cn } from '@verbb/plugin-kit-react/utils';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';
import { Button, Icon, Input } from '@verbb/plugin-kit-react/components';
import { FieldLayout, useSchemaEngineContext } from '@verbb/plugin-kit-react/forms';
import { useFormValues } from '@form-builder/hooks/useFormTools';
import { takeAtLeast, getErrorMessage } from '@verbb/plugin-kit-core';

function ErrorDisplay({ error, className }) {
    const { heading, text, traceAsArray } = error;

    return (
        <div className={cn(
            'text-error',
            'leading-[1.5]',
        )}>
            <div className={cn('flex flex-col gap-1', className)}>
                {heading && (
                    <div className="flex items-center gap-1">
                        <Icon icon="triangle-exclamation" className="size-3" />

                        <div className="font-bold">{heading}</div>
                    </div>
                )}

                {text && (
                    <div className="text-[11px]">{text}</div>
                )}

                {traceAsArray && traceAsArray.length > 0 && (
                    <div className="text-[9px] font-mono">
                        {traceAsArray.map((line, index) => {
                            return (
                                <div key={index} className="whitespace-pre-wrap">
                                    {line}
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </div>
    );
}

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
                <div className="mt-2.5">
                    <ErrorDisplay
                        error={error}
                        className="text-error"
                    />
                </div>
            )}

            {success && (
                <div className="mt-2.5">
                    <div className="text-success">{successMessage}</div>
                </div>
            )}
        </FieldLayout>
    );
}

export { NotificationTest };
