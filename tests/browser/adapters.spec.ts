import { test, expect } from '@playwright/test';
for (const adapter of ['react', 'vue', 'web-components']) {
    test(`${adapter}: validates, survives remount, and persists one exact submission`, async ({ page, request }) => {
        const errors: string[] = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(`/browser-fixture?adapter=${adapter}`);
        await expect(page.getByLabel('Visitor name', { exact: false })).toBeVisible();
        await page.getByRole('button', { name: 'Unmount', exact: true }).click();
        await expect(page.locator('#host input')).toHaveCount(0);
        await page.getByRole('button', { name: 'Mount', exact: true }).click();
        const name = page.getByLabel('Visitor name', { exact: false });
        const email = page.getByLabel('Visitor email', { exact: false });
        await expect(name).toBeVisible();
        const initialRegions = page.locator('#host [data-formie-field-errors]');
        const initialRegionCount = await initialRegions.count();
        expect(initialRegionCount).toBeGreaterThanOrEqual(2);
        expect((await initialRegions.allTextContents()).map((text) => text.trim())).toEqual(Array(initialRegionCount).fill(''));
        await expect(initialRegions.first()).toHaveAttribute('aria-live', 'polite');
        await expect(initialRegions.first()).toHaveAttribute('aria-atomic', 'true');
        await expect(initialRegions.first()).toHaveCSS('position', 'absolute');
        const before = await (await request.get('/browser-saved')).json();
        await name.fill(`Browser ${adapter}`);
        await page.locator('#host button[type="submit"]').click();
        await expect(email).toHaveAttribute('aria-invalid', 'true');
        const errorId = await email.getAttribute('aria-errormessage');
        expect(errorId).toBeTruthy();
        await expect(email).toHaveAttribute('aria-describedby', errorId!);
        const errorRegion = page.locator('#host').locator(`[id="${errorId}"]`);
        expect((await errorRegion.textContent())?.trim()).not.toBe('');
        await expect(errorRegion).not.toHaveCSS('position', 'absolute');
        await expect(errorRegion).not.toHaveAttribute('role', 'alert');
        await expect(email).toBeFocused();
        await expect(name).toHaveValue(`Browser ${adapter}`);
        expect(await (await request.get('/browser-saved')).json()).toEqual(before);
        await email.fill(`${adapter}@example.test`);
        await page.locator('#host button[type="submit"]').click();
        await expect(page.locator('#host')).toContainText('Submission saved.');
        await expect(errorRegion).toBeAttached();
        await expect(errorRegion).toHaveText('');
        await expect(errorRegion).toHaveCSS('position', 'absolute');
        await expect.poll(async () => (await (await request.get('/browser-saved')).json()).length).toBe(before.length + 1);
        const saved = await (await request.get('/browser-saved')).json();
        expect(saved.filter(row => !before.some(old => old.id === row.id))).toEqual([
            { id: expect.any(Number), name: `Browser ${adapter}`, email: `${adapter}@example.test` },
        ]);
        expect(errors).toEqual([]);
    });

    test(`${adapter}: renders Rich Text, HTML and Heading fields`, async ({ page }) => {
        const errors: string[] = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(`/browser-fixture?adapter=${adapter}&form=displayContract`);
        const host = page.locator('#host');
        await expect(page.getByLabel('Visitor name', { exact: false })).toBeVisible();

        await expect(host.locator('h2', { hasText: 'Before you start' })).toBeVisible();
        await expect(host.locator('strong', { hasText: 'terms' })).toBeVisible();
        await expect(host.locator('.display-notice')).toHaveText('Plain HTML notice');
        await expect(host.locator('h3', { hasText: 'Your details' })).toBeVisible();
        // The heading text appears once, as the heading rather than also as a label.
        await expect(host.getByText('Your details', { exact: true })).toHaveCount(1);
        // Rich Text and HTML labels stay hidden unless the author shows them.
        await expect(host.getByText('Intro label', { exact: true })).toHaveCount(0);
        await expect(host.getByText('Notice label', { exact: true })).toHaveCount(0);
        await expect(host.getByText('Shown intro label', { exact: true })).toBeVisible();
        await expect(host).not.toContainText('Unsupported field type');
        await expect(host).not.toContainText('Unknown field type');
        expect(errors).toEqual([]);
    });
}
