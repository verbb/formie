type FormieDebugGlobal = {
    __FORMIE_DEBUG__?: boolean;
};

export type FormieDebugCategory = 'general' | 'fields' | 'conditions' | 'address' | 'captchas' | 'payments';

function getDebugGlobal(): FormieDebugGlobal {
    return globalThis as FormieDebugGlobal;
}

export function isFormieDebugEnabled(): boolean {
    return getDebugGlobal().__FORMIE_DEBUG__ === true;
}

export function setFormieDebugEnabled(enabled: boolean): void {
    getDebugGlobal().__FORMIE_DEBUG__ = enabled;
}

export function debugLog(scope: string, message: string, meta?: unknown): void {
    if (!isFormieDebugEnabled()) {
        return;
    }

    if (typeof meta === 'undefined') {
        console.log(`[formie:${scope}] ${message}`);
        return;
    }

    console.log(`[formie:${scope}] ${message}`, meta);
}

export function debugWarn(scope: string, message: string, meta?: unknown): void {
    if (!isFormieDebugEnabled()) {
        return;
    }

    if (typeof meta === 'undefined') {
        console.warn(`[formie:${scope}] ${message}`);
        return;
    }

    console.warn(`[formie:${scope}] ${message}`, meta);
}

export function createDebug(category: FormieDebugCategory, module?: string): {
    log: (message: string, meta?: unknown) => void;
    warn: (message: string, meta?: unknown) => void;
} {
    const scope = module ? `${category}:${module}` : category;

    return {
        log: (message, meta) => {
            debugLog(scope, message, meta);
        },
        warn: (message, meta) => {
            debugWarn(scope, message, meta);
        },
    };
}
