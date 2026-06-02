import type { FormieModuleDefinition } from '#contracts/modules';

type PaymentProviderConfig = {
    handle: string;
    requiredInputSuffixes: string[];
    waitForValueMs: number;
    errorMessage?: string;
};

const DEFAULT_WAIT_FOR_VALUE_MS = 2500;

const DEFAULT_PROVIDER_SUFFIXES: Record<string, string[]> = {
    stripe: ['stripePaymentIntentId'],
    paypal: ['paypalOrderId'],
    payway: ['paywayTokenId'],
    opayo: ['opayoTokenId'],
};

function normalizeProviders(rawProviders: unknown, fallbackSuffixes: string[]): PaymentProviderConfig[] {
    if (!Array.isArray(rawProviders) || rawProviders.length === 0) {
        return [{
            handle: 'generic',
            requiredInputSuffixes: fallbackSuffixes,
            waitForValueMs: DEFAULT_WAIT_FOR_VALUE_MS,
        }];
    }

    return rawProviders.map((item) => {
        if (typeof item === 'string') {
            const normalizedHandle = item.trim();
            return {
                handle: normalizedHandle || 'unknown',
                requiredInputSuffixes: DEFAULT_PROVIDER_SUFFIXES[normalizedHandle] || fallbackSuffixes,
                waitForValueMs: DEFAULT_WAIT_FOR_VALUE_MS,
            };
        }

        const raw = (item && typeof item === 'object' ? item : {}) as Record<string, unknown>;
        const handle = String(raw.handle || '').trim();

        return {
            handle: handle || 'unknown',
            requiredInputSuffixes: Array.isArray(raw.requiredInputSuffixes)
                ? raw.requiredInputSuffixes.map(String)
                : (DEFAULT_PROVIDER_SUFFIXES[handle] || fallbackSuffixes),
            waitForValueMs: Number(raw.waitForValueMs || DEFAULT_WAIT_FOR_VALUE_MS),
            errorMessage: typeof raw.errorMessage === 'string' ? raw.errorMessage : undefined,
        };
    }).filter((provider) => {
        return provider.requiredInputSuffixes.length > 0;
    });
}

function findInputBySuffix(root: Element, suffix: string): HTMLInputElement | null {
    const escapedSuffix = suffix.replace(/"/g, '\\"');
    return (root.querySelector(`input[name$="[${escapedSuffix}]"]`) ||
        root.querySelector(`input[name$="${escapedSuffix}"]`)) as HTMLInputElement | null;
}

function hasAllRequiredInputs(root: Element, requiredInputSuffixes: string[]): { ok: boolean; missingSuffix?: string } {
    const missingSuffix = requiredInputSuffixes.find((suffix) => {
        const input = findInputBySuffix(root, suffix);
        return !input || String(input.value || '').trim() === '';
    });

    return {
        ok: !missingSuffix,
        missingSuffix,
    };
}

async function waitForRequiredInputs(root: Element, requiredInputSuffixes: string[], waitForValueMs: number): Promise<{ ok: boolean; missingSuffix?: string }> {
    const initial = hasAllRequiredInputs(root, requiredInputSuffixes);
    if (initial.ok) {
        return initial;
    }

    const deadline = Date.now() + Math.max(waitForValueMs, 0);

    // Payment SDKs often populate hidden tokens asynchronously after the user
    // clicks submit, so authorize waits briefly for those inputs to settle.
    while (Date.now() < deadline) {
        await new Promise((resolve) => {
            window.setTimeout(resolve, 120);
        });

        const current = hasAllRequiredInputs(root, requiredInputSuffixes);
        if (current.ok) {
            return current;
        }
    }

    return hasAllRequiredInputs(root, requiredInputSuffixes);
}

export const paymentModule: FormieModuleDefinition = {
    id: 'payment',
    kind: 'payment',
    match: () => true,
    setup: async(ctx) => {
        const targetRoot = ctx.scope === 'field' ? ctx.target : ctx.root;
        const requiredInputSuffixes = Array.isArray(ctx.options?.requiredInputSuffixes)
            ? (ctx.options?.requiredInputSuffixes as string[])
            : [];
        const providers = normalizeProviders(ctx.options?.providers, requiredInputSuffixes);

        const hasPaymentField = !!targetRoot.querySelector('[data-formie-field-type="payment"]');

        return {
            destroy: () => {
                void ctx.emit('formie:module:payment:destroy', {});
            },
            onBeforeStage: async(stageCtx) => {
                if (stageCtx.stage !== 'authorize') {
                    return;
                }

                if (stageCtx.action !== 'submit') {
                    return;
                }

                if (!hasPaymentField) {
                    return;
                }

                await ctx.emit('formie:payment:authorize:before', {
                    action: stageCtx.action,
                });

                for (const provider of providers) {
                    await ctx.emit('formie:payment:provider:authorize:before', {
                        provider,
                        action: stageCtx.action,
                    });

                    const result = await waitForRequiredInputs(
                        targetRoot,
                        provider.requiredInputSuffixes,
                        provider.waitForValueMs
                    );

                    if (!result.ok) {
                        // Abort before dispatch so the form never posts without the
                        // provider-specific token the backend expects to finalize payment.
                        const message = provider.errorMessage || 'Payment authorization is incomplete.';
                        stageCtx.abort(message);

                        await ctx.emit('formie:payment:provider:authorize:error', {
                            reason: 'missing-payment-token',
                            provider,
                            action: stageCtx.action,
                            missingSuffix: result.missingSuffix,
                        });

                        await ctx.emit('formie:payment:authorize:error', {
                            reason: 'missing-payment-token',
                            providerHandle: provider.handle,
                            missingSuffix: result.missingSuffix,
                            action: stageCtx.action,
                        });

                        return;
                    }

                    await ctx.emit('formie:payment:provider:authorize:after', {
                        provider,
                        action: stageCtx.action,
                    });
                }
            },
            onAfterStage: async(stageCtx, result) => {
                if (stageCtx.stage !== 'authorize') {
                    return;
                }

                await ctx.emit('formie:payment:authorize:after', {
                    action: stageCtx.action,
                    result,
                });
            },
        };
    },
};
