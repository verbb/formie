export type PaymentProviderOptions = {
    handle?: string;
    requiredInputSuffixes?: string[];
    waitForValueMs?: number;
    errorMessage?: string;
};
export declare function getPaymentProviderHandle(id: string, options: PaymentProviderOptions): string;
export declare function findPaymentInputBySuffix(root: Element, suffix: string): HTMLInputElement | null;
export declare function hasRequiredPaymentInputs(root: Element, requiredInputSuffixes: string[]): {
    ok: boolean;
    missingSuffix?: string;
};
export declare function waitForRequiredPaymentInputs(root: Element, requiredInputSuffixes: string[], waitForValueMs: number): Promise<{
    ok: boolean;
    missingSuffix?: string;
}>;
//# sourceMappingURL=utils.d.ts.map