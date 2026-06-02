import { sleep } from '#utils/async';

export type PaymentProviderOptions = {
    handle?: string;
    requiredInputSuffixes?: string[];
    waitForValueMs?: number;
    errorMessage?: string;
};

export function getPaymentProviderHandle(id: string, options: PaymentProviderOptions): string {
    if (typeof options.handle === 'string' && options.handle.trim() !== '') {
        return options.handle.trim();
    }

    return id;
}

export function findPaymentInputBySuffix(root: Element, suffix: string): HTMLInputElement | null {
    const escapedSuffix = suffix.replace(/"/g, '\\"');
    return (root.querySelector(`input[name$="[${escapedSuffix}]"]`) ||
        root.querySelector(`input[name$="${escapedSuffix}"]`)) as HTMLInputElement | null;
}

export function hasRequiredPaymentInputs(root: Element, requiredInputSuffixes: string[]): { ok: boolean; missingSuffix?: string } {
    const missingSuffix = requiredInputSuffixes.find((suffix) => {
        const input = findPaymentInputBySuffix(root, suffix);
        return !input || String(input.value || '').trim() === '';
    });

    return {
        ok: !missingSuffix,
        missingSuffix,
    };
}

export async function waitForRequiredPaymentInputs(
    root: Element,
    requiredInputSuffixes: string[],
    waitForValueMs: number,
): Promise<{ ok: boolean; missingSuffix?: string }> {
    const initial = hasRequiredPaymentInputs(root, requiredInputSuffixes);

    if (initial.ok) {
        return initial;
    }

    const deadline = Date.now() + Math.max(waitForValueMs, 0);

    while (Date.now() < deadline) {
        await sleep(120);

        const current = hasRequiredPaymentInputs(root, requiredInputSuffixes);

        if (current.ok) {
            return current;
        }
    }

    return hasRequiredPaymentInputs(root, requiredInputSuffixes);
}
