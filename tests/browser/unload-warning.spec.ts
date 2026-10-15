import { test, expect } from '@playwright/test';
import { build } from 'esbuild';
import path from 'node:path';

let guardScript: string;

test.beforeAll(async () => {
    // Exercise the browser guard without requiring live CAPTCHA services or Craft fixtures.
    const result = await build({
        stdin: {
            contents: `export { createFormUnloadWarningGuard } from './packages/formie-browser/src/js/utils/unload-warning';
                export { createCaptchaModule, createPassiveCaptchaModule, createManagedCaptchaModule } from './packages/formie-browser/src/js/modules/captchas/factories';`,
            resolveDir: process.cwd(),
        },
        bundle: true,
        format: 'iife',
        globalName: 'unloadWarningBoundary',
        alias: {
            '#utils': path.resolve('packages/formie-browser/src/js/utils'),
            '#core': path.resolve('packages/formie-browser/src/js/core'),
            '#modules': path.resolve('packages/formie-browser/src/js/modules'),
            '#theme': path.resolve('packages/formie-browser/src/js/theme'),
        },
        write: false,
    });
    guardScript = result.outputFiles[0].text;
});

test.beforeEach(async ({ page }) => {
    await page.setContent(
        '<form><label>Message<input name="fields[message]"></label><input type="hidden" name="fields[hidden]" value="initial"><div data-snaptcha-captcha-placeholder><input type="hidden" name="custom-snaptcha" value="initial"></div></form>',
    );
    await page.addScriptTag({ content: guardScript });
    await page.evaluate(async () => {
        (window as any).guard = (
            window as any
        ).unloadWarningBoundary.createFormUnloadWarningGuard(
            document.querySelector('form'),
        );
        // Wait for the guard's scheduled initialization before changing any values.
        await new Promise<void>((resolve) => {
            requestAnimationFrame(() => setTimeout(resolve, 0));
        });
    });
});

test('integrations can exclude exact transport names through the bubbling collection event', async ({
    page,
}) => {
    const states = await page.evaluate(() => {
        const form = document.querySelector('form')!;
        const guard = (window as any).guard;
        const states: boolean[] = [];
        document.addEventListener(
            'formie:unload-warning:collect-exclusions',
            (event) => {
                const { form: eventForm, exclude } = (event as CustomEvent)
                    .detail;
                if (eventForm === form) {
                    exclude(
                        'snaptcha',
                        'cf-turnstile-response',
                        'g-recaptcha-response',
                        'h-captcha-response',
                        'frc-captcha-solution',
                        'frc-captcha-response',
                        'captcha-eu-token',
                        '__JSCHK',
                        '__DUP',
                        'beesknees',
                        'custom-snaptcha',
                        'rotated-snaptcha-name',
                    );
                }
            },
        );
        for (const name of [
            'snaptcha',
            'cf-turnstile-response',
            'g-recaptcha-response',
            'h-captcha-response',
            'frc-captcha-solution',
            'frc-captcha-response',
            'captcha-eu-token',
            '__JSCHK',
            '__DUP',
            'beesknees',
        ]) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = 'solved';
            form.append(input);
            states.push(guard.refreshDirtyState());
            input.value = 'refreshed';
            states.push(guard.refreshDirtyState());
            input.remove();
            states.push(guard.refreshDirtyState());
        }
        const custom = form.querySelector<HTMLInputElement>(
            '[name="custom-snaptcha"]',
        )!;
        custom.name = 'rotated-snaptcha-name';
        custom.value = 'refreshed';
        states.push(guard.refreshDirtyState());
        custom.remove();
        states.push(guard.refreshDirtyState());
        form.querySelector('[data-snaptcha-captcha-placeholder]')!.append(
            custom,
        );
        const turnstile = document.createElement('input');
        turnstile.type = 'hidden';
        turnstile.name = 'cf-turnstile-response';
        turnstile.value = 'refreshed';
        form.append(turnstile);
        states.push(guard.refreshDirtyState());
        return states;
    });
    expect(states).toEqual(Array(states.length).fill(false));

    // A real user gesture enables Chromium's beforeunload dialog behavior.
    await page.getByRole('textbox', { name: 'Message' }).click();
    const warnings: string[] = [];
    page.on('dialog', async (dialog) => {
        warnings.push(dialog.type());
        await dialog.accept();
    });
    await page.goto('about:blank');
    expect(warnings).toEqual([]);
});

