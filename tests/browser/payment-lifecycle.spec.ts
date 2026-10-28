import { test, expect } from '@playwright/test';
import { build } from 'esbuild';
import { readFileSync, readdirSync } from 'node:fs';
import path from 'node:path';
import type { createManagedPaymentModule } from '../../packages/formie-browser/src/js/modules/payments/factories';
import type { opayoModule } from '../../packages/formie-browser/src/js/modules/payments/opayo';
import type {
    BeforeSubmitContext,
    BrowserModuleInstance,
    ModuleSetupContext,
} from '../../packages/formie-browser/src/js/contracts/modules';

declare global {
    interface Window {
        paymentLifecycle: {
            createManagedPaymentModule: typeof createManagedPaymentModule;
            opayoModule: typeof opayoModule;
        };
        paymentTest: {
            context: ModuleSetupContext;
            submit: BeforeSubmitContext;
            emit: (name: string) => void;
            reveal: () => void;
            instance?: BrowserModuleInstance;
            release?: () => void;
            mounts: number;
            unmounts: number;
            authorized: number;
        };
    }
}

let script: string;
test.beforeAll(async () => {
    const imports = JSON.parse(
        readFileSync('packages/formie-browser/package.json', 'utf8'),
    ).imports;
    const result = await build({
        stdin: {
            contents: `export { createManagedPaymentModule } from './packages/formie-browser/src/js/modules/payments/factories';
                export { opayoModule } from './packages/formie-browser/src/js/modules/payments/opayo';`,
            resolveDir: process.cwd(),
        },
        bundle: true,
        format: 'iife',
        globalName: 'paymentLifecycle',
        write: false,
        loader: { '.css': 'text' },
        alias: Object.fromEntries(
            Object.entries(imports).map(([key, value]) => [
                key.replace('/*', ''),
                path.resolve(
                    'packages/formie-browser',
                    (typeof value === 'string'
                        ? value
                        : (value as { default: string }).default
                    ).replace('/*', ''),
                ),
            ]),
        ),
    });
    script = result.outputFiles[0].text;
});

test.beforeEach(async ({ page }) => {
    await page.setContent(`<form action="https://payments.example.test/submit">
        <label>Amount<input name="amount" type="number"></label>
        <div data-formie-field-type="payment" hidden><div data-formie-opayo-drop-in></div><div data-payment-error></div></div>
    </form>`);
    await page.addScriptTag({ content: script });
    await page.evaluate(() => {
        const form = document.querySelector('form')!;
        const target = form.querySelector<HTMLElement>(
            '[data-formie-field-type="payment"]',
        )!;
        const listeners = new Map<string, Set<(payload: unknown) => void>>();
        const emit = (name: string) =>
            listeners.get(name)?.forEach((callback) => callback({}));
        const context: ModuleSetupContext = {
            formId: 'payment-test',
            form,
            root: form,
            target,
            scope: 'field',
            state: {},
            surface: 'server-rendered',
            on: (name, callback) => {
                if (!listeners.has(name)) listeners.set(name, new Set());
                listeners.get(name)!.add(callback);
                return () => {
                    listeners.get(name)!.delete(callback);
                };
            },
            emit: async (name) => {
                emit(name);
            },
        };
        window.paymentTest = {
            context,
            emit,
            reveal: () => {
                target.hidden = false;
                emit('formie:conditions:evaluated');
            },
            submit: {
                form,
                action: 'submit',
                formData: new FormData(form),
                abort: () => {},
                isAborted: () => false,
                abortReason: () => undefined,
            },
            mounts: 0,
            unmounts: 0,
            authorized: 0,
        };
        form.querySelector('input')!.addEventListener('input', (event) => {
            target.hidden =
                Number((event.target as HTMLInputElement).value) < 50;
            emit('formie:conditions:evaluated');
        });
    });
});

