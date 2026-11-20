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

/**
 * Payment widgets should neither mount nor enforce authorize tokens when the
 * field (or an ancestor row/page) is hidden by conditions / page flow.
 * Matches server-side `isConditionallyHidden()` skips in payment workflow tasks.
 */
export function isPaymentFieldActive(element: Element): boolean {
    const node = element as HTMLElement;

    return !node.closest('[data-formie-conditionally-hidden]')
        && !node.closest('[data-formie-row-hidden]')
        && !node.closest('[data-formie-page-hidden]')
        && !node.closest('[hidden]');
}

export function findPaymentInputBySuffix(root: Element, suffix: string): HTMLInputElement | null {
    const escapedSuffix = suffix.replace(/"/g, '\\"');
    return (root.querySelector(`input[name$="[${escapedSuffix}]"]`) ||
        root.querySelector(`input[name$="${escapedSuffix}"]`)) as HTMLInputElement | null;
}

/**
 * A form can hold several Payment fields with the same inputs, such as two Stripe fields switched by conditions.
 * Use the field's own input, and only search the rest of the form when the field doesn't render one.
 */
export function findFieldPaymentInput(field: Element | null | undefined, root: Element, suffix: string): HTMLInputElement | null {
    return (field ? findPaymentInputBySuffix(field, suffix) : null) ?? findPaymentInputBySuffix(root, suffix);
}

export function hasRequiredPaymentInputs(root: Element, requiredInputSuffixes: string[], field?: Element | null): { ok: boolean; missingSuffix?: string } {
    const missingSuffix = requiredInputSuffixes.find((suffix) => {
        const input = findFieldPaymentInput(field, root, suffix);
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
    field?: Element | null,
): Promise<{ ok: boolean; missingSuffix?: string }> {
    const initial = hasRequiredPaymentInputs(root, requiredInputSuffixes, field);

    if (initial.ok) {
        return initial;
    }

    const deadline = Date.now() + Math.max(waitForValueMs, 0);

    while (Date.now() < deadline) {
        await sleep(120);

        const current = hasRequiredPaymentInputs(root, requiredInputSuffixes, field);

        if (current.ok) {
            return current;
        }
    }

    return hasRequiredPaymentInputs(root, requiredInputSuffixes, field);
}