test('visitor edits still warn and reverting the edit restores the clean state', async ({
    page,
}) => {
    const message = page.getByRole('textbox', { name: 'Message' });
    await message.fill('Keep this message');
    expect(
        await page.evaluate(() => (window as any).guard.refreshDirtyState()),
    ).toBe(true);
    const warnings: string[] = [];
    page.on('dialog', async (dialog) => {
        warnings.push(dialog.type());
        await dialog.dismiss();
    });
    await page.goto('about:blank').catch(() => {});
    expect(warnings).toEqual(['beforeunload']);
    await message.fill('');
    expect(
        await page.evaluate(() => (window as any).guard.refreshDirtyState()),
    ).toBe(false);
    await page.goto('about:blank');
    expect(warnings).toEqual(['beforeunload']);
});

test('ordinary hidden field values remain tracked and destroying the guard removes the warning', async ({
    page,
}) => {
    expect(
        await page.evaluate(() => {
            document.querySelector<HTMLInputElement>(
                '[name="fields[hidden]"]',
            )!.value = 'changed';
            return (window as any).guard.refreshDirtyState();
        }),
    ).toBe(true);
    await page.getByRole('textbox', { name: 'Message' }).click();
    await page.evaluate(() => (window as any).guard.destroy());
    const warnings: string[] = [];
    page.on('dialog', async (dialog) => {
        warnings.push(dialog.type());
        await dialog.accept();
    });
    await page.goto('about:blank');
    expect(warnings).toEqual([]);
});

test('late exclusions preserve genuine edits and removing the listener restores tracking', async ({
    page,
}) => {
    const result = await page.evaluate(() => {
        const form = document.querySelector('form')!;
        const guard = (window as any).guard;
        const token = form.querySelector<HTMLInputElement>(
            '[name="custom-snaptcha"]',
        )!;
        token.value = 'refreshed';
        const beforeRegistration = guard.refreshDirtyState();
        const listener = (event: Event) =>
            (event as CustomEvent).detail.exclude('custom-snaptcha');
        form.addEventListener(
            'formie:unload-warning:collect-exclusions',
            listener,
        );
        const afterRegistration = guard.refreshDirtyState();
        form.querySelector<HTMLInputElement>(
            '[name="fields[message]"]',
        )!.value = 'Keep my edit';
        const withVisitorEdit = guard.refreshDirtyState();
        form.querySelector<HTMLInputElement>(
            '[name="fields[message]"]',
        )!.value = '';
        const afterRevert = guard.refreshDirtyState();
        form.removeEventListener(
            'formie:unload-warning:collect-exclusions',
            listener,
        );
        return {
            beforeRegistration,
            afterRegistration,
            withVisitorEdit,
            afterRevert,
            afterRemoval: guard.refreshDirtyState(),
        };
    });
    expect(result).toEqual({
        beforeRegistration: true,
        afterRegistration: false,
        withVisitorEdit: true,
        afterRevert: false,
        afterRemoval: true,
    });
});

test('asynchronous exclusion attempts do not suppress the current check', async ({
    page,
}) => {
    const result = await page.evaluate(async () => {
        const form = document.querySelector('form')!;
        form.addEventListener(
            'formie:unload-warning:collect-exclusions',
            async (event) => {
                await Promise.resolve();
                (event as CustomEvent).detail.exclude('fields[hidden]');
            },
        );
        form.querySelector<HTMLInputElement>('[name="fields[hidden]"]')!.value =
            'changed';
        const initialCheck = (window as any).guard.refreshDirtyState();
        await Promise.resolve();
        return {
            initialCheck,
            nextCheck: (window as any).guard.refreshDirtyState(),
        };
    });
    expect(result).toEqual({ initialCheck: true, nextCheck: true });
});