test('visibility events and submit share an asynchronous payment mount', async ({
    page,
}) => {
    const result = await page.evaluate(async () => {
        const state = window.paymentTest;
        const widget = {};
        const pending = new Promise<object>((resolve) => {
            state.release = () => resolve(widget);
        });
        const module = window.paymentLifecycle.createManagedPaymentModule({
            moduleId: 'test:payment',
            load: async () => ({}),
            mount: () => {
                state.mounts++;
                return pending;
            },
            onBeforePayment: ({ widget: mounted }) => {
                if (mounted === widget) state.authorized++;
                return true;
            },
        });
        const instance = (await module.setup(state.context))!;
        state.reveal();
        state.emit('formie:conditions:evaluated');
        state.context.root.dispatchEvent(
            new Event('formie:page:navigate:after'),
        );
        state.context.root.dispatchEvent(new Event('formie:submit:result'));
        const submit = instance.beforeSubmit!(state.submit);
        await new Promise((resolve) => setTimeout(resolve, 0));
        const before = { mounts: state.mounts, authorized: state.authorized };
        state.release!();
        await submit;
        state.emit('formie:conditions:evaluated');
        await instance.destroy();
        return { before, mounts: state.mounts, authorized: state.authorized };
    });
    expect(result).toEqual({
        before: { mounts: 1, authorized: 0 },
        mounts: 1,
        authorized: 1,
    });
});

test('a failed visibility mount is handled and can retry before submission', async ({
    page,
}) => {
    const errors: string[] = [];
    page.on('pageerror', (error) => errors.push(error.message));
    const result = await page.evaluate(async () => {
        const state = window.paymentTest;
        const module = window.paymentLifecycle.createManagedPaymentModule({
            moduleId: 'test:payment',
            load: async () => ({}),
            mount: async () => {
                if (++state.mounts === 1)
                    throw new Error('Provider unavailable');
                return {};
            },
            onBeforePayment: () => {
                state.authorized++;
                return true;
            },
        });
        const instance = (await module.setup(state.context))!;
        state.reveal();
        state.emit('formie:conditions:evaluated');
        await new Promise((resolve) => setTimeout(resolve, 0));
        const feedback = state.context.target.textContent;
        await instance.beforeSubmit!(state.submit);
        await instance.destroy();
        return { feedback, mounts: state.mounts, authorized: state.authorized };
    });
    expect(errors).toEqual([]);
    expect(result.feedback).toContain('Payment authorization is incomplete.');
    expect(result.mounts).toBe(2);
    expect(result.authorized).toBe(1);
});

test('destroy waits for an in-flight mount and releases the widget exactly once', async ({
    page,
}) => {
    const result = await page.evaluate(async () => {
        const state = window.paymentTest;
        const pending = new Promise<object>((resolve) => {
            state.release = () => resolve({});
        });
        const module = window.paymentLifecycle.createManagedPaymentModule({
            moduleId: 'test:payment',
            load: async () => ({}),
            mount: () => {
                state.mounts++;
                return pending;
            },
            unmount: () => {
                state.unmounts++;
            },
        });
        const instance = (await module.setup(state.context))!;
        state.reveal();
        await new Promise((resolve) => setTimeout(resolve, 0));
        const destroy = instance.destroy();
        const repeatedDestroy = instance.destroy();
        state.emit('formie:conditions:evaluated');
        state.release!();
        await Promise.all([destroy, repeatedDestroy]);
        await new Promise((resolve) => setTimeout(resolve, 0));
        return { mounts: state.mounts, unmounts: state.unmounts };
    });
    expect(result).toEqual({ mounts: 1, unmounts: 1 });
});

for (const change of ['destroy', 'hide']) {
    test(`does not mount after ${change} while the provider API is loading`, async ({
        page,
    }) => {
        const mounts = await page.evaluate(async (change) => {
            const state = window.paymentTest;
            const api = new Promise<object>((resolve) => {
                state.release = () => resolve({});
            });
            const module = window.paymentLifecycle.createManagedPaymentModule({
                moduleId: 'test:payment',
                load: () => api,
                mount: () => {
                    state.mounts++;
                    return {};
                },
            });
            const instance = (await module.setup(state.context))!;
            state.reveal();
            const destroy =
                change === 'destroy' ? instance.destroy() : undefined;
            if (change === 'hide')
                (state.context.target as HTMLElement).hidden = true;
            state.release!();
            await destroy;
            await new Promise((resolve) => setTimeout(resolve, 0));
            await instance.destroy();
            return state.mounts;
        }, change);
        expect(mounts).toBe(0);
    });
}

test('initial and submit-time failures still reject their awaited caller', async ({
    page,
}) => {
    const result = await page.evaluate(async () => {
        const state = window.paymentTest;
        const module = window.paymentLifecycle.createManagedPaymentModule({
            moduleId: 'test:payment',
            load: async () => ({}),
            mount: async () => {
                throw new Error('Provider unavailable');
            },
        });
        const instance = (await module.setup(state.context))!;
        (state.context.target as HTMLElement).hidden = false;
        const submit = await Promise.resolve(
            instance.beforeSubmit!(state.submit),
        ).then(
            () => 'accepted',
            (error) => error.message,
        );
        await instance.destroy();
        const setup = await module.setup(state.context).then(
            () => 'accepted',
            (error) => error.message,
        );
        return { submit, setup };
    });
    expect(result).toEqual({
        submit: 'Provider unavailable',
        setup: 'Provider unavailable',
    });
});

