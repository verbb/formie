import { test, expect } from '@playwright/test';

test('module occurrences reconcile repeated targets, conditional DOM and failures', async({ page }) => {
    await page.goto('/browser-fixture?adapter=react');
    await page.waitForFunction(() => !!(window as any).browserModules);
    const result = await page.evaluate(async() => {
        const { hydrateFormieModules, ModuleRegistry } = (window as any).browserModules;
        const root = document.createElement('form'); document.body.append(root);
        root.innerHTML = '<div data-formie-field-uid="field-uid"></div>';
        let mounts = 0, disposes = 0;
        const registry = new ModuleRegistry();
        registry.register({ moduleId: 'test:repeat', version: 2, surfaces: ['client-rendered'], kind: 'field', match: () => true, setup: async() => { mounts++; return { destroy: () => { disposes++; } }; } });
        const entry = { key: 'first', moduleId: 'test:repeat', kind: 'field', targets: [{ type: 'field', uid: 'field-uid' }], config: {}, required: true };
        const host = await hydrateFormieModules({ root, registry, surface: 'client-rendered', modules: { contractVersion: 2, surface: 'client-rendered', entries: [entry, { ...entry, key: 'second' }] } });
        const settle = () => new Promise((resolve) => setTimeout(resolve, 50));
        const initial = mounts;
        root.append(document.createElement('span')); await settle(); const unchanged = mounts;
        const row = document.createElement('div'); row.dataset.formieFieldUid = 'field-uid'; root.append(row); await settle();
        const repeated = mounts;
        row.hidden = true; await settle(); const hidden = disposes;
        row.hidden = false; await settle(); const shown = mounts;
        row.remove(); await settle(); const removed = disposes;
        await host.destroy(); const destroyed = disposes;
        const failure = async(required: boolean) => {
            const failureHost = await hydrateFormieModules({ root, surface: 'client-rendered', modules: { contractVersion: 2, surface: 'client-rendered', entries: [{ ...entry, key: 'failure', moduleId: 'unknown:module', required }] } });
            let blocked = false; try { failureHost.assertReady(); } catch { blocked = true; }
            await failureHost.destroy(); return blocked;
        };
        const optional = await failure(false); const required = await failure(true);
        let versionRejected = false;
        try { await hydrateFormieModules({ root, modules: { contractVersion: 99, surface: 'cp-edit', entries: [] } }); } catch { versionRejected = true; }
        root.remove();
        return { initial, unchanged, repeated, hidden, shown, removed, destroyed, optional, required, versionRejected };
    });
    expect(result).toEqual({ initial: 2, unchanged: 2, repeated: 4, hidden: 2, shown: 6, removed: 4, destroyed: 6, optional: false, required: true, versionRejected: true });
});

for (const adapter of ['react', 'vue', 'web-components']) {
    test(`${adapter}: canonical entries and unsupported bootstrap versions`, async({ page, request }) => {
        const parity = await (await request.get('/browser-module-parity')).json();
        expect(parity.server.surface).toBe('server-rendered');
        expect(parity.bootstrap.surface).toBe('client-rendered');
        expect(parity.server.entries).toEqual(parity.bootstrap.entries);
        expect(parity.graphql.errors).toBeUndefined();
        expect(parity.graphql.data.formieClientForm.contractVersion).toBe(1);
        expect(parity.graphql.data.formieClientForm.definition.modules).toEqual(parity.bootstrap);
        expect(parity.server.entries.length).toBeGreaterThan(0);
        const response = page.waitForResponse((response) => response.url().includes('/client/forms/load'));
        await page.goto(`/browser-fixture?adapter=${adapter}&form=browserJourney`);
        const bootstrap = await (await response).json();
        expect(bootstrap.definition.modules).toEqual(parity.bootstrap);
        await page.getByLabel('Visitor name', { exact: false }).fill('Parity');
        await page.locator('#host button[type="submit"]').click();
        await page.waitForFunction(() => (window as any).moduleObservations.filter((event) => event.name === 'formie:browser:module:mount').length > 0);
        const entries = parity.bootstrap.entries;
        await expect.poll(() => page.evaluate(() => (window as any).moduleObservations.filter((event) => event.name === 'formie:browser:module:mount').map((event) => event.key).sort())).toEqual(entries.map((entry) => entry.key).sort());
        expect(await page.evaluate(() => (window as any).moduleObservations.filter((event) => event.name === 'formie:browser:module:error'))).toEqual([]);
        await page.route('**/client/forms/load*', async(route) => {
            const response = await route.fetch();
            const body = await response.json(); body.contractVersion = 999;
            await route.fulfill({ response, json: body });
        });
        await page.reload();
        await expect(page.locator('#host')).toContainText('Unsupported client-rendered contractVersion');
        await expect(page.locator('#host form')).toHaveCount(0);
    });
}