test('exclusions are scoped to their form and exact names, including repeated values', async ({
    page,
}) => {
    const result = await page.evaluate(() => {
        const first = document.querySelector('form')!;
        const second = document.createElement('form');
        first.insertAdjacentHTML(
            'beforeend',
            '<input name="transport[]" value="a"><input name="transport[]" value="b"><input name="transportExtra" value="keep">',
        );
        second.innerHTML = '<input name="transport[]" value="a">';
        document.body.append(second);
        const firstGuard = (window as any).guard;
        const secondGuard = (
            window as any
        ).unloadWarningBoundary.createFormUnloadWarningGuard(second);
        firstGuard.captureBaseline();
        secondGuard.captureBaseline();
        first.addEventListener(
            'formie:unload-warning:collect-exclusions',
            (event) => (event as CustomEvent).detail.exclude('transport[]'),
        );
        first
            .querySelectorAll<HTMLInputElement>('[name="transport[]"]')
            .forEach((input) => input.remove());
        second.querySelector('input')!.value = 'changed';
        const repeatedRemoved = firstGuard.refreshDirtyState();
        const otherForm = secondGuard.refreshDirtyState();
        first.querySelector<HTMLInputElement>(
            '[name="transportExtra"]',
        )!.value = 'changed';
        const prefixSibling = firstGuard.refreshDirtyState();
        secondGuard.destroy();
        return { repeatedRemoved, otherForm, prefixSibling };
    });
    expect(result).toEqual({
        repeatedRemoved: false,
        otherForm: true,
        prefixSibling: true,
    });
});

for (const surface of ['server-rendered', 'client-rendered']) {
    test(`${surface} CAPTCHA transport declarations follow rotated names and clean up on destroy`, async ({
        page,
    }) => {
        const result = await page.evaluate(async (surface) => {
            const form = document.querySelector('form')!;
            const { createPassiveCaptchaModule } = (window as any)
                .unloadWarningBoundary;
            const module = createPassiveCaptchaModule({
                moduleId: 'test:captcha',
                defaultPlaceholderSelector: '[data-test-captcha]',
            });
            if (surface === 'server-rendered') {
                form.insertAdjacentHTML(
                    'beforeend',
                    '<div data-test-captcha></div>',
                );
            }
            const context = {
                formId: 'test',
                form,
                root: form,
                target: form,
                scope: 'form',
                surface,
                state: {},
                options: { sessionKey: 'custom-snaptcha', value: 'initial' },
                on: () => () => {},
                emit: async () => {},
            };
            const instance = await module.setup(context);
            const guard = (window as any).guard;
            guard.captureBaseline();
            const refresh = (sessionKey: string) =>
                form.dispatchEvent(
                    new CustomEvent('formie:refresh-tokens:after', {
                        detail: {
                            captchas: {
                                captcha: { sessionKey, value: 'refreshed' },
                            },
                        },
                    }),
                );
            refresh('rotated-token');
            const afterRotation = guard.refreshDirtyState();
            guard.captureBaseline();
            refresh('another-token');
            const afterRecapturedRotation = guard.refreshDirtyState();
            form.querySelector<HTMLInputElement>(
                '[name="another-token"]',
            )!.remove();
            const afterRemoval = guard.refreshDirtyState();
            await instance.destroy();
            form.querySelector<HTMLInputElement>(
                '[name="custom-snaptcha"]',
            )!.value = 'now tracked';
            return {
                afterRotation,
                afterRecapturedRotation,
                afterRemoval,
                afterDestroy: guard.refreshDirtyState(),
                generatedPlaceholderRemoved:
                    surface !== 'client-rendered' ||
                    !form.querySelector('[data-test-captcha]'),
            };
        }, surface);
        expect(result).toEqual({
            afterRotation: false,
            afterRecapturedRotation: false,
            afterRemoval: false,
            afterDestroy: true,
            generatedPlaceholderRemoved: true,
        });
    });
}

