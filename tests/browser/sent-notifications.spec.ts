import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

const fixture = () => JSON.parse(readFileSync('.cache/verbb-tests/sent-notification-browser.json', 'utf8'));

async function login(page) {
    await page.goto('/admin/login');
    await page.getByRole('textbox', { name: 'Username or Email', exact: true }).fill('admin');
    await page.locator('input[name="password"]').fill('testing-only-password');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await page.waitForURL((url) => !url.pathname.endsWith('/login'));
}

async function expectSingleResendDialog(page) {
    const dialog = page.locator('pk-dialog[data-formie-resend-dialog="single"]');
    await expect(dialog.getByRole('heading', { name: 'Resend Email Notification', exact: true })).toBeVisible();
    expect((await dialog.locator('dialog').boundingBox())?.width).toBeGreaterThanOrEqual(820);
    await expect(dialog.getByRole('textbox', { name: 'Recipients' })).toHaveValue('recipient@example.test');
    await expect(dialog.getByText('Browser sent notification subject', { exact: true })).toBeVisible();
    await expect(dialog.getByText('Browser Sender <sender@example.test>', { exact: true })).toBeVisible();
    await expect(dialog.locator('iframe[title="Email preview"]')).toHaveAttribute('sandbox', 'allow-same-origin');
    await dialog.getByRole('button', { name: 'Cancel', exact: true }).click();
    await expect(dialog).toHaveCount(0);
}

test('uses the shared Plugin Kit resend dialog from the index and detail pages', async ({ page }) => {
    const data = fixture();
    await login(page);

    await page.goto('/admin/formie/sent-notifications');
    await page.locator(`.js-fui-notification-modal-resend-btn[data-id="${data.id}"]`).click();
    await expectSingleResendDialog(page);

    const editUrl = new URL(data.editUrl);
    await page.goto(`${editUrl.pathname}${editUrl.search}`);
    await page.locator(`.js-fui-notification-modal-resend-btn[data-id="${data.id}"]`).click();
    await expectSingleResendDialog(page);
});

test('uses a Plugin Kit dialog for bulk resend options', async ({ page }) => {
    const data = fixture();
    await login(page);
    await page.goto('/admin/formie/sent-notifications');

    await page.evaluate((id) => {
        new (window as any).Craft.Formie.BulkResendModal([String(id)]);
    }, data.id);

    const dialog = page.locator('pk-dialog[data-formie-resend-dialog="bulk"]');
    await expect(dialog.getByRole('heading', { name: 'Bulk Resend Email Notifications', exact: true })).toBeVisible();
    expect((await dialog.locator('dialog').boundingBox())?.width).toBeLessThan(800);
    await expect(dialog.getByText('You are about to resend 1 notification email.', { exact: false })).toBeVisible();
    await expect(dialog.getByRole('button', { name: 'Recipients', exact: true })).toContainText('Original Recipients');
});
