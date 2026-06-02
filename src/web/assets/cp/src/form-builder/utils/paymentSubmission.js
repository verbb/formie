import { getFormFields } from '@form-builder/hooks/useFormTools';

const PAYMENT_FIELD_CLASS = 'verbb\\formie\\fields\\Payment';

const isPaymentField = (field) => {
    const type = String(field?.type || '').trim();

    return type === PAYMENT_FIELD_CLASS
        || type.endsWith('\\Payment')
        || type === 'Payment';
};

const getAjaxForcedPaymentIntegrationHandles = (paymentIntegrations = []) => {
    return new Set((paymentIntegrations || []).map((integration) => {
        if (!integration?.requiresAjaxSubmission) {
            return '';
        }

        return String(integration?.handle || '').trim();
    }).filter(Boolean));
};

const isAjaxSubmissionForcedByPayments = (values, paymentIntegrations = []) => {
    const forcedHandles = getAjaxForcedPaymentIntegrationHandles(paymentIntegrations);
    if (!forcedHandles.size) {
        return false;
    }

    const fields = getFormFields(values);

    return fields.some((field) => {
        if (!isPaymentField(field)) {
            return false;
        }

        const providerHandle = String(field?.paymentIntegration || '').trim();
        return providerHandle && forcedHandles.has(providerHandle);
    });
};

export {
    isPaymentField,
    isAjaxSubmissionForcedByPayments,
    getAjaxForcedPaymentIntegrationHandles,
};
