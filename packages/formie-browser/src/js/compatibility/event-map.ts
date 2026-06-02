export type LegacyBridgeDisposition = 'safe' | 'approximate';

export type LegacyCompatibilityOptions = boolean | {
    legacyDomEvents?: boolean;
    legacyValidatorEvents?: boolean;
};

export type ResolvedLegacyCompatibilityOptions = {
    enabled: boolean;
    legacyDomEvents: boolean;
    legacyValidatorEvents: boolean;
};

export type LegacyDomEventBridge = {
    legacyEvent: string;
    canonicalEvent: string;
    disposition: LegacyBridgeDisposition;
    target?: 'form' | 'document';
};

export type LegacyValidatorEventBridge = {
    legacyEvent: string;
    canonicalEvent: string;
    disposition: 'safe';
};

export const LEGACY_FORMIE_DOM_EVENT_BRIDGES: LegacyDomEventBridge[] = [
    { legacyEvent: 'onFormieLoaded', canonicalEvent: 'formie:mount:after', disposition: 'approximate', target: 'document' },
    { legacyEvent: 'onFormieInit', canonicalEvent: 'formie:mount:after', disposition: 'approximate', target: 'document' },
    { legacyEvent: 'onFormieReady', canonicalEvent: 'formie:mount:after', disposition: 'safe' },
    { legacyEvent: 'onAfterFormieSubmit', canonicalEvent: 'formie:submit:result', disposition: 'safe' },
    { legacyEvent: 'onFormieSubmitError', canonicalEvent: 'formie:submit:result', disposition: 'safe' },
    { legacyEvent: 'onFormiePageToggle', canonicalEvent: 'formie:page:navigate:after', disposition: 'safe' },
    { legacyEvent: 'onBeforeFormieSubmit', canonicalEvent: 'formie:submit:before', disposition: 'approximate' },
    { legacyEvent: 'onFormieValidate', canonicalEvent: 'formie:stage:validate:before', disposition: 'approximate' },
    { legacyEvent: 'onAfterFormieValidate', canonicalEvent: 'formie:stage:validate:after', disposition: 'approximate' },
    { legacyEvent: 'onFormieSubmit', canonicalEvent: 'formie:submit:after', disposition: 'approximate' },
];

export const LEGACY_FORMIE_VALIDATOR_EVENT_BRIDGES: LegacyValidatorEventBridge[] = [
    { legacyEvent: 'formieValidatorInitialized', canonicalEvent: 'formie:validator:ready', disposition: 'safe' },
    { legacyEvent: 'formieValidatorDestroyed', canonicalEvent: 'formie:validator:destroy', disposition: 'safe' },
    { legacyEvent: 'formieValidatorShowError', canonicalEvent: 'formie:validator:show-error', disposition: 'safe' },
    { legacyEvent: 'formieValidatorClearError', canonicalEvent: 'formie:validator:clear-error', disposition: 'safe' },
];

export function resolveLegacyCompatibilityOptions(options: LegacyCompatibilityOptions | undefined): ResolvedLegacyCompatibilityOptions {
    if (!options) {
        return {
            enabled: false,
            legacyDomEvents: false,
            legacyValidatorEvents: false,
        };
    }

    if (options === true) {
        return {
            enabled: true,
            legacyDomEvents: true,
            legacyValidatorEvents: true,
        };
    }

    const legacyDomEvents = options.legacyDomEvents ?? true;
    const legacyValidatorEvents = options.legacyValidatorEvents ?? true;

    return {
        enabled: legacyDomEvents || legacyValidatorEvents,
        legacyDomEvents,
        legacyValidatorEvents,
    };
}