test('CAPTCHA token writes register custom names and failed setup releases its exclusions', async ({
    page,
}) => {
    const result = await page.evaluate(async () => {
        const form = document.querySelector('form')!;
        const { createCaptchaModule } = (window as any).unloadWarningBoundary;
        const guard = (window as any).guard;
        const context = {
            formId: 'test',
            form,
            root: form,
            target: form,
            scope: 'form',
            surface: 'client-rendered',
            state: {},
            options: { sessionKey: 'custom-snaptcha' },
            on: () => () => {},
            emit: async () => {},
        };
        const module = createCaptchaModule({
            moduleId: 'test:writer',
            defaultPlaceholderSelector: '[data-test-writer]',
            setup: async ({ services }: any) => {
                services.tokens.write('solved', {
                    names: ['provider-response'],
                });
                return {
                    destroy: () => {
                        throw new Error('Provider cleanup failed');
                    },
                };
            },
        });
        const instance = await module.setup(context);
        const afterWrite = guard.refreshDirtyState();
        let destroyFailed = false;
        try {
            await instance.destroy();
        } catch {
            destroyFailed = true;
        }
        form.querySelector('[name="provider-response"]')!.remove();
        form.querySelector<HTMLInputElement>(
            '[name="custom-snaptcha"]',
        )!.value = 'changed';
        const afterFailedDestroy = guard.refreshDirtyState();
        const failing = createCaptchaModule({
            moduleId: 'test:failing',
            defaultPlaceholderSelector: '[data-test-failing]',
            setup: async () => {
                throw new Error('Provider setup failed');
            },
        });
        let setupFailed = false;
        try {
            await failing.setup(context);
        } catch {
            setupFailed = true;
        }
        return {
            afterWrite,
            destroyFailed,
            afterFailedDestroy,
            setupFailed,
            afterFailedSetup: guard.refreshDirtyState(),
            generatedPlaceholdersRemoved: !form.querySelector(
                '[data-test-writer], [data-test-failing]',
            ),
        };
    });
    expect(result).toEqual({
        afterWrite: false,
        destroyFailed: true,
        afterFailedDestroy: true,
        setupFailed: true,
        afterFailedSetup: true,
        generatedPlaceholdersRemoved: true,
    });
});

test('destroyed guards cannot be reactivated or dispatch exclusion checks', async ({
    page,
}) => {
    const result = await page.evaluate(async () => {
        const guard = (window as any).guard;
        let checks = 0;
        document
            .querySelector('form')!
            .addEventListener(
                'formie:unload-warning:collect-exclusions',
                () => {
                    checks++;
                },
            );
        guard.destroy();
        guard.captureBaseline();
        guard.scheduleBaselineCapture();
        await new Promise((resolve) => setTimeout(resolve, 30));
        document.querySelector<HTMLInputElement>(
            '[name="fields[hidden]"]',
        )!.value = 'changed';
        return { dirty: guard.refreshDirtyState(), checks };
    });
    expect(result).toEqual({ dirty: false, checks: 0 });
});

test("one CAPTCHA instance can be removed without removing another instance's exclusions", async ({
    page,
}) => {
    const result = await page.evaluate(async () => {
        const form = document.querySelector('form')!;
        const { createCaptchaModule } = (window as any).unloadWarningBoundary;
        const context = {
            formId: 'test',
            form,
            root: form,
            target: form,
            scope: 'form',
            surface: 'client-rendered',
            state: {},
            options: { sessionKey: 'custom-snaptcha' },
            on: () => () => {},
            emit: async () => {},
        };
        const module = createCaptchaModule({
            moduleId: 'test:overlap',
            defaultPlaceholderSelector: '[data-overlap]',
            setup: async () => ({ destroy: () => {} }),
        });
        const first = await module.setup(context);
        const second = await module.setup(context);
        form.querySelector<HTMLInputElement>(
            '[name="custom-snaptcha"]',
        )!.value = 'refreshed';
        await first.destroy();
        const surviving = (window as any).guard.refreshDirtyState();
        await second.destroy();
        return {
            surviving,
            afterBothRemoved: (window as any).guard.refreshDirtyState(),
        };
    });
    expect(result).toEqual({ surviving: false, afterBothRemoved: true });
});