for (const buildType of ['source', 'browser package', 'plugin assets']) {
    test(`${buildType} Opayo requests one session and creates one checkout when typing reveals the field`, async ({
        page,
    }) => {
        const errors: string[] = [];
        page.on('pageerror', (error) => errors.push(error.message));
        let sessions = 0;
        let releaseSession!: () => void;
        const pending = new Promise<void>((resolve) => {
            releaseSession = resolve;
        });
        await page.route(
            'https://payments.example.test/session',
            async (route) => {
                sessions++;
                await pending;
                await route.fulfill({
                    contentType: 'application/json',
                    body: JSON.stringify({ merchantSessionKey: 'session-key' }),
                });
            },
        );
        if (buildType !== 'source') {
            const dist =
                buildType === 'browser package'
                    ? 'packages/formie-browser/dist'
                    : 'src/web/assets/frontend/dist/js';
            await page.route(
                'https://payments.example.test/browser/**',
                (route) =>
                    route.fulfill({
                        path: path.resolve(
                            dist,
                            new URL(route.request().url()).pathname.slice(
                                '/browser/'.length,
                            ),
                        ),
                        contentType: 'application/javascript',
                        headers: { 'Access-Control-Allow-Origin': '*' },
                    }),
            );
            const chunk = readdirSync(path.join(dist, 'chunks')).find((file) =>
                /^opayo-.*\.js$/.test(file),
            )!;
            await page.evaluate(async (chunk) => {
                window.paymentLifecycle.opayoModule = (
                    await import(
                        `https://payments.example.test/browser/chunks/${chunk}`
                    )
                ).opayoModule;
            }, chunk);
        }
        await page.evaluate(async () => {
            const state = window.paymentTest;
            window.sagepayCheckout = () => {
                if (++state.mounts > 1)
                    throw new Error('Parent page already initialised');
                return {
                    tokenise() {},
                    destroy() {
                        state.unmounts++;
                    },
                };
            };
            state.context.options = {
                handle: 'payment',
                checkoutMode: 'dropIn',
                useSandbox: true,
                sessionToken: 'scoped-token',
                sessionEndpoint: 'https://payments.example.test/session',
            };
            state.instance = (await window.paymentLifecycle.opayoModule.setup(
                state.context,
            ))!;
        });
        await page
            .getByRole('spinbutton', { name: 'Amount' })
            .pressSequentially('50');
        await expect.poll(() => sessions).toBe(1);
        await page
            .getByRole('spinbutton', { name: 'Amount' })
            .pressSequentially('0');
        await page.evaluate(() => {
            window.paymentTest.emit('formie:conditions:evaluated');
            window.paymentTest.context.root.dispatchEvent(
                new Event('formie:page:navigate:after'),
            );
        });
        releaseSession();
        await expect
            .poll(() => page.evaluate(() => window.paymentTest.mounts))
            .toBeGreaterThan(0);
        await page.evaluate(() => window.paymentTest.instance!.destroy());
        expect(sessions).toBe(1);
        expect(
            await page.evaluate(() => ({
                mounts: window.paymentTest.mounts,
                unmounts: window.paymentTest.unmounts,
            })),
        ).toEqual({ mounts: 1, unmounts: 1 });
        expect(errors).toEqual([]);
    });
}

test('a payment retry unmounts the previous widget before mounting its replacement', async ({
    page,
}) => {
    const calls = await page.evaluate(async () => {
        const state = window.paymentTest;
        const calls: string[] = [];
        const module = window.paymentLifecycle.createManagedPaymentModule({
            moduleId: 'test:payment',
            load: async () => ({}),
            mount: () => {
                calls.push('mount');
                return {};
            },
            unmount: async () => {
                calls.push('unmount');
                state.emit('formie:conditions:evaluated');
                await new Promise((resolve) => setTimeout(resolve, 0));
            },
            onAfterSubmit: () => ({ remount: true }),
        });
        (state.context.target as HTMLElement).hidden = false;
        const instance = (await module.setup(state.context))!;
        await instance.afterSubmit!(state.submit, { ok: false });
        await instance.destroy();
        return calls;
    });
    expect(calls).toEqual(['mount', 'unmount', 'mount', 'unmount']);
});

