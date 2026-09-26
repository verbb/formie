import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

const fixture = () => JSON.parse(readFileSync('.cache/verbb-tests/delivery-browser.json', 'utf8'));

async function login(page, username = 'admin') {
    await page.goto('/admin/login');
    await page.getByRole('textbox', { name: 'Username or Email', exact: true }).fill(username);
    await page.locator('input[name="password"]').fill('testing-only-password');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await page.waitForURL(url => !url.pathname.endsWith('/login'));
}

test('opens Formie diagnostics from a failed real Craft job and exports a redacted support bundle', async ({ page }) => {
    const data = fixture();
    await login(page);
    await page.goto('/admin/utilities/queue-manager');
    await page.getByRole('rowheader', { name: 'Delivering form integrations.', exact: true }).click();
    await expect(page.getByRole('button', { name: 'Formie delivery diagnostics', exact: true })).toBeVisible();
    await page.getByRole('button', { name: 'Formie delivery diagnostics', exact: true }).click();
    const dialog = page.locator('pk-dialog');
    await expect(dialog.getByText('browser_simulated_response_loss', { exact: false })).toBeVisible();
    await expect(dialog.locator('pre')).toContainText('mapping-inputs');
    await expect(dialog.locator('pre')).toContainText('Delivery browser fixture');
    await expect(dialog.locator('pre')).toContainText('Gateway response lost');
    await expect(dialog.locator('pre')).not.toContainText('browser-never-display-secret');
    expect(await page.evaluate(() => (window as any).deliveryInjection)).toBeUndefined();
    await page.screenshot({ path: '../context/tasks/08-validation/delivery-modal.png', fullPage: true });
    const download = page.waitForEvent('download');
    await dialog.getByRole('button', { name: 'Download support bundle', exact: true }).click();
    expect((await download).suggestedFilename()).toBe(`formie-delivery-${data.uid}.json`);
    await expect(dialog.getByRole('button', { name: 'Export sensitive evidence', exact: true })).toBeDisabled();
    await dialog.getByRole('link', { name: 'Open Submission Delivery History' }).click();
    await expect(page.getByRole('heading', { name: 'Submission Delivery History' })).toBeVisible();
    await page.getByRole('button', { name: '@dispatch: dispatch (unknown)', exact: true }).click();
    await expect(page.locator('pk-dialog pre')).toContainText('browser_simulated_response_loss');
    await page.getByRole('textbox', { name: 'Reconciliation reason' }).fill('Synthetic fixture confirmed not sent.');
    await page.getByRole('button', { name: 'Confirm not delivered', exact: true }).click();
    await expect(page.locator('pk-dialog').getByRole('status')).toHaveText('Reconciliation recorded.');
    await expect(page.locator('pk-dialog pre')).toContainText('confirmed_not_delivered');
});

test('denies delivery diagnostics without diagnostics and form permissions', async ({ page }) => {
    const data = fixture();
    await login(page, 'browserScopedEditor');
    const status = await page.evaluate(async (uid) => {
        try {
            await (window as any).Craft.sendActionRequest('GET', 'formie/delivery/bundle', { params: { uid } });
            return 200;
        } catch (error: any) { return error.response.status; }
    }, data.uid);
    expect(status).toBe(403);
});