test('queued visibility work and late SDK writes cannot revive a destroyed CAPTCHA', async ({
    page,
}) => {
    const result = await page.evaluate(async () => {
        const form = document.querySelector('form')!;
        form.insertAdjacentHTML('beforeend', '<div data-lifecycle></div>');
        let services: any;
        let shows = 0;
        const module = (
            window as any
        ).unloadWarningBoundary.createCaptchaModule({
            moduleId: 'test:lifecycle',
            defaultPlaceholderSelector: '[data-lifecycle]',
            defaultTokenFieldNames: ['lifecycle-token'],
            setup: async (context: any) => {
                services = context.services;
                const visibility = services.placeholder.observe(
                    () => {
                        shows++;
                        services.tokens.write('solved');
                    },
                    () => {},
                );
                return {
                    destroy: () => {
                        visibility.cleanup();
                        services.tokens.clear();
                    },
                };
            },
        });
        const context = {
            formId: 'test',
            form,
            root: form,
            target: form,
            scope: 'form',
            surface: 'server-rendered',
            state: {},
            options: {},
            on: () => () => {},
            emit: async () => {},
        };
        const instance = await module.setup(context);
        form.append(document.createElement('span'));
        await Promise.resolve();
        await instance.destroy();
        services.tokens.write('stale', { names: ['stale-sdk-token'] });
        await new Promise((resolve) => setTimeout(resolve, 40));
        return {
            shows,
            token: form.querySelector<HTMLInputElement>(
                '[name="lifecycle-token"]',
            )!.value,
            staleToken: !!form.querySelector('[name="stale-sdk-token"]'),
        };
    });
    expect(result).toEqual({ shows: 1, token: '', staleToken: false });
});

test('numeric zero CAPTCHA widget handles participate in challenge, reset and cleanup', async ({
    page,
}) => {
    const result = await page.evaluate(async () => {
        const form = document.querySelector('form')!;
        form.insertAdjacentHTML('beforeend', '<div data-zero-widget></div>');
        const calls: Array<[string, number]> = [];
        let resolveReset: () => void = () => {};
        const reset = new Promise<void>((resolve) => {
            resolveReset = resolve;
        });
        const module = (
            window as any
        ).unloadWarningBoundary.createManagedCaptchaModule({
            moduleId: 'test:zero',
            defaultPlaceholderSelector: '[data-zero-widget]',
            load: async () => ({}),
            mount: () => 0,
            challenge: ({ widget }: any) => {
                calls.push(['challenge', widget]);
            },
            reset: ({ widget }: any) => {
                calls.push(['reset', widget]);
                resolveReset();
            },
            unmount: ({ widget }: any) => {
                calls.push(['unmount', widget]);
            },
        });
        const context = {
            formId: 'test',
            form,
            root: form,
            target: form,
            scope: 'form',
            surface: 'server-rendered',
            state: {},
            options: {},
            on: () => () => {},
            emit: async () => {},
        };
        const instance = await module.setup(context);
        let aborted = false;
        await instance.beforeSubmit({
            action: 'submit',
            abort: () => {
                aborted = true;
            },
        });
        form.dispatchEvent(new CustomEvent('formie:state:reset'));
        await reset;
        await instance.destroy();
        return { aborted, calls };
    });
    expect(result).toEqual({
        aborted: false,
        calls: [
            ['challenge', 0],
            ['reset', 0],
            ['unmount', 0],
        ],
    });
});

test('managed CAPTCHA teardown cleans every widget when one provider unmount fails', async ({
    page,
}) => {
    const result = await page.evaluate(async () => {
        const form = document.querySelector('form')!;
        form.insertAdjacentHTML(
            'beforeend',
            '<div data-test-managed="first"></div><div data-test-managed="second"></div>',
        );
        const unmounted: string[] = [];
        const module = (
            window as any
        ).unloadWarningBoundary.createManagedCaptchaModule({
            moduleId: 'test:managed',
            defaultPlaceholderSelector: '[data-test-managed]',
            defaultTokenFieldNames: ['managed-token'],
            load: async () => ({}),
            mount: async ({ placeholder, container, services }: any) => {
                container.textContent = 'Provider widget';
                services.tokens.write('solved');
                return placeholder.dataset.testManaged;
            },
            challenge: () => {},
            unmount: async ({ widget }: any) => {
                unmounted.push(widget);
                if (widget === 'first')
                    throw new Error('Provider unmount failed');
            },
        });
        const context = {
            formId: 'test',
            form,
            root: form,
            target: form,
            scope: 'form',
            surface: 'server-rendered',
            state: {},
            options: {},
            on: () => () => {},
            emit: async () => {},
        };
        const instance = await module.setup(context);
        let failed = false;
        try {
            await instance.destroy();
        } catch {
            failed = true;
        }
        return {
            failed,
            unmounted,
            placeholderText: Array.from(
                form.querySelectorAll('[data-test-managed]'),
                (element) => element.textContent,
            ),
            tokenValue: form.querySelector<HTMLInputElement>(
                '[name="managed-token"]',
            )!.value,
        };
    });
    expect(result).toEqual({
        failed: true,
        unmounted: ['first', 'second'],
        placeholderText: ['', ''],
        tokenValue: '',
    });
});