test('initialization failure policy, manifest updates and untrusted URLs', async({ page }) => {
    await page.goto('/browser-fixture');
    await page.waitForFunction(() => !!(window as any).browserModules);
    const result = await page.evaluate(async() => {
        const { hydrateFormieModules, ModuleRegistry } = (window as any).browserModules;
        const root = document.createElement('form'); document.body.append(root);
        const registry = new ModuleRegistry(); let updates = 0, disposed = 0;
        registry.register({ moduleId: 'test:update', version: 2, surfaces: ['server-rendered'], kind: 'core', match: () => true, setup: async() => ({ update: () => { updates++; }, destroy: () => { disposed++; } }) });
        registry.register({ moduleId: 'test:throw', version: 2, surfaces: ['server-rendered'], kind: 'core', match: () => true, setup: async() => { throw new Error('provider failed'); } });
        const entry = { key: 'one', moduleId: 'test:update', kind: 'core', targets: [{ type: 'form' }], config: {}, required: true };
        const manifest = (entries) => ({ contractVersion: 2, surface: 'server-rendered', entries });
        const host = await hydrateFormieModules({ root, registry, surface: 'server-rendered', modules: manifest([entry]) });
        await host.update(manifest([{ ...entry, config: { changed: true } }]));
        await host.update(manifest([]));
        await host.update(manifest([{ ...entry, kind: 'payment' }]));
        let kindMismatch = false;
        try { host.assertReady(); } catch { kindMismatch = true; }
        await host.update(manifest([]));
        const failed = async(required: boolean) => {
            await host.update(manifest([{ ...entry, moduleId: 'test:throw', required }]));
            try { host.assertReady(); return false; } catch { return true; }
        };
        const optional = await failed(false), required = await failed(true);
        let src = false, version = false;
        try { await host.update(manifest([{ ...entry, src: 'https://evil.invalid/execute.js' }])); } catch { src = true; }
        try { registry.register({ moduleId: 'test:future', version: 999 }); } catch { version = true; }
        await host.destroy(); root.remove();
        return { updates, disposed, kindMismatch, optional, required, src, version };
    });
    expect(result).toEqual({ updates: 1, disposed: 1, kindMismatch: true, optional: false, required: true, src: true, version: true });
});

test('discriminated targets and invalid server manifests fail safely', async({ page }) => {
    await page.goto('/browser-fixture');
    await page.waitForFunction(() => !!(window as any).legacyModules);
    const result = await page.evaluate(async() => {
        const { hydrateFormieModules, ModuleRegistry } = (window as any).browserModules;
        const { createFormieClient } = (window as any).legacyModules;
        const root = document.createElement('form'); document.body.append(root);
        root.innerHTML = '<section data-formie-page-id="page-one"></section><button data-formie-action="submit"></button><span data-test-global></span>';
        const registry = new ModuleRegistry(); const targets: string[] = [];
        registry.register({ moduleId: 'test:targets', version: 2, surfaces: ['server-rendered'], kind: 'core', match: () => true, setup: async(context) => { targets.push(context.scope); return { destroy: () => {} }; } });
        const entries = [
            { key: 'form', target: { type: 'form' } },
            { key: 'page', target: { type: 'page', id: 'page-one' } },
            { key: 'action', target: { type: 'action', action: 'submit' } },
            { key: 'selector', target: { type: 'selector', selector: '[data-test-global]' } },
        ].map(({ key, target }) => ({ key, moduleId: 'test:targets', kind: 'core', targets: [target], config: {}, required: true }));
        const host = await hydrateFormieModules({ root, registry, surface: 'server-rendered', modules: { contractVersion: 2, surface: 'server-rendered', entries } });
        await host.destroy(); root.remove();
        const invalid: boolean[] = [];
        for (const value of ['{"contractVersion":999,"surface":"server-rendered","entries":[]}', '{broken']) {
            const form = document.createElement('form'); document.body.append(form);
            form.setAttribute('data-formie-modules', value);
            let rejected = false;
            try { await createFormieClient().mount(form, {}); } catch { rejected = true; }
            const event = new Event('submit', { cancelable: true }); form.dispatchEvent(event);
            invalid.push(rejected && event.defaultPrevented && !!form.querySelector('[role="alert"]'));
            form.remove();
        }
        const shell = document.createElement('div'); document.body.append(shell);
        let rejectedPayload = false;
        try { await createFormieClient().mount(shell, { payload: { html: `<form data-formie-modules='{"contractVersion":2,"surface":"server-rendered","entries":[]}'></form>`, modules: { contractVersion: 999, surface: 'server-rendered', entries: [] } } }); } catch { rejectedPayload = true; }
        invalid.push(rejectedPayload); shell.remove();
        return { targets, invalid };
    });
    expect(result).toEqual({ targets: ['form', 'page', 'action', 'selector'], invalid: [true, true, true] });
});


test('shared module hosts expose stable submit hooks and apply optional hook failure policy', async({ page }) => {
    await page.goto('/browser-fixture');
    await page.waitForFunction(() => !!(window as any).browserModules);
    const result = await page.evaluate(async() => {
        const { hydrateFormieModules, ModuleRegistry } = (window as any).browserModules;
        const form = document.createElement('form'); document.body.append(form);
        const registry = new ModuleRegistry(); const stages: string[] = [];
        registry.register({ moduleId: 'test:hooks', version: 2, surfaces: ['client-rendered'], kind: 'core', match: () => true, setup: async() => ({ destroy: () => {}, beforeSubmit: (ctx) => stages.push(`before:${ctx.action}`), afterSubmit: (ctx) => stages.push(`after:${ctx.action}`) }) });
        registry.register({ moduleId: 'test:optional', version: 2, surfaces: ['client-rendered'], kind: 'core', match: () => true, setup: async() => ({ destroy: () => { throw new Error('optional disposal unavailable'); }, beforeSubmit: () => { throw new Error('optional unavailable'); } }) });
        const entry = { key: 'hooks', moduleId: 'test:hooks', kind: 'core', targets: [{ type: 'form' }], config: {}, required: true };
        const host = await hydrateFormieModules({ root: form, registry, surface: 'client-rendered', modules: { contractVersion: 2, surface: 'client-rendered', entries: [entry, { ...entry, key: 'optional', moduleId: 'test:optional', required: false }] } });
        await host.prepare('submit'); await host.result({ ok: true }); host.assertReady();
        await host.destroy(); form.remove(); return stages;
    });
    expect(result).toEqual(['before:submit', 'after:submit']);
});
