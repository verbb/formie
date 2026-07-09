import { useEffect, useRef, useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faArrowsRotate, faCheck,
} from '@fortawesome/pro-solid-svg-icons';

import { Button } from '@verbb/plugin-kit-react/components';
import { FieldLayout } from '@verbb/plugin-kit-react/forms/Field';
import { getErrorMessage } from '@verbb/plugin-kit-react/utils';
import { useFormBuilderApp } from '@form-builder/contexts/FormBuilderAppContext';
import { useFormBuilderForm } from '@form-builder/contexts/FormBuilderFormContext';
import useAppStore from '@form-builder/hooks/useAppStore';
import { refreshIntegrationFormSettings } from '@form-builder/hooks/useFormTools';
import { IntegrationErrorMessage } from './IntegrationErrorMessage';

const SUCCESS_FEEDBACK_DURATION = 2200;

function IntegrationActionButtonField({ field }) {
    const { activeIntegrationHandle, formId } = useFormBuilderApp();
    const { getValueAtPath } = useFormBuilderForm();
    const formId = useAppStore((state) => { return state.formId; });
    const [loading, setLoading] = useState(false);
    const [actionError, setActionError] = useState(null);
    const [showActionSuccess, setShowActionSuccess] = useState(false);
    const successTimeoutRef = useRef(null);

    const actionType = String(field.actionType || '').trim() || (field.$field === 'integrationSendTestPayloadButton' ? 'testPayload' : 'refresh');
    const integrationHandle = activeIntegrationHandle || field.integrationHandle || '';
    const buttonLabel = field.buttonLabel
        || (actionType === 'testPayload' ? Craft.t('formie', 'Send Test Payload') : Craft.t('formie', 'Refresh Data'));

    useEffect(() => {
        return () => {
            if (successTimeoutRef.current) {
                clearTimeout(successTimeoutRef.current);
            }
        };
    }, []);

    const handleAction = async() => {
        if (loading) {
            return;
        }

        setShowActionSuccess(false);

        if (!integrationHandle) {
            const errorMessage = Craft.t('formie', 'Unable to run integration action: missing integration handle.');
            setActionError({
                heading: Craft.t('formie', 'Configuration error'),
                text: errorMessage,
                traceAsString: '',
            });
            return;
        }

        if (actionType === 'testPayload' && !formId) {
            const errorMessage = Craft.t('formie', 'You must save your form before sending a test payload.');
            setActionError({
                heading: Craft.t('formie', 'Validation error'),
                text: errorMessage,
                traceAsString: '',
            });
            return;
        }

        setActionError(null);

        const settingsPath = `settings.integrations.${integrationHandle}`;
        const currentSettings = getValueAtPath(settingsPath, {}) || {};

        setLoading(true);
        const result = await refreshIntegrationFormSettings(integrationHandle, currentSettings, {
            formId,
        });
        setLoading(false);

        if (typeof window !== 'undefined') {
            window.dispatchEvent(new CustomEvent('formie:integration-settings-refreshed', {
                detail: {
                    handle: integrationHandle,
                    ok: result?.ok === true,
                    error: result?.error || null,
                    actionType,
                },
            }));
        }

        if (result?.ok !== true) {
            const fallbackError = actionType === 'testPayload'
                ? Craft.t('formie', 'Failed to send test payload.')
                : Craft.t('formie', 'Failed to refresh integration data.');
            const errorMessage = result?.error || fallbackError;
            const parsedError = result?.errorObject ? getErrorMessage(result.errorObject) : null;
            setActionError(parsedError?.text ? parsedError : {
                heading: Craft.t('formie', 'Internal Server Error'),
                text: errorMessage,
                traceAsString: '',
            });
            return;
        }

        setShowActionSuccess(true);

        if (successTimeoutRef.current) {
            clearTimeout(successTimeoutRef.current);
        }

        successTimeoutRef.current = setTimeout(() => {
            setShowActionSuccess(false);
            successTimeoutRef.current = null;
        }, SUCCESS_FEEDBACK_DURATION);
    };

    const content = (
        <div>
            <Button
                type="button"
                onClick={handleAction}
                loading={loading}
                className={showActionSuccess ? 'relative text-green-600' : 'relative'}
            >
                <span className={`inline-flex items-center gap-1 ${showActionSuccess ? 'text-transparent' : ''}`}>
                    <FontAwesomeIcon icon={faArrowsRotate} className="size-3" />
                    {buttonLabel}
                </span>

                {showActionSuccess && (
                    <FontAwesomeIcon
                        icon={faCheck}
                        className="absolute left-1/2 top-1/2 size-3 -translate-x-1/2 -translate-y-1/2"
                    />
                )}
            </Button>

            <IntegrationErrorMessage error={actionError} className="mt-2" />
        </div>
    );

    if (!field.label && !field.instructions) {
        return content;
    }

    return (
        <FieldLayout
            name={field.name}
            label={field.label}
            instructions={field.instructions}
            required={field.required}
            withControl={false}
        >
            {content}
        </FieldLayout>
    );
}

export { IntegrationActionButtonField };