test('mounting one payment occurrence does not block another', async ({
    page,
}) => {
    const result = await page.evaluate(async () => {
        const state = window.paymentTest;
        const pending = new Promise<object>((resolve) => {
            state.release = () => resolve({});
        });
        const module = window.paymentLifecycle.createManagedPaymentModule({
            moduleId: 'test:payment',
            load: async () => ({}),
            mount: () => {
                state.mounts++;
                return pending;
            },
            unmount: () => {
                state.unmounts++;
            },
        });
        const first = (await module.setup({
            ...state.context,
            entryKey: 'first',
        }))!;
        const target = state.context.target.cloneNode(true) as HTMLElement;
        state.context.root.appendChild(target);
        const second = (await module.setup({
            ...state.context,
            target,
            entryKey: 'second',
        }))!;
        target.hidden = false;
        state.reveal();
        state.emit('formie:conditions:evaluated');
        await new Promise((resolve) => setTimeout(resolve, 0));
        state.release!();
        await Promise.all([first.destroy(), second.destroy()]);
        return { mounts: state.mounts, unmounts: state.unmounts };
    });
    expect(result).toEqual({ mounts: 2, unmounts: 2 });
});

test('Opayo Own Form requests its session only during authorization', async ({
    page,
}) => {
    let sessions = 0;
    await page.route('https://payments.example.test/session', async (route) => {
        sessions++;
        await route.fulfill({
            contentType: 'application/json',
            body: JSON.stringify({ merchantSessionKey: 'own-form-session' }),
        });
    });
    await page.evaluate(async () => {
        const state = window.paymentTest;
        window.sagepayOwnForm = () => ({
            tokeniseCardDetails: ({ onTokenised }) => {
                state.authorized++;
                onTokenised({ success: true, cardIdentifier: 'own-form-card' });
            },
        });
        state.context.options = {
            handle: 'payment',
            checkoutMode: 'ownForm',
            useSandbox: true,
            sessionToken: 'scoped-token',
            sessionEndpoint: 'https://payments.example.test/session',
        };
        state.instance = (await window.paymentLifecycle.opayoModule.setup(
            state.context,
        ))!;
        state.reveal();
        state.emit('formie:conditions:evaluated');
        await new Promise((resolve) => setTimeout(resolve, 0));
    });
    expect(sessions).toBe(0);
    await page.evaluate(async () => {
        const state = window.paymentTest;
        await state.instance!.beforeSubmit!(state.submit);
        await state.instance!.destroy();
    });
    expect(sessions).toBe(1);
    expect(await page.evaluate(() => window.paymentTest.authorized)).toBe(1);
});

