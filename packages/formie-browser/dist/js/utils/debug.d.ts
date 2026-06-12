export type FormieDebugCategory = 'general' | 'fields' | 'conditions' | 'address' | 'captchas' | 'payments';
export declare function isFormieDebugEnabled(): boolean;
export declare function setFormieDebugEnabled(enabled: boolean): void;
export declare function debugLog(scope: string, message: string, meta?: unknown): void;
export declare function debugWarn(scope: string, message: string, meta?: unknown): void;
export declare function createDebug(category: FormieDebugCategory, module?: string): {
    log: (message: string, meta?: unknown) => void;
    warn: (message: string, meta?: unknown) => void;
};
//# sourceMappingURL=debug.d.ts.map