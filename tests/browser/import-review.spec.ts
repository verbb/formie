import { readFile } from 'node:fs/promises';
import { randomUUID } from 'node:crypto';

import { test, expect, type Page } from '@playwright/test';

const settingsUrl = '/admin/formie/settings/import-export';
const red = 'rgb(208, 2, 27)';

const signIn = async (page: Page) => {
    await page.goto('/admin/login');
    await page.getByRole('textbox', { name: 'Username or Email', exact: true }).fill('admin');
    await page.locator('input[name="password"]').fill('testing-only-password');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await page.waitForURL(url => !url.pathname.endsWith('/login'));
};

const exportForm = async (page: Page, title: string) => {
    await page.goto(settingsUrl);
    await page.locator('select[name="formId"]').selectOption(await page.locator('select[name="formId"] option', { hasText: title }).first().getAttribute('value') as string);
    const [download] = await Promise.all([
        page.waitForEvent('download'),
        page.getByRole('button', { name: 'Export', exact: true }).click(),
    ]);
    const contents = JSON.parse(await readFile(await download.path(), 'utf8'));

    return Array.isArray(contents) ? contents[0] : contents;
};

const upload = async (page: Page, document: unknown) => {
    await page.goto(settingsUrl);
    await page.locator('input[name="file"]').setInputFiles({
        name: 'import.json', mimeType: 'application/json', buffer: Buffer.from(JSON.stringify(document)),
    });
    await page.getByRole('button', { name: 'Review Import', exact: true }).click();
    await page.waitForURL(/import-configure/);
};

const fields = (document: any) => document.pages[0].rows.flatMap((row: any) => row.fields);
const field = (document: any, handle: string) => fields(document).find((node: any) => node.settings.handle === handle);
const importButton = (page: Page) => page.getByRole('button', { name: 'Import', exact: true });
const line = (page: Page, text: string) => page.locator('.log-label', { hasText: text });
const formCount = async (page: Page, title: string) => {
    await page.goto(settingsUrl);

    return page.locator('select[name="formId"] option', { hasText: title }).count();
};

test.describe.configure({ mode: 'serial' });

test('reviews a routine import with only the log and the form action', async ({ page }) => {
    await signIn(page);
    await upload(page, await exportForm(page, 'Import Review A'));

    await expect(line(page, 'Synced field, already on this site.')).toBeVisible();
    await expect(line(page, 'Form handle is')).toHaveCSS('color', red);
    await expect(page.locator('main, #content').first()).not.toContainText(/Retained:|reuseUid|RemovedNotifications|independent/);
    await expect(page.locator('select[name^="syncedFields"]')).toHaveCount(0);

    const count = await formCount(page, 'Import Review A');
    await page.goBack();
    await importButton(page).click();
    await expect(page.locator('p.error')).toHaveText('Choose how to handle the existing form.');
    expect(await formCount(page, 'Import Review A')).toBe(count);
});

test('names what an update removes before replacing the form', async ({ page }) => {
    await signIn(page);
    const document = await exportForm(page, 'Import Review A');
    document.pages[0].rows.forEach((row: any) => { row.fields = row.fields.filter((node: any) => node.settings.handle !== 'extra'); });
    document.notifications = [];
    await upload(page, document);

    await expect(page.locator('#action-update')).toBeHidden();
    await page.locator('#formAction').selectOption('update');
    await expect(page.locator('#action-update')).toContainText('Removes these fields: Extra.');
    await expect(page.locator('#action-update')).toContainText('Removes these notifications: Old notice.');
    await importButton(page).click();
    await expect(page.locator('#content h2', { hasText: 'Import Completed' })).toBeVisible();
    expect(field(await exportForm(page, 'Import Review A'), 'extra')).toBeUndefined();
});

