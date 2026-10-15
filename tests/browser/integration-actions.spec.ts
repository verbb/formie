import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

test('sends an explicit automation test through its own action and displays transport rejection', async ({ page }) => {
    await page.goto('/admin/login');
    await page.getByRole('textbox', { name: 'Username or Email', exact: true }).fill('admin');
    await page.locator('input[name="password"]').fill('testing-only-password');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await page.waitForURL(url => !url.pathname.endsWith('/login'));
    const { formId } = JSON.parse(readFileSync('.cache/verbb-tests/automation-test.json', 'utf8'));
    await page.goto(`/admin/formie/forms/edit/${formId}`);
    await page.getByRole('tab', { name: 'Integrations', exact: true }).click();
    await page.getByRole('button', { name: /Browser automation test/ }).click();
    const responsePromise = page.waitForResponse(response => response.url().includes('send-test-payload') && response.request().method() === 'POST');
    await page.getByRole('button', { name: 'Send Test Payload', exact: true }).click();
    const response = await responsePromise;
    // The fixture deliberately uses loopback: the real server must reject it before sending.
    expect(response.status()).toBeGreaterThanOrEqual(400);
    expect(await response.text()).toContain('Outbound integration URL must use a public HTTP or HTTPS endpoint.');
    await expect(page.locator('pk-alert[variant="error"]')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Send Test Payload', exact: true })).toBeEnabled();
});