for (const checkoutMode of ['dropIn', 'ownForm']) {
    for (const response of ['completed', 'failed', 'missing-reference']) {
        test(`Opayo ${checkoutMode} handles a ${response} 3DS callback without redundant authorization`, async ({
            page,
        }) => {
            let sessions = 0;
            await page.route(
                'https://payments.example.test/session',
                (route) => {
                    sessions++;
                    return route.fulfill({
                        json: { merchantSessionKey: `session-${sessions}` },
                    });
                },
            );
            await page.route(
                'https://payments.example.test/challenge',
                (route) =>
                    route.fulfill({
                        contentType: 'text/html',
                        body: `<script>parent.postMessage({message:'formie:payment:opayo:challenge:response',value:${JSON.stringify(response === 'failed' ? { error: { message: 'Challenge failed.' } } : { transactionId: response === 'completed' ? 'verified-transaction' : '' })}},'*');</script>`,
                    }),
            );
            const errors: string[] = [];
            page.on('pageerror', (error) => errors.push(error.message));
            await page.evaluate(async (checkoutMode) => {
                const state = window.paymentTest;
                const { form, target } = state.context;
                (target as HTMLElement).hidden = false;
                target.insertAdjacentHTML(
                    'beforeend',
                    `<input type="hidden" name="fields[payment][opayoTokenId]"><input type="hidden" name="fields[payment][opayoSessionKey]"><input type="hidden" name="fields[payment][opayo3DSComplete]">`,
                );
                window.sagepayCheckout = (options) => ({
                    tokenise: () => {
                        state.authorized++;
                        options.onTokenise!({
                            success: true,
                            cardIdentifier: `card-${state.authorized}`,
                        });
                    },
                    destroy() {},
                });
                window.sagepayOwnForm = () => ({
                    tokeniseCardDetails: ({ onTokenised }) => {
                        state.authorized++;
                        onTokenised({
                            success: true,
                            cardIdentifier: `card-${state.authorized}`,
                        });
                    },
                });
                state.context.options = {
                    handle: 'payment',
                    checkoutMode,
                    useSandbox: true,
                    sessionToken: 'test-authority',
                    sessionEndpoint: 'https://payments.example.test/session',
                };
                const instance =
                    (await window.paymentLifecycle.opayoModule.setup(
                        state.context,
                    ))!;
                state.instance = instance;
                await instance.beforeSubmit!(state.submit);
                await instance.afterSubmit!(state.submit, { ok: false });
                form!.addEventListener('submit', (event) => {
                    event.preventDefault();
                    void Promise.resolve(
                        instance.beforeSubmit!(state.submit),
                    ).then(() => {
                        form!.dataset.authorized = String(state.authorized);
                        form!.dataset.completion =
                            form!.querySelector<HTMLInputElement>(
                                'input[name$="[opayo3DSComplete]"]',
                            )!.value;
                    });
                });
                form!.dispatchEvent(
                    new CustomEvent('formie:payment:opayo:challenge', {
                        detail: {
                            data: {
                                acsUrl: 'https://payments.example.test/challenge',
                                creq: 'test-challenge',
                            },
                        },
                    }),
                );
            }, checkoutMode);
            await expect(page.locator('.formie-modal')).toHaveCount(0);
            if (response === 'failed') {
                await expect(page.locator('[data-payment-error]')).toHaveText(
                    'Challenge failed.',
                );
                expect(
                    await page.locator('form').getAttribute('data-authorized'),
                ).toBeNull();
                expect(
                    await page.evaluate(() => window.paymentTest.authorized),
                ).toBe(1);
            } else {
                await expect(page.locator('form')).toHaveAttribute(
                    'data-authorized',
                    response === 'completed' ? '1' : '2',
                );
                await expect(page.locator('form')).toHaveAttribute(
                    'data-completion',
                    response === 'completed' ? 'verified-transaction' : '',
                );
            }
            expect(sessions).toBe(
                checkoutMode === 'ownForm' && response === 'missing-reference'
                    ? 2
                    : 1,
            );
            // Clearing the result must restore normal authorization for the next attempt.
            const next = await page.evaluate(async () => {
                const state = window.paymentTest;
                await state.instance!.afterSubmit!(state.submit, { ok: false });
                await state.instance!.beforeSubmit!(state.submit);
                await state.instance!.destroy();
                return state.authorized;
            });
            expect(next).toBe(response === 'missing-reference' ? 3 : 2);
            expect(errors).toEqual([]);
        });
    }

    test(`Opayo ${checkoutMode} does not reuse another field's completed challenge`, async ({
        page,
    }) => {
        await page.route('https://payments.example.test/session', (route) =>
            route.fulfill({ json: { merchantSessionKey: 'session' } }),
        );
        const authorized = await page.evaluate(async (checkoutMode) => {
            const state = window.paymentTest;
            const { form, target } = state.context;
            (target as HTMLElement).hidden = false;
            form!.insertAdjacentHTML(
                'beforeend',
                '<div data-formie-field-type="payment"><input type="hidden" name="fields[other][opayo3DSComplete]" value="other-transaction"></div>',
            );
            window.sagepayCheckout = (options) => ({
                tokenise: () => {
                    state.authorized++;
                    options.onTokenise!({
                        success: true,
                        cardIdentifier: 'card',
                    });
                },
                destroy() {},
            });
            window.sagepayOwnForm = () => ({
                tokeniseCardDetails: ({ onTokenised }) => {
                    state.authorized++;
                    onTokenised({ success: true, cardIdentifier: 'card' });
                },
            });
            state.context.options = {
                handle: 'payment',
                checkoutMode,
                useSandbox: true,
                sessionToken: 'test-authority',
                sessionEndpoint: 'https://payments.example.test/session',
            };
            const instance = (await window.paymentLifecycle.opayoModule.setup(
                state.context,
            ))!;
            await instance.beforeSubmit!(state.submit);
            await instance.destroy();
            return state.authorized;
        }, checkoutMode);
        expect(authorized).toBe(1);
    });
}
