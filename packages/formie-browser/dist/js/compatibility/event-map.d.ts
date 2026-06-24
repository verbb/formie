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
export declare const LEGACY_FORMIE_DOM_EVENT_BRIDGES: LegacyDomEventBridge[];
export declare const LEGACY_FORMIE_VALIDATOR_EVENT_BRIDGES: LegacyValidatorEventBridge[];
export declare function resolveLegacyCompatibilityOptions(options: LegacyCompatibilityOptions | undefined): ResolvedLegacyCompatibilityOptions;
//# sourceMappingURL=event-map.d.ts.map