import opayoCss from '#theme-css/integrations/_opayo.css?inline';

import { definePaymentModule } from '#modules/payments/api';
import { ensureModuleStyles } from '#modules/styles';
import { createDebug } from '#utils/debug';
import { getPaymentProviderActionEventName } from '#utils/event-names';
import { loadScriptAndEnsureGlobal } from '#utils/scripts';

ensureModuleStyles('opayo', [opayoCss]);

type SagePayTokeniseResult = {
    success: boolean;
    cardIdentifier?: string;
    errors?: Array<{ message: string }>;
};

type SagePayOwnFormGlobal = {
    (opts: { merchantSessionKey: string }): {
        tokeniseCardDetails: (opts: {
            cardDetails: Record<string, string>;
            onTokenised: (result: SagePayTokeniseResult) => void;
        }) => void;
    };
};

type SagePayCheckoutInstance = {
    tokenise: (opts?: { newMerchantSessionKey?: string }) => void;
    destroy: () => void;
};

type SagePayCheckoutGlobal = {
    (opts: {
        merchantSessionKey: string;
        containerSelector: string;
        onTokenise?: (result: SagePayTokeniseResult) => void;
        reusableCardIdentifier?: string;
    }): SagePayCheckoutInstance;
};

declare global {
    interface Window {
        sagepayOwnForm?: SagePayOwnFormGlobal;
        sagepayCheckout?: SagePayCheckoutGlobal;
    }
}

type OpayoCheckoutMode = 'ownForm' | 'dropIn';

type OpayoProviderOptions = {
    useSandbox?: boolean;
    handle?: string;
    sessionToken?: string | null;
    checkoutMode?: OpayoCheckoutMode;
};

type OpayoDropInWidget = {
    checkout: SagePayCheckoutInstance;
    merchantSessionKey: string;
    pendingAuthorize: ((success: boolean) => void) | null;
    retriedTokenise: boolean;
};

const SCRIPT_ID = 'FORMIE_OPAYO_SCRIPT';
const SCRIPT_SRC_LIVE = 'https://live.opayo.eu.elavon.com/api/v1/js/sagepay.js';
const SCRIPT_SRC_SANDBOX = 'https://sandbox.opayo.eu.elavon.com/api/v1/js/sagepay.js';
const DROP_IN_SELECTOR = '[data-formie-opayo-drop-in]';
const debug = createDebug('payments', 'opayo');
const CHALLENGE_EVENT = getPaymentProviderActionEventName('opayo', 'challenge');
const CHALLENGE_RESPONSE_MESSAGE = 'formie:payment:opayo:challenge:response';

function isDropInCheckoutMode(provider: OpayoProviderOptions): boolean {
    return provider.checkoutMode === 'dropIn';
}

async function requestMerchantSessionKey(args: {
    form: HTMLFormElement;
    handle: string;
    sessionToken: string;
    services: {
        addError: (message: string) => void;
    };
}): Promise<string | null> {
    const { form, handle, sessionToken, services } = args;
    const formData = new FormData();
    formData.append('action', 'formie/payment-webhooks/process-callback');
    formData.append('merchantSessionKey', 'true');
    formData.append('handle', handle);
    formData.append('sessionToken', sessionToken);

    try {
        const res = await fetch(form.action, {
            method: 'POST',
            body: formData,
        });

        if (res.status < 200 || res.status >= 300) {
            services.addError(`${res.status}: ${res.statusText}`);
            debug.warn('Merchant session request failed.', {
                status: res.status,
                statusText: res.statusText,
            });

            return null;
        }

        const response = await res.json();
        const merchantSessionKey = response.merchantSessionKey;

        if (!merchantSessionKey) {
            services.addError('Unable to get merchant session.');
            debug.warn('merchantSessionKey missing in callback response.');

            return null;
        }

        return merchantSessionKey as string;
    } catch {
        services.addError('Network error. Please try again.');
        debug.warn('Network error requesting merchant session.');

        return null;
    }
}

function ensureDropInContainerId(container: HTMLElement): string {
    if (!container.id) {
        container.id = `formie-opayo-drop-in-${Math.random().toString(36).slice(2, 9)}`;
    }

    return container.id;
}