test('requires a choice when a synced field differs, and only then changes other forms', async ({ page }) => {
    await signIn(page);
    const document = await exportForm(page, 'Import Review B');
    field(document, 'email').settings.label = 'Changed label';
    await upload(page, document);

    await expect(line(page, 'Synced field, settings differ on this site.')).toHaveCSS('color', red);
    await expect(page.locator('main, #content').first()).toContainText('Updating it changes every form using it: Import Review A (importReviewA), Import Review B (importReviewB).');
    await page.locator('#formAction').selectOption('update');
    await importButton(page).click();

    await expect(page.locator('p.error')).toHaveText('Choose how to handle the “Changed label” synced field.');
    await expect(page.locator('#formAction')).toHaveValue('update');
    await expect(page.locator('#action-update')).toBeVisible();

    await page.locator('select[name^="syncedFields"]').selectOption('keep');
    await importButton(page).click();
    await expect(page.locator('#content h2', { hasText: 'Import Completed' })).toBeVisible();
    expect(field(await exportForm(page, 'Import Review A'), 'email').settings.label).toBe('Shared email');

    await upload(page, document);
    await page.locator('#formAction').selectOption('update');
    await page.locator('select[name^="syncedFields"]').selectOption('update');
    await importButton(page).click();
    await expect(page.locator('#content h2', { hasText: 'Import Completed' })).toBeVisible();
    expect(field(await exportForm(page, 'Import Review A'), 'email').settings.label).toBe('Changed label');
});

test('creates a missing synced field without asking', async ({ page }) => {
    await signIn(page);
    const document = await exportForm(page, 'Import Review A');
    const uid = randomUUID();
    document.title = 'Import Review New';
    document.handle = 'importReviewNew';
    field(document, 'email').syncedDefinitionUid = uid;
    await upload(page, document);

    await expect(line(page, 'Synced field, will be created.')).toBeVisible();
    await expect(line(page, 'Ready to import.')).toBeVisible();
    await expect(page.locator('main select')).toHaveCount(0);
    await importButton(page).click();
    await expect(page.locator('#content h2', { hasText: 'Import Completed' })).toBeVisible();

    document.title = 'Import Review Second';
    document.handle = 'importReviewSecond';
    await upload(page, document);
    await expect(line(page, 'Synced field, already on this site.')).toBeVisible();
});

test('blocks an import when a synced field has a different type', async ({ page }) => {
    await signIn(page);
    const document = await exportForm(page, 'Import Review A');
    field(document, 'email').type = 'verbb\\formie\\fields\\SingleLineText';
    await upload(page, document);

    await expect(line(page, 'Synced field, different field type on this site.')).toHaveCSS('color', red);
    await expect(line(page, 'has a different field type on this site. Change or detach it')).toBeVisible();
    expect(await line(page, 'Change or detach it').textContent()).toMatch(/^The /);
    await expect(importButton(page)).toBeDisabled();
});

test('links simple fields on update, and prevents updating parent fields that would lose answers', async ({ page }) => {
    await signIn(page);
    const source = await exportForm(page, 'Import Review A');
    const plain = await exportForm(page, 'Import Review Plain');
    const uid = field(source, 'email').syncedDefinitionUid;
    field(plain, 'email').syncedDefinitionUid = uid;
    field(plain, 'email').settings.label = field(source, 'email').settings.label;
    await upload(page, plain);

    await page.locator('#formAction').selectOption('update');
    await expect(page.locator('#action-update')).toContainText('Links these fields to synced fields and keeps their saved answers: Shared email.');
    await importButton(page).click();
    await expect(page.locator('#content h2', { hasText: 'Import Completed' })).toBeVisible();
    expect(field(await exportForm(page, 'Import Review Plain'), 'email').syncedDefinitionUid).toBe(uid);

    const parent = await exportForm(page, 'Import Review Plain');
    field(parent, 'contact').syncedDefinitionUid = randomUUID();
    await upload(page, parent);
    await expect(page.locator('#formAction option[value="update"]')).toBeDisabled();
    await expect(line(page, 'Create a new form instead.')).toHaveCSS('color', red);
});
