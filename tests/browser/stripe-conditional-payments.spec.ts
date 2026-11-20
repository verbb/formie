import { test, expect } from '@playwright/test';
import { build } from 'esbuild';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import type { createManagedPaymentModule } from '../../packages/formie-browser/src/js/modules/payments/factories';
import type { stripeModule } from '../../packages/formie-browser/src/js/modules/payments/stripe';
import type { BeforeSubmitContext, BrowserModuleDefinition, BrowserModuleInstance, ModuleSetupContext } from '../../packages/formie-browser/src/js/contracts/modules';

// Two Stripe Payment fields on one page, switched by a condition (#3153).
declare global {
    interface Window {
        stripeConditional: { stripeModule: typeof stripeModule; createManagedPaymentModule: typeof createManagedPaymentModule };
        stripeTest: {
            setup: (handle: string, module?: BrowserModuleDefinition, options?: Record<string, unknown>) => Promise<BrowserModuleInstance>;
            setVisible: (handle: string, visible: boolean) => void;
            submitted: Record<string, string>[];
            confirmedAmounts: unknown[];
        };
    }
}

let script: string;

test.beforeAll(async () => {
    const imports = JSON.parse(readFileSync('packages/formie-browser/package.json', 'utf8')).imports;
    const result = await build({
        stdin: {
            contents: `export { stripeModule } from './packages/formie-browser/src/js/modules/payments/stripe';
                export { createManagedPaymentModule } from './packages/formie-browser/src/js/modules/payments/factories';`,
            resolveDir: process.cwd(),
        },
        bundle: true,
        format: 'iife',
        globalName: 'stripeConditional',
        write: false,
        loader: { '.css': 'text' },
        alias: Object.fromEntries(Object.entries(imports).map(([key, value]) => [
            key.replace('/*', ''),
            path.resolve('packages/formie-browser', (typeof value === 'string' ? value : (value as { default: string }).default).replace('/*', '')),
        ])),
    });
    script = result.outputFiles[0].text;
});

test.beforeEach(async ({ page }) => {
    const field = (handle: string) => `<div data-formie-field data-formie-field-handle="${handle}" data-formie-field-type="payment">
        <div data-formie-field-control>
            <div data-formie-stripe-elements-placeholder></div>
            <div data-formie-stripe-elements></div>
            <input type="hidden" name="fields[${handle}][stripePaymentIntentId]">
            <input type="hidden" name="fields[${handle}][stripeSubscriptionId]">
        </div>
    </div>`;

    await page.setContent(`<form action="https://payments.example.test/submit">${field('paymentPreset')}${field('paymentCustom')}</form>`);

    // Stripe.js stand-in. Like the real SDK, a Payment Element throws when destroyed twice.
    await page.addScriptTag({ content: `
        window.Stripe = function () {
            return {
                elements: function (options) {
                    return {
                        options: options,
                        create: function () {
                            var destroyed = false;
                            return {
                                mount: function () {},
                                destroy: function () {
                                    if (destroyed) throw new Error('This Element has already been destroyed. Please create a new one.');
                                    destroyed = true;
                                },
                            };
                        },
                        update: function () {},
                        submit: function () { return Promise.resolve({}); },
                    };
                },
                confirmPayment: function (opts) {
                    window.stripeTest.confirmedAmounts.push(opts.elements.options.amount);
                    return Promise.resolve({ paymentIntent: { id: 'pi_3153' } });
                },
                confirmSetup: function () { return Promise.resolve({ setupIntent: { id: 'seti_3153' } }); },
            };
        };
    ` });
    await page.addScriptTag({ content: script });
    await page.evaluate(() => {
        const form = document.querySelector('form')!;
        const listeners = new Set<{ name: string; callback: (payload: unknown) => void }>();
        const amounts: Record<string, number> = { paymentPreset: 10, paymentCustom: 15 };
        const fieldFor = (handle: string) => form.querySelector<HTMLElement>(`[data-formie-field-handle="${handle}"]`)!;

        window.stripeTest = {
            submitted: [],
            confirmedAmounts: [],
            // Formie hides a field by condition with this attribute and disables its inputs.
            setVisible: (handle, visible) => {
                const target = fieldFor(handle);
                target.toggleAttribute('data-formie-conditionally-hidden', !visible);
                target.querySelectorAll('input').forEach((input) => {
                    input.disabled = !visible;
                });
                listeners.forEach((listener) => {
                    if (listener.name === 'formie:conditions:evaluated') listener.callback({});
                });
            },
            setup: async (handle, module, extraOptions) => {
                const context: ModuleSetupContext = {
                    formId: 'issue-3153',
                    form,
                    root: form,
                    target: fieldFor(handle),
                    scope: 'field',
                    state: {},
                    surface: 'server-rendered',
                    entryKey: handle,
                    options: {
                        handle: 'stripe',
                        publishableKey: 'pk_test_3153',
                        paymentType: 'single',
                        amountType: 'fixed',
                        currencyType: 'fixed',
                        initialPaymentInformation: { amount: amounts[handle], currency: 'aud' },
                        ...extraOptions,
                    },
                    on: (name, callback) => {
                        const listener = { name, callback };
                        listeners.add(listener);

                        return () => {
                            listeners.delete(listener);
                        };
                    },
                    emit: async () => {},
                } as ModuleSetupContext;

                return (await (module ?? window.stripeConditional.stripeModule).setup(context))!;
            },
        };

        // Capture the internal resubmit instead of navigating.
        form.requestSubmit = () => {
            window.stripeTest.submitted.push(Object.fromEntries(new FormData(form)) as Record<string, string>);
        };
    });
});