test('managed CAPTCHA teardown waits for pending widgets and releases partial setup on failure', async ({
    page,
}) => {
    const result = await page.evaluate(async () => {
        const form = document.querySelector('form')!;
        form.insertAdjacentHTML(
            'beforeend',
            '<div data-test-managed="initial"></div>',
        );
        let releaseMount: () => void = () => {};
        let pendingStarted: () => void = () => {};
        const started = new Promise<void>((resolve) => {
            pendingStarted = resolve;
        });
        const pending = new Promise<void>((resolve) => {
            releaseMount = resolve;
        });
        const unmounted: string[] = [];
        const module = (
            window as any
        ).unloadWarningBoundary.createManagedCaptchaModule({
            moduleId: 'test:managed',
            defaultPlaceholderSelector: '[data-test-managed]',
            defaultTokenFieldNames: ['managed-token'],
            load: async () => ({}),
            mount: async ({ placeholder, container, services }: any) => {
                if (placeholder.dataset.testManaged === 'late') {
                    pendingStarted();
                    await pending;
                }
                container.textContent = 'Provider widget';
                services.tokens.write('solved');
                return placeholder.dataset.testManaged;
            },
            challenge: () => {},
            unmount: async ({ widget }: any) => {
                unmounted.push(widget);
            },
        });
        const context = {
            formId: 'test',
            form,
            root: form,
            target: form,
            scope: 'form',
            surface: 'server-rendered',
            state: {},
            options: {},
            on: () => () => {},
            emit: async () => {},
        };
        const instance = await module.setup(context);
        form.insertAdjacentHTML(
            'beforeend',
            '<div data-test-managed="late"></div>',
        );
        form.dispatchEvent(new CustomEvent('formie:page:navigate'));
        await started;
        const destroying = instance.destroy();
        releaseMount();
        await destroying;
        await pending;
        await new Promise((resolve) => setTimeout(resolve, 0));
        const pendingCleanup = {
            unmounted: [...unmounted].sort(),
            tokenValue: form.querySelector<HTMLInputElement>(
                '[name="managed-token"]',
            )!.value,
            placeholderText: Array.from(
                form.querySelectorAll('[data-test-managed]'),
                (element) => element.textContent,
            ),
        };

        const partial = (
            window as any
        ).unloadWarningBoundary.createManagedCaptchaModule({
            moduleId: 'test:partial',
            defaultPlaceholderSelector: '[data-test-managed]',
            defaultTokenFieldNames: ['managed-token'],
            load: async () => ({}),
            mount: async ({ placeholder, container }: any) => {
                if (placeholder.dataset.testManaged === 'late')
                    throw new Error('Second widget failed');
                container.textContent = 'Partial widget';
                return 'partial';
            },
            challenge: () => {},
            unmount: async ({ widget }: any) => {
                unmounted.push(widget);
            },
        });
        let setupFailed = false;
        try {
            await partial.setup(context);
        } catch {
            setupFailed = true;
        }
        return {
            pendingCleanup,
            setupFailed,
            partialUnmounted: unmounted.includes('partial'),
            partialPlaceholderText: form.querySelector(
                '[data-test-managed="initial"]',
            )!.textContent,
        };
    });
    expect(result).toEqual({
        pendingCleanup: {
            unmounted: ['initial', 'late'],
            tokenValue: '',
            placeholderText: ['', ''],
        },
        setupFailed: true,
        partialUnmounted: true,
        partialPlaceholderText: '',
    });
});

