import { test, expect } from '@playwright/test';

test('shares reference syntax in live and posted browser values without evaluating server sources', async ({ page }) => {
    await page.goto('/browser-fixture?adapter=react');
    await page.waitForFunction(() => !!(window as any).referenceBoundary);
    const result = await page.evaluate(() => {
        const runtime = (window as any).referenceBoundary;
        const form = document.createElement('form');
        form.innerHTML = '<input name="fields[answer]" value=""><input name="fields[name][first]" value="Ada">';
        document.body.append(form);
        const registry = runtime.buildFieldValueRegistry(form);
        const emptyLive = runtime.resolveFieldReferenceLive('{field:answer|fallback}', registry);
        const emptyPosted = runtime.resolveFieldReferenceFromFormData('{field:answer|fallback}', new FormData(form), registry);
        const missing = runtime.resolveFieldReferenceLive('{field:deleted|fallback}', registry);
        const child = runtime.resolveFieldReferenceLive('{field:name:first}', registry);
        (form.elements[0] as HTMLInputElement).value = '$SECRET {{ 7 * 7 }}';
        const literal = runtime.resolveFieldReferenceLive('{field:answer}', registry);
        const server = runtime.resolveReference('{custom:acme/secret}', { definitions: { 'custom:acme/secret': { id: 'custom:acme/secret', availability: { server: true, browser: false } } }, values: {} });
        form.remove();
        return { emptyLive, emptyPosted, missing, child, literal, server };
    });
    expect(result.emptyLive.value).toBe('fallback');
    expect(result.emptyPosted.value).toBe('fallback');
    expect(result.missing.diagnostic).toBe('missingField');
    expect(result.child.value).toBe('Ada');
    expect(result.literal.value).toBe('$SECRET {{ 7 * 7 }}');
    expect(result.server.diagnostic).toBe('forbiddenSource');
});

import { readFileSync } from 'node:fs';

test('preserves explicit literal and field mapping modes through save and reload', async ({ page }) => {
    await page.goto('/admin/login');
    await page.getByRole('textbox', { name: 'Username or Email', exact: true }).fill('admin');
    await page.locator('input[name="password"]').fill('testing-only-password');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await page.waitForURL(url => !url.pathname.endsWith('/login'));
    const { formId } = JSON.parse(readFileSync('.cache/verbb-tests/reference-picker.json', 'utf8'));
    await page.goto(`/admin/formie/forms/edit/${formId}`);
    await page.getByRole('tab', { name: 'Integrations', exact: true }).click();
    const literal = page.getByRole('textbox', { name: 'Use text exactly as entered', exact: true });
    await expect(page.getByRole('cell', { name: 'Contact name', exact: true })).toBeVisible();
    if (!(await literal.count())) {
        await page.getByRole('button', { name: 'More actions', exact: true }).click();
        await page.getByRole('menuitem', { name: 'Switch to Custom Value', exact: true }).click();
        await page.getByRole('button', { name: 'More actions', exact: true }).click();
        await page.getByRole('menuitem', { name: 'Use text exactly as entered', exact: true }).click();
    }
    await literal.fill('Literal {field:missing}');
    await page.getByRole('button', { name: 'Save', exact: true }).click();
    await expect(page.locator('#notifications').getByText('Form saved.', { exact: true })).toBeVisible();
    await page.reload();
    await page.getByRole('tab', { name: 'Integrations', exact: true }).click();
    await expect(literal).toHaveValue('Literal {field:missing}');
    await page.getByRole('button', { name: 'More actions', exact: true }).click();
    await page.getByRole('menuitem', { name: 'Switch to Field Value', exact: true }).click();
    await page.getByRole('button', { name: "Don't Include", exact: true }).click();
    await page.getByRole('listbox').getByRole('button', { name: 'Contact name', exact: true }).click();
    await page.getByRole('button', { name: 'Save', exact: true }).click();
    await expect(page.locator('#notifications').getByText('Form saved.', { exact: true })).toBeVisible();
    await page.reload();
    await page.getByRole('tab', { name: 'Integrations', exact: true }).click();
    await expect(page.getByRole('button', { name: 'Contact name', exact: true })).toBeVisible();
    await expect(literal).toHaveCount(0);
});