test('only the visible Payment field confirms, and its own input carries the intent', async ({ page }) => {
    const result = await page.evaluate(async () => {
        const state = window.stripeTest;

        // Payment A mounts while visible, then the visitor switches to Payment B.
        state.setVisible('paymentCustom', false);
        const first = await state.setup('paymentPreset');
        const second = await state.setup('paymentCustom');
        state.setVisible('paymentPreset', false);
        state.setVisible('paymentCustom', true);
        await new Promise((resolve) => setTimeout(resolve, 0));

        document.querySelector('form')!.dispatchEvent(new CustomEvent('formie:payment:stripe:confirm', {
            bubbles: true,
            detail: { data: { clientSecret: 'pi_3153_secret_custom' } },
        }));
        await new Promise((resolve) => setTimeout(resolve, 50));

        await first.destroy();
        await second.destroy();

        return { confirmedAmounts: state.confirmedAmounts, submitted: state.submitted };
    });

    expect(result.confirmedAmounts).toEqual([15]);
    expect(result.submitted).toHaveLength(1);
    expect(result.submitted[0]['fields[paymentCustom][stripePaymentIntentId]']).toBe('pi_3153');
    expect(result.submitted[0]).not.toHaveProperty('fields[paymentPreset][stripePaymentIntentId]');
});

test('the first Payment field still confirms when it is the visible one', async ({ page }) => {
    const result = await page.evaluate(async () => {
        const state = window.stripeTest;

        state.setVisible('paymentCustom', false);
        const first = await state.setup('paymentPreset');
        const second = await state.setup('paymentCustom');

        document.querySelector('form')!.dispatchEvent(new CustomEvent('formie:payment:stripe:confirm', {
            bubbles: true,
            detail: { data: { clientSecret: 'pi_3153_secret_preset' } },
        }));
        await new Promise((resolve) => setTimeout(resolve, 50));

        await first.destroy();
        await second.destroy();

        return { confirmedAmounts: state.confirmedAmounts, submitted: state.submitted, errors: document.querySelectorAll('[data-payment-error]').length };
    });

    expect(result.confirmedAmounts).toEqual([10]);
    expect(result.submitted).toHaveLength(1);
    expect(result.submitted[0]['fields[paymentPreset][stripePaymentIntentId]']).toBe('pi_3153');
    expect(result.errors).toBe(0);
});

test('tearing down after a completed payment does not destroy the Payment Element twice', async ({ page }) => {
    const result = await page.evaluate(async () => {
        const state = window.stripeTest;
        const instance = await state.setup('paymentPreset');

        await instance.afterSubmit!({} as never, { ok: true } as never);

        try {
            await instance.destroy();

            return 'destroyed';
        } catch (error) {
            return (error as Error).message;
        }
    });

    expect(result).toBe('destroyed');
});

test('a token provider writes to and checks its own Payment field', async ({ page }) => {
    const result = await page.evaluate(async () => {
        const state = window.stripeTest;
        const form = document.querySelector('form')!;
        // A provider without onBeforePayment, like PayPal, whose token the factory waits for before submitting.
        const module = window.stripeConditional.createManagedPaymentModule({
            moduleId: 'test:token',
            load: async () => ({}),
            mount: ({ services }) => {
                services.updateInputs('stripePaymentIntentId', 'tok_3153');

                return {};
            },
        });

        state.setVisible('paymentPreset', false);
        const instance = await state.setup('paymentCustom', module, { requiredInputSuffixes: ['stripePaymentIntentId'], waitForValueMs: 200 });
        let aborted = false;
        await instance.beforeSubmit!({
            form,
            action: 'submit',
            formData: new FormData(form),
            abort: () => {
                aborted = true;
            },
            isAborted: () => aborted,
            abortReason: () => undefined,
        } as BeforeSubmitContext);
        await instance.destroy();

        return { aborted, posted: Object.fromEntries(new FormData(form)) };
    });

    expect(result.aborted).toBe(false);
    expect(result.posted['fields[paymentCustom][stripePaymentIntentId]']).toBe('tok_3153');
});
