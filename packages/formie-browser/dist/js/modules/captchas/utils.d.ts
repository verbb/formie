export declare function getScriptAttributes(loadingMethod?: string): {
    async: boolean;
    defer: boolean;
};
export declare function getInputValue(root: ParentNode, name: string): string;
export declare function hasCaptchaValue(root: ParentNode, names: string[]): boolean;
export declare function clearCaptchaValues(root: ParentNode, names: string[]): void;
export declare function ensureCaptchaValueInput(root: ParentNode, name: string, { value, container, }?: {
    value?: string;
    container?: HTMLElement | null;
}): HTMLInputElement;
export declare function waitForCaptchaValue(root: ParentNode, names: string[], waitForValueMs: number): Promise<boolean>;
//# sourceMappingURL=utils.d.ts.map