import type { FormieModuleDefinition } from '#contracts/modules';
import {
    getPaymentProviderHandle,
    waitForRequiredPaymentInputs,
    type PaymentProviderOptions,
} from '#modules/payments/utils';

type StubPaymentModuleConfig = {
    id: string;
};

const DEFAULT_WAIT_FOR_VALUE_MS = 2500;

export function createStubPaymentModule({ id }: StubPaymentModuleConfig): FormieModuleDefinition {
    return {
        id,
        kind: 'payment',
        match: () => true,
        setup: async(ctx) => {
            const options = (ctx.options || {}) as PaymentProviderOptions;
            const targetRoot = ctx.scope === 'field' ? ctx.target : ctx.root;
            const requiredInputSuffixes = Array.isArray(options.requiredInputSuffixes)
                ? options.requiredInputSuffixes.map(String)
                : [];
            const waitForValueMs = Number(options.waitForValueMs || DEFAULT_WAIT_FOR_VALUE_MS);
            const providerHandle = getPaymentProviderHandle(id, options);

            await ctx.emit(`formie:module:${id}:init`, {
                options,
            });

            return {
                destroy: () => {
                    void ctx.emit(`formie:module:${id}:destroy`, {});
                },
                onBeforeStage: async(stageCtx) => {
                    if (stageCtx.stage !== 'authorize' || stageCtx.action !== 'submit') {
                        return;
                    }

                    if (requiredInputSuffixes.length === 0) {
                        return;
                    }

                    await ctx.emit('formie:payment:provider:authorize:before', {
                        provider: {
                            handle: providerHandle,
                            ...options,
                        },
                        action: stageCtx.action,
                    });

                    const result = await waitForRequiredPaymentInputs(
                        targetRoot,
                        requiredInputSuffixes,
                        waitForValueMs
                    );

                    if (!result.ok) {
                        const message = options.errorMessage || 'Payment authorization is incomplete.';
                        stageCtx.abort(message);

                        await ctx.emit('formie:payment:provider:authorize:error', {
                            reason: 'missing-payment-token',
                            provider: {
                                handle: providerHandle,
                                ...options,
                            },
                            action: stageCtx.action,
                            missingSuffix: result.missingSuffix,
                        });

                        return;
                    }

                    await ctx.emit('formie:payment:provider:authorize:after', {
                        provider: {
                            handle: providerHandle,
                            ...options,
                        },
                        action: stageCtx.action,
                    });
                },
            };
        },
    };
}
