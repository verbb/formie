import { readFile } from 'node:fs/promises';

import { test, expect, type Page } from '@playwright/test';

const signIn = async (page: Page, username: string) => {
    await page.goto('/admin/login');
    await page.getByRole('textbox', { name: 'Username or Email', exact: true }).fill(username);
    await page.locator('input[name="password"]').fill('testing-only-password');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await page.waitForURL(url => !url.pathname.endsWith('/login'));
};

const downloadExport = async (page: Page, label: string | RegExp) => {
    const [download] = await Promise.all([
        page.waitForEvent('download'),
        page.getByRole('button', { name: label }).click(),
    ]);

    return { filename: download.suggestedFilename(), contents: await readFile(await download.path(), 'utf8') };
};

test('exports from the submissions index without a saved report', async ({ page }) => {
    const requests: string[] = [];
    page.on('request', (request) => { requests.push(request.url()); });
    await signIn(page, 'browserExportClient');
    await page.goto('/admin/formie/submissions/exportDialogForm');

    const exportButton = page.locator('#footer').getByRole('button', { name: 'Export…', exact: true });
    await expect(exportButton).toBeVisible();
    // Opening the index must not load the dialog's code or form options.
    expect(requests.filter(url => /submissionExportModal|submission-export-options/.test(url))).toEqual([]);

    await exportButton.click();
    const dialog = page.getByRole('dialog', { name: 'Export Submissions' });
    await expect(dialog).toBeVisible();

    // The default state includes every submission type, matching what the index shows.
    await expect(page.getByRole('button', { name: 'Export 4 submissions', exact: true })).toBeVisible();

    const everything = await downloadExport(page, 'Export 4 submissions');
    expect(everything.filename).toMatch(/^exportDialogForm-submissions-.+\.xlsx$/);
    await expect(dialog).toHaveCount(0);
    await expect(exportButton).toBeFocused();
});

test('starts from the index filters and lets them be changed', async ({ page }) => {
    await signIn(page, 'browserExportClient');
    await page.goto('/admin/formie/submissions/exportDialogForm?state=complete');
    await page.locator('#footer').getByRole('button', { name: 'Export…', exact: true }).click();

    await expect(page.getByRole('button', { name: 'Export 2 submissions', exact: true })).toBeVisible();

    // The checkbox's own visual control covers its input, so a real click lands on that.
    await page.getByRole('checkbox', { name: 'Incomplete', exact: true }).click({ force: true });
    await expect(page.getByRole('button', { name: 'Export 3 submissions', exact: true })).toBeVisible();

    // A narrowed column choice reaches the file.
    await page.getByRole('button', { name: /\d+ of \d+ columns/ }).click();
    await page.getByRole('checkbox', { name: 'Topic', exact: true }).click({ force: true });

    await page.getByRole('button', { name: 'Select no columns', exact: true }).click();
    await page.getByRole('button', { name: 'Export 3 submissions', exact: true }).click();
    await expect(page.getByText('Choose at least one column.', { exact: true })).toBeVisible();
    await page.getByRole('button', { name: 'Select all columns', exact: true }).click();
    await expect(page.getByRole('button', { name: /^All \d+ columns/ })).toBeVisible();
    await page.getByRole('checkbox', { name: 'Topic', exact: true }).click({ force: true });

    await page.getByRole('button', { name: 'Format', exact: true }).click();
    await page.getByRole('option', { name: 'CSV', exact: true }).click();

    const exported = await downloadExport(page, 'Export 3 submissions');
    expect(exported.filename).toMatch(/\.csv$/);
    expect(exported.contents).toContain('First answer');
    expect(exported.contents).toContain('Draft answer');
    expect(exported.contents).not.toContain('Spam answer');
    expect(exported.contents).not.toContain('Billing');
});

test('hides the export button from users who cannot export', async ({ page }) => {
    await signIn(page, 'browserScopedEditor');
    await page.goto('/admin/formie/submissions');
    await expect(page.getByRole('button', { name: 'New submission', exact: true })).toBeVisible();
    await expect(page.locator('#footer').getByRole('button', { name: 'Export…', exact: true })).toHaveCount(0);
});

test('lets a report manager create and edit a report', async ({ page }) => {
    await signIn(page, 'browserReportManager');
    await page.goto('/admin/formie/reports');
    await page.getByRole('button', { name: 'New report', exact: true }).first().click();

    // The name field is focused on open, and the form is preselected when there's only one.
    const name = `Client report ${Date.now()}`;
    await expect(page.getByRole('dialog', { name: 'New report' })).toBeVisible();
    await page.keyboard.type(name);
    await page.getByRole('button', { name: 'Create report', exact: true }).click();

    await page.waitForURL(url => !url.toString().includes('reports/create') && url.toString().includes('formie/reports/'));

    // With a single report, the dashboard shows it directly rather than a picker naming it.
    const edit = page.getByRole('link', { name: 'Edit', exact: true });
    await edit.click();
    await expect(page.getByText('Only administrators can change', { exact: false })).toHaveCount(0);
    await expect(page.getByRole('textbox').first()).toHaveValue(name);
    await page.getByRole('button', { name: 'Save', exact: true }).first().click();
    await page.waitForURL(url => !url.toString().includes('/edit/'));
    await expect(page.getByRole('link', { name: 'Edit', exact: true })).toBeVisible();
});