export const opayoModule = definePaymentModule<OpayoProviderOptions, null, OpayoDropInWidget | null>({
    id: 'opayo',
    defaultRequiredInputSuffixes: ['opayoTokenId'],
    load: async(ctx) => {
        const { provider } = ctx.options;
        const useSandbox = Boolean(provider.useSandbox);
        const src = useSandbox ? SCRIPT_SRC_SANDBOX : SCRIPT_SRC_LIVE;
        const globalName = isDropInCheckoutMode(provider) ? 'sagepayCheckout' : 'sagepayOwnForm';

        await loadScriptAndEnsureGlobal<Window['sagepayOwnForm'] | Window['sagepayCheckout']>(globalName, {
            id: SCRIPT_ID,
            src,
            timeoutMs: 10000,
        });

        return null;
    },
    mount: async({ field, services, provider }) => {
        if (!isDropInCheckoutMode(provider)) {
            return null;
        }

        const form = services.form;
        const sagepayCheckout = window.sagepayCheckout;
        const container = field.querySelector<HTMLElement>(DROP_IN_SELECTOR);

        if (!form?.action) {
            services.addError('Form action is missing.');
            debug.warn('Missing form action before drop-in mount.');

            return null;
        }

        if (!sagepayCheckout) {
            services.addError('Opayo script failed to load.');
            debug.warn('sagepayCheckout global not available.');

            return null;
        }

        if (!container) {
            services.addError('Opayo drop-in container is missing.');
            debug.warn('Drop-in container not found in payment field.');

            return null;
        }

        const handle = (provider.handle || 'opayo') as string;
        const merchantSessionKey = await requestMerchantSessionKey({
            form,
            handle,
            sessionToken: provider.sessionToken || '',
            services,
        });

        if (!merchantSessionKey) {
            return null;
        }

        const containerId = ensureDropInContainerId(container);
        const widget: OpayoDropInWidget = {
            checkout: null as unknown as SagePayCheckoutInstance,
            merchantSessionKey,
            pendingAuthorize: null,
            retriedTokenise: false,
        };

        widget.checkout = sagepayCheckout({
            merchantSessionKey,
            containerSelector: `#${containerId}`,
            onTokenise: (result) => {
                const resolve = widget.pendingAuthorize;
                widget.pendingAuthorize = null;

                if (!resolve) {
                    debug.warn('Drop-in tokenisation completed without a pending authorize step.');
                    return;
                }

                if (result.success && result.cardIdentifier) {
                    services.updateInputs('opayoTokenId', result.cardIdentifier);
                    services.updateInputs('opayoSessionKey', widget.merchantSessionKey);
                    debug.log('Drop-in tokenization succeeded.', {
                        hasCardIdentifier: !!result.cardIdentifier,
                    });
                    resolve(true);

                    return;
                }

                if (!widget.retriedTokenise) {
                    widget.retriedTokenise = true;
                    void requestMerchantSessionKey({
                        form,
                        handle,
                        sessionToken: provider.sessionToken || '',
                        services,
                    }).then((newMerchantSessionKey) => {
                        if (!newMerchantSessionKey) {
                            services.addError(result.errors?.[0]?.message || 'Tokenization failed.');
                            debug.warn('Drop-in tokenization failed after session refresh.', result);
                            resolve(false);

                            return;
                        }

                        widget.merchantSessionKey = newMerchantSessionKey;
                        widget.pendingAuthorize = resolve;
                        widget.checkout.tokenise({ newMerchantSessionKey });
                    });

                    return;
                }

                services.addError(result.errors?.[0]?.message || 'Tokenization failed.');
                debug.warn('Drop-in tokenization failed.', result);
                resolve(false);
            },
        });

        debug.log('Drop-in checkout mounted.', { containerId });

        return widget;
    },
    unmount: async({ widget }) => {
        widget?.checkout?.destroy?.();
    },
    onBeforeAuthorize: async(args) => {
        const { field, services, options, provider, widget } = args;
        const handle = (provider.handle || 'opayo') as string;
        const form = services.form;

        if (!form?.action) {
            services.addError('Form action is missing.');
            debug.warn('Missing form action before authorize.');

            return false;
        }

        if (isDropInCheckoutMode(provider)) {
            if (!widget) {
                services.addError('Opayo drop-in checkout is not ready.');
                debug.warn('Drop-in authorize requested before widget mount.');

                return false;
            }

            widget.retriedTokenise = false;

            return new Promise<boolean>((resolve) => {
                widget.pendingAuthorize = resolve;
                widget.checkout.tokenise();
            });
        }

        const sagepayOwnForm = window.sagepayOwnForm;

        if (!sagepayOwnForm) {
            services.addError('Opayo script failed to load.');
            debug.warn('sagepayOwnForm global not available.');

            return false;
        }

        const cardholderName = field.querySelector<HTMLInputElement>('[data-opayo-card="cardholder-name"]')?.value ?? '';
        let cardNumber = field.querySelector<HTMLInputElement>('[data-opayo-card="card-number"]')?.value ?? '';
        let expiryDate = field.querySelector<HTMLInputElement>('[data-opayo-card="expiry-date"]')?.value ?? '';
        const securityCode = field.querySelector<HTMLInputElement>('[data-opayo-card="security-code"]')?.value ?? '';

        cardNumber = cardNumber.replace(/[\s/]/g, '');
        expiryDate = expiryDate.replace(/[\s/]/g, '');

        const merchantSessionKey = await requestMerchantSessionKey({
            form,
            handle,
            sessionToken: provider.sessionToken || '',
            services,
        });

        if (!merchantSessionKey) {
            return false;
        }

        return new Promise<boolean>((resolve) => {
            sagepayOwnForm({ merchantSessionKey }).tokeniseCardDetails({
                cardDetails: {
                    cardholderName,
                    cardNumber,
                    expiryDate,
                    securityCode,
                },
                onTokenised: (result) => {
                    if (result.success && result.cardIdentifier) {
                        services.updateInputs('opayoTokenId', result.cardIdentifier);
                        services.updateInputs('opayoSessionKey', merchantSessionKey);
                        debug.log('Tokenization succeeded.', {
                            hasCardIdentifier: !!result.cardIdentifier,
                        });

                        resolve(true);
                    } else {
                        services.addError(result.errors?.[0]?.message || 'Tokenization failed.');
                        debug.warn('Tokenization failed.', result);
                        resolve(false);
                    }
                },
            });
        });
    },
    setup: async(ctx) => {
        const { services } = ctx;
        const field = ctx.target;

        type Opayo3DSData = {
            acsUrl?: string;
            creq?: string;
            threeDSSessionData?: string;
            returnUrl?: string;
            redirectUrl?: string;
        };

        let activeDialog: HTMLElement | null = null;
        let handling3DSResponse = false;

        const closeDialog = () => {
            if (activeDialog?.parentNode) {
                activeDialog.parentNode.removeChild(activeDialog);
            }

            activeDialog = null;
        };

        const unbind3DS = services.events.onForm(CHALLENGE_EVENT, ((event: CustomEvent<{ data?: Opayo3DSData }>) => {
            const data = event.detail?.data;
            if (!data?.acsUrl || !data?.creq) return;
            handling3DSResponse = false;
            debug.log('Received payment challenge event.', {
                hasAcsUrl: !!data.acsUrl,
                hasCreq: !!data.creq,
            });

            const form = services.form;
            const sessionInput = form?.querySelector<HTMLInputElement>('input[name*="opayoSessionKey"]');
            const md = sessionInput?.value || '';

            const dialog = document.createElement('div');
            dialog.className = 'formie-modal';
            dialog.id = `formie-opayo-dialog-${Math.random().toString(36).slice(2, 9)}`;
            dialog.innerHTML = `
                <div class="formie-modal-backdrop" data-dialog-close></div>
                <div class="formie-modal-content">
                    <div class="formie-loading formie-loading-large" style="--formie-loading-width: 3rem; --formie-loading-height: 3rem; top: 50%; margin-top: -1.5rem;"></div>
                    <iframe width="100%" height="100%" style="width: 100%; height: 100%; position: relative; z-index: 1;"></iframe>
                </div>
            `;
            const iframe = dialog.querySelector('iframe');
            const esc = (s: string) => s.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
            const callbackUrl = data.returnUrl || data.redirectUrl || '';
            const html = `<form action="${esc(data.acsUrl)}" method="post">
                <input type="hidden" name="creq" value="${esc(data.creq || '')}" />
                <input type="hidden" name="threeDSSessionData" value="${esc(data.threeDSSessionData || '')}" />
                <input type="hidden" name="MD" value="${esc(md)}" />
                <input type="hidden" name="TermUrl" value="${esc(callbackUrl)}" />
                <input type="hidden" name="ThreeDSNotificationURL" value="${esc(callbackUrl)}" />
            </form><script>document.forms[0].submit();</script>`;
            closeDialog();
            document.body.appendChild(dialog);
            activeDialog = dialog;
            if (iframe?.contentWindow) {
                iframe.contentWindow.document.open();
                iframe.contentWindow.document.write(html);
                iframe.contentWindow.document.close();
            }
        }) as EventListener);

        const messageHandler = (event: MessageEvent) => {
            if (event.data?.message !== CHALLENGE_RESPONSE_MESSAGE) {
                return;
            }

            // Only the module instance that currently owns an active 3DS dialog
            // should consume the callback. This avoids cross-instance duplicate
            // submits when multiple listeners exist.
            if (!activeDialog) {
                debug.log('Ignoring 3DS response without active dialog.');
                return;
            }

            if (handling3DSResponse) {
                debug.warn('Ignoring duplicate 3DS response while processing.');
                return;
            }

            handling3DSResponse = true;
            debug.log('Received payment challenge response message.', event.data?.value);

            closeDialog();
            services.removeError();

            if (event.data?.value?.error) {
                services.addError(event.data.value.error.message);
                services.releaseSubmitLoading();
                handling3DSResponse = false;

                return;
            }

            services.updateInputs('opayo3DSComplete', event.data.value?.transactionId ?? '');

            services.triggerSubmit();
        };

        window.addEventListener('message', messageHandler);

        return {
            destroy: () => {
                unbind3DS();
                window.removeEventListener('message', messageHandler);
                closeDialog();
                handling3DSResponse = false;
            },
        };
    },
    onAfterSubmit: async({ services }) => {
        services.updateInputs(['opayoTokenId', 'opayoSessionKey', 'opayo3DSComplete'], '');
    },
});
