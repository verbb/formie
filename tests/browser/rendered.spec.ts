import { test, expect } from '@playwright/test';

for (const mode of ['ajax', 'native', 'cached']) {
    test(`Twig-rendered ${mode}: validates, clears hidden content and saves the calculated result`, async ({ page, request }) => {
        const pageErrors: string[] = [];
        page.on('pageerror', error => pageErrors.push(error.message));
        await page.addInitScript(() => {
            document.addEventListener('formie:mount:after', () => { document.documentElement.dataset.formieReady = 'true'; });
        });
        const name = `Rendered ${mode} ${Date.now()}`;
        const savedBefore = await (await request.get('/browser-rendered-saved')).json();
        const refreshed = mode === 'cached' ? page.waitForResponse(response => response.url().includes('/refresh-tokens') && response.ok()) : null;
        const initial = await page.goto(`/browser-rendered?method=${mode}${mode === 'cached' ? '&cached=1' : ''}`);
        if (refreshed) {
            expect(await initial!.text()).not.toContain('name="CRAFT_CSRF_TOKEN"');
            await refreshed;
            await expect(page.locator('input[name="CRAFT_CSRF_TOKEN"]')).not.toHaveValue('');
        }
        await expect(page.locator('html')).toHaveAttribute('data-formie-ready', 'true');
        await expect(page.locator('script[data-formie-startup]')).toHaveAttribute('src', /\/cpresources\/.+\/js\/formie\.js$/);
        await expect(page.locator('form[data-formie]')).toHaveAttribute('data-formie-submit-method', mode === 'native' ? 'page-reload' : 'ajax');
        const initialRegions = page.locator('form[data-formie] [data-formie-field-errors]');
        const initialRegionCount = await initialRegions.count();
        expect(initialRegionCount).toBeGreaterThan(0);
        expect(await initialRegions.allTextContents()).toEqual(Array(initialRegionCount).fill(''));
        await expect(initialRegions.first()).toHaveAttribute('aria-live', 'polite');
        await expect(initialRegions.first()).toHaveAttribute('aria-atomic', 'true');
        await expect(initialRegions.first()).toHaveCSS('position', 'absolute');
        await page.getByLabel('Enquiry', { exact: true }).selectOption('other');
        await page.getByLabel('Details', { exact: true }).fill('Discard this hidden value');
        await page.getByLabel('Quantity', { exact: true }).fill('3');
        await page.getByLabel('Price', { exact: true }).fill('7');
        await expect(page.locator('input[data-formie-calculation-input]')).toHaveValue('21');
        await page.getByLabel('Quantity', { exact: true }).fill('4');
        await expect(page.locator('input[data-formie-calculation-input]')).toHaveValue('28');
        await page.getByRole('button', { name: 'Submit', exact: true }).click();
        await expect(page.getByLabel('Details', { exact: true })).toHaveValue('Discard this hidden value');
        const visitorName = page.getByRole('textbox', { name: 'Visitor name', exact: true });
        await expect(visitorName).toHaveAttribute('aria-invalid', 'true');
        const errorId = await visitorName.getAttribute('aria-errormessage');
        expect(errorId).toBeTruthy();
        await expect(visitorName).toHaveAttribute('aria-describedby', new RegExp(`(?:^|\\s)${errorId}(?:\\s|$)`));
        const errorRegion = page.locator(`[id="${errorId}"]`);
        expect((await errorRegion.textContent())?.trim()).not.toBe('');
        await expect(errorRegion).not.toHaveAttribute('role', 'alert');
        const stableErrorRegionParent = errorRegion.locator('..');
        await expect(stableErrorRegionParent).toHaveAttribute('data-formie-field-errors', '');
        const stableErrorRegionId = await stableErrorRegionParent.getAttribute('id');
        expect(stableErrorRegionId).toBeTruthy();
        const stableErrorRegion = page.locator(`[id="${stableErrorRegionId}"]`);
        await expect(stableErrorRegion).not.toHaveCSS('position', 'absolute');
        expect(await (await request.get('/browser-rendered-saved')).json()).toEqual(savedBefore);
        await visitorName.fill(name);
        await expect(stableErrorRegion).toBeAttached();
        await expect(stableErrorRegion).toHaveText('');
        await expect(stableErrorRegion).toHaveCSS('position', 'absolute');
        await page.getByLabel('Enquiry', { exact: true }).selectOption('general');
        await expect(page.getByLabel('Details', { exact: true })).not.toBeVisible();
        // The real rendered form keeps its three-second minimum-submit-time spam guard.
        await page.waitForFunction(() => {
            const started = Number((document.querySelector('input[name="formStartedAt"]') as HTMLInputElement)?.value);
            return started > 0 && Date.now() - started >= 3500;
        });
        const navigation = mode === 'native' ? page.waitForNavigation({ waitUntil: 'domcontentloaded' }) : null;
        await page.getByRole('button', { name: 'Submit', exact: true }).click();
        if (navigation) { await navigation; }
        await expect(page.getByText('Submission saved.', { exact: true })).toBeVisible();
        await expect.poll(async () => (await (await request.get('/browser-rendered-saved')).json()).filter(row => row.name === name)).toEqual([{ name, details: '', total: '28' }]);
        expect(pageErrors).toEqual([]);
    });
}