for (const method of ['ajax', 'page-reload']) {
    for (const earlyEdit of [false, true]) {
        test(`production ${method} forms ${earlyEdit ? 'preserve edits made immediately after mount' : 'ignore Snaptcha refreshes and Turnstile callbacks'}`, async ({
            page,
        }) => {
            if (earlyEdit) {
                await page.clock.install({ time: 0 });
                await page.clock.pauseAt(0);
            }
            const manifest = JSON.stringify({
                contractVersion: 2,
                surface: 'server-rendered',
                entries: [
                    {
                        key: 'snaptcha',
                        moduleId: 'formie:snaptcha',
                        kind: 'captcha',
                        targets: [{ type: 'form' }],
                        config: {
                            sessionKey: 'custom-snaptcha',
                            value: 'initial',
                        },
                        required: true,
                    },
                    {
                        key: 'turnstile',
                        moduleId: 'formie:turnstile',
                        kind: 'captcha',
                        targets: [{ type: 'form' }],
                        config: { siteKey: 'synthetic' },
                        required: true,
                    },
                ],
            }).replaceAll('"', '&quot;');
            const errors: string[] = [];
            page.on('pageerror', (error) => errors.push(error.message));
            // Serve the shipped startup script and lazy chunks. Only the external SDK is synthetic.
            await page.route('https://formie.test/**', async (route) => {
                const pathname = new URL(route.request().url()).pathname;
                if (pathname.startsWith('/assets/')) {
                    await route.fulfill({
                        path: path.resolve(
                            'src/web/assets/frontend/dist',
                            pathname.slice('/assets/'.length),
                        ),
                        contentType: 'text/javascript',
                    });
                    return;
                }
                await route.fulfill({
                    contentType: 'text/html',
                    body:
                        pathname === '/done'
                            ? 'Done'
                            : `<!doctype html><html><body>
                <form data-formie data-formie-unload-warning="true" data-formie-submit-method="${method}" data-formie-modules="${manifest}">
                    <label>Message<input name="fields[message]"></label>
                    <div data-snaptcha-captcha-placeholder></div><div data-turnstile-placeholder></div>
                </form><button id="refresh">Refresh CAPTCHA</button><a href="/done">Leave page</a>
                <script>
                    window.turnstile = { render: (container, options) => { window.solveTurnstile = options.callback; return 'synthetic-widget'; }, reset() {}, remove() {} };
                    document.addEventListener('formie:mount:after', () => { document.documentElement.dataset.ready = 'true'; });
                    document.querySelector('#refresh').addEventListener('click', () => {
                        document.querySelector('form').dispatchEvent(new CustomEvent('formie:refresh-tokens:after', { detail: { captchas: { snaptcha: { sessionKey: 'custom-snaptcha', value: 'refreshed' } } } }));
                        window.solveTurnstile('refreshed-turnstile');
                    });
                </script><script type="module" data-formie-startup src="/assets/js/formie.js"></script>
            </body></html>`,
                });
            });
            await page.goto('https://formie.test/');
            await expect(page.locator('html')).toHaveAttribute(
                'data-ready',
                'true',
            );
            // Check both immediate visitor edits and delayed provider callbacks.
            if (earlyEdit) {
                await page
                    .getByRole('textbox', { name: 'Message' })
                    .fill('Keep this early edit');
                await page.clock.runFor(500);
                await page.clock.resume();
            } else {
                await page.waitForTimeout(500);
            }
            await expect(
                page.locator('input[name="custom-snaptcha"]'),
            ).toHaveValue('initial');
            await page.getByRole('button', { name: 'Refresh CAPTCHA' }).click();
            await expect(
                page.locator('input[name="custom-snaptcha"]'),
            ).toHaveValue('refreshed');
            await expect(
                page.locator('input[name="cf-turnstile-response"]'),
            ).toHaveValue('refreshed-turnstile');
            const warnings: string[] = [];
            page.on('dialog', async (dialog) => {
                warnings.push(dialog.type());
                await dialog.accept();
            });
            await page.getByRole('link', { name: 'Leave page' }).click();
            await expect(page).toHaveURL('https://formie.test/done');
            expect(warnings).toEqual(earlyEdit ? ['beforeunload'] : []);
            expect(errors).toEqual([]);
        });
    }
}
