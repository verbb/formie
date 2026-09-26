import { definePaymentModule } from '#modules/payments/api';

type BpointCardData = {
    cardholderName: string;
    cardNumber: string;
    expiryDate: string;
    cvn: string;
};

export const bpointModule = definePaymentModule<Record<string, never>, null, null>({
    moduleId: 'formie:bpoint',
    defaultRequiredInputSuffixes: ['bpointToken'],
    load: async() => {
        return null;
    },
    onBeforePayment: async({ field, services }) => {
        const existingValue = (field.querySelector('input[name$="[bpointToken]"]') as HTMLInputElement | null)?.value || '';
        if (existingValue.trim() !== '') {
            return true;
        }

        const cardholderName = field.querySelector<HTMLInputElement>('[data-bpoint-card="cardholder-name"]')?.value?.trim() || '';
        const cardNumber = field.querySelector<HTMLInputElement>('[data-bpoint-card="card-number"]')?.value?.replace(/\s+/g, '') || '';
        const expiryRaw = field.querySelector<HTMLInputElement>('[data-bpoint-card="expiry-date"]')?.value || '';
        const cvn = field.querySelector<HTMLInputElement>('[data-bpoint-card="security-code"]')?.value?.trim() || '';
        const expiryDate = (expiryRaw.match(/\d/g) || []).join('').slice(0, 4);

        if (!cardNumber || expiryDate.length !== 4 || !cvn) {
            services.addError('Please provide valid card details to continue.');

            return false;
        }

        const cardData: BpointCardData = {
            cardholderName,
            cardNumber,
            expiryDate,
            cvn,
        };

        services.updateInputs('bpointToken', JSON.stringify(cardData));

        return true;
    },
    onAfterSubmit: async({ services }) => {
        services.updateInputs('bpointToken', '');
    },
});
