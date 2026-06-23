import { useState, useEffect, useRef } from 'react';
import { cn, takeAtLeast } from '@verbb/plugin-kit-react/utils';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';
import { Label, Button } from '@verbb/plugin-kit-react/components';
import { LargeErrorState } from '@utils';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faExclamationTriangle } from '@fortawesome/pro-solid-svg-icons';
import { useSchemaEngineContext } from '@verbb/plugin-kit-react/forms/engine/context';
import { useFormValues } from '@form-builder/hooks/useFormTools';

function NotificationPreview() {
    const form = useSchemaEngineContext();
    const formValues = useFormValues();
    const [email, setEmail] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const iframeRef = useRef(null);

    const t = useTranslation();

    useEffect(() => {
        updatePreview();
    }, []);

    const updateiFrame = () => {
        if (iframeRef.current && email?.body) {
            const doc = iframeRef.current.contentWindow.document;

            if (doc) {
                doc.open();
                doc.write(`<html><head><title></title></head><body>${email.body}</body></html>`);
                doc.close();
            }
        }
    };

    useEffect(() => {
        if (email) {
            updateiFrame();
        }
    }, [email]);

    const updatePreview = async() => {
        setError(null);
        setLoading(true);

        const data = {
            formId: formValues?.id,
            handle: formValues?.handle,
            isStencil: formValues?.isStencil,
            notification: form?.store?.state?.values ?? {},
        };

        try {
            const response = await takeAtLeast(500)(
                Craft.sendActionRequest('POST', 'formie/email/preview', { data }),
            );

            if (response.data.error) {
                throw response.data.error;
            }

            setEmail(response.data);
        } catch (error) {
            setError(error);
        }

        setLoading(false);
    };

    const emailAddress = (object) => {
        if (!object) {
            return '';
        }

        const [email] = Object.keys(object);

        if (object[email]) {
            return `${object[email]} <${email}>`;
        }

        return email;
    };

    return (
        <div>
            <div className={cn(
                'flex items-center justify-between mb-2',
            )}>
                <div className="">
                    <Label>{t('Email Preview')}</Label>
                    <p>{t('The example below shows a preview of this email notification.')}</p>
                </div>

                <div className="">
                    <Button
                        variant="primary"
                        type="button"
                        onClick={updatePreview}
                        loading={loading}
                    >
                        {t('Refresh')}
                    </Button>
                </div>
            </div>

            <div className={cn(
                'relative bg-white rounded',
                'shadow-[0_0_0_1px_rgba(49,49,93,0.05),0_2px_5px_0_rgba(49,49,93,0.075),0_1px_3px_0_rgba(49,49,93,0.15)]',
            )}>
                <div className={cn(
                    'w-full h-5 bg-[#f5f8fc]',
                    'border-b border-[#e5e5e5] border-solid',
                    'rounded-t',
                )}></div>

                <div className={cn(
                    'flex text-[13px] py-1',
                    'border-b border-[#e5e5e5] border-solid',
                )}>
                    <div className={cn(
                        'w-[75px] text-right font-bold text-[#60758a]',
                        'mr-2 flex-none',
                    )}>{t('To:')}</div>
                    <div>{emailAddress(email?.to)}</div>
                </div>

                {email?.cc && (
                    <div className={cn(
                        'flex text-[13px] py-1',
                        'border-b border-[#e5e5e5] border-solid',
                    )}>
                        <div className={cn(
                            'w-[75px] text-right font-bold text-[#60758a]',
                            'mr-2 flex-none',
                        )}>{t('Cc:')}</div>
                        <div>{emailAddress(email.cc)}</div>
                    </div>
                )}

                {email?.bcc && (
                    <div className={cn(
                        'flex text-[13px] py-1',
                        'border-b border-[#e5e5e5] border-solid',
                    )}>
                        <div className={cn(
                            'w-[75px] text-right font-bold text-[#60758a]',
                            'mr-2 flex-none',
                        )}>{t('Bcc:')}</div>
                        <div>{emailAddress(email.bcc)}</div>
                    </div>
                )}

                <div className={cn(
                    'flex text-[13px] py-1',
                    'border-b border-[#e5e5e5] border-solid',
                )}>
                    <div className={cn(
                        'w-[75px] text-right font-bold text-[#60758a]',
                        'mr-2 flex-none',
                    )}>{t('Subject:')}</div>
                    <div>{email?.subject}</div>
                </div>

                {email?.replyTo && (
                    <div className={cn(
                        'flex text-[13px] py-1',
                        'border-b border-[#e5e5e5] border-solid',
                    )}>
                        <div className={cn(
                            'w-[75px] text-right font-bold text-[#60758a]',
                            'mr-2 flex-none',
                        )}>{t('Reply To:')}</div>
                        <div>{emailAddress(email.replyTo)}</div>
                    </div>
                )}

                <div className={cn(
                    'flex text-[13px] py-1',
                    'border-b border-[#e5e5e5] border-solid',
                )}>
                    <div className={cn(
                        'w-[75px] text-right font-bold text-[#60758a]',
                        'mr-2 flex-none',
                    )}>{t('From:')}</div>
                    <div>{emailAddress(email?.from)}</div>
                </div>

                <div className={cn(
                    'min-h-[20rem] p-4',
                )}>
                    {email?.body && email.body.length ? (
                        <iframe
                            ref={iframeRef}
                            src="about:blank"
                            sandbox="allow-same-origin"
                            frameBorder="0"
                            style={{ height: '100vh', width: '100%' }}
                        />
                    ) : (
                        <span className="flex items-center gap-1 text-warning">
                            <FontAwesomeIcon icon={faExclamationTriangle} className="block size-4 shrink-0" />
                            <span className="flex-1">{email?.warning || t('No email content.')}</span>
                        </span>
                    )}
                </div>

                <div className={cn(
                    'w-full h-5 bg-[#f5f8fc]',
                    'border-t border-[#e5e5e5] border-solid',
                    'rounded-b',
                )}></div>

                {loading && (
                    <div className={cn(
                        'absolute w-full h-full top-0 left-0',
                        'bg-white/70',
                    )}>
                        <div className={cn(
                            'absolute top-1/2 left-1/2 bg-white',
                        )}></div>
                    </div>
                )}

                {error && (
                    <div className={cn(
                        'absolute w-full h-full top-0 left-0',
                        'bg-white/70',
                        'flex items-center justify-center',
                    )}>
                        <LargeErrorState
                            error={error}
                            message={t('Unable to generate email preview.')}
                            detailsLabel={t('Show error details')}
                            actionLabel={t('Try Again')}
                            onAction={updatePreview}
                            containerClassName="flex w-full h-full items-center justify-center"
                        />
                    </div>
                )}
            </div>
        </div>
    );
}

export { NotificationPreview };
