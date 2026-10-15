import { test, expect, type Page } from '@playwright/test';

async function observeEvents(page: Page) {
    await page.addInitScript(() => {
        const counts = { ready: 0, legacyReady: 0, validator: 0, legacyValidator: 0, legacySuccess: 0 };
        (window as any).compatibilityEvents = counts;
        for (const [event, key] of Object.entries({
            'formie:mount:after': 'ready',
            onFormieReady: 'legacyReady',
            'formie:validator:ready': 'validator',
            formieValidatorInitialized: 'legacyValidator',
            onAfterFormieSubmit: 'legacySuccess',
        } as const)) {
            document.addEventListener(event, () => counts[key]++);
        }
    });
}

for (const enabled of [true, false]) {
    test(`automatic startup follows plugin compatibility mode: ${enabled}`, async ({ page }) => {
        await observeEvents(page);
        await page.goto(`/browser-rendered?compatibility=${enabled ? '1' : '0'}`);
        await expect(page.locator('form[data-formie]')).toHaveAttribute('data-formie-compatibility', String(enabled));
        await expect.poll(() => page.evaluate(() => (window as any).compatibilityEvents.ready)).toBe(1);
        const { validator, ...events } = await page.evaluate(() => (window as any).compatibilityEvents);
        expect(validator).toBeGreaterThan(0);
        expect(events).toEqual({
            ready: 1, legacyReady: enabled ? 1 : 0,
            legacyValidator: enabled ? 1 : 0, legacySuccess: 0,
        });
        // Exercise the adapter after startup without sending a submission.
        await page.locator('form[data-formie]').evaluate(form => {
            form.dispatchEvent(new CustomEvent('formie:submit:result', { bubbles: true, detail: { ok: true } }));
        });
        expect(await page.evaluate(() => (window as any).compatibilityEvents.legacySuccess)).toBe(enabled ? 1 : 0);
    });
}

for (const transport of ['rest', 'graphql']) {
    test(`fetched ${transport} HTML inherits compatibility mode and remounts without duplicate events`, async ({ page }) => {
        await observeEvents(page);
        await page.goto(`/browser-fixture?adapter=react&product=server&form=renderedContract&transport=${transport}`);
        await expect.poll(() => page.evaluate(() => (window as any).compatibilityEvents.ready)).toBe(1);
        expect(await page.evaluate(() => (window as any).compatibilityEvents.legacyReady)).toBe(1);
        await page.getByRole('button', { name: 'Unmount', exact: true }).click();
        await page.getByRole('button', { name: 'Mount', exact: true }).click();
        await expect.poll(() => page.evaluate(() => (window as any).compatibilityEvents.ready)).toBe(2);
        expect(await page.evaluate(() => (window as any).compatibilityEvents.legacyReady)).toBe(2);
    });
}

test('an explicit browser option overrides the rendered plugin setting', async ({ page }) => {
    await page.goto('/browser-fixture');
    await page.waitForFunction(() => !!(window as any).legacyModules);
    const events = await page.evaluate(async () => {
        const { createFormieClient } = (window as any).legacyModules;
        const client = createFormieClient();
        const host = document.createElement('div');
        document.body.append(host);
        let ready = 0;
        host.addEventListener('onFormieReady', () => ready++);
        await client.mount(host, { mode: 'server-rendered', formHandle: 'renderedContract', compatibility: false });
        const setting = host.querySelector('form')?.getAttribute('data-formie-compatibility');
        await client.unmount(host);
        host.remove();
        return { ready, setting };
    });
    expect(events).toEqual({ ready: 0, setting: 'true' });
});
