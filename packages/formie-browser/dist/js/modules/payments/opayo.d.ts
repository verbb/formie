type SagePayTokeniseResult = {
    success: boolean;
    cardIdentifier?: string;
    errors?: Array<{
        message: string;
    }>;
};
type SagePayOwnFormGlobal = {
    (opts: {
        merchantSessionKey: string;
    }): {
        tokeniseCardDetails: (opts: {
            cardDetails: Record<string, string>;
            onTokenised: (result: SagePayTokeniseResult) => void;
        }) => void;
    };
};
type SagePayCheckoutInstance = {
    tokenise: (opts?: {
        newMerchantSessionKey?: string;
    }) => void;
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
export declare const opayoModule: import("../../..").FormieModuleDefinition;
export {};
//# sourceMappingURL=opayo.d.ts.map