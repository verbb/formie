type SagePayTokeniseResult = {
    success: boolean;
    cardIdentifier?: string;
    errors?: Array<{
        message: string;
    }>;
};
type SagePayGlobal = {
    (opts: {
        merchantSessionKey: string;
    }): {
        tokeniseCardDetails: (opts: {
            cardDetails: Record<string, string>;
            onTokenised: (result: SagePayTokeniseResult) => void;
        }) => void;
    };
};
declare global {
    interface Window {
        sagepayOwnForm?: SagePayGlobal;
    }
}
export declare const opayoModule: import("../../..").FormieModuleDefinition;
export {};
//# sourceMappingURL=opayo.d.ts.map