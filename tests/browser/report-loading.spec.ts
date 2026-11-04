import { test, expect, type Page } from '@playwright/test';

const signIn = async (page: Page) => {
    await page.goto('/admin/login');
    await page.getByRole('textbox', { name: 'Username or Email', exact: true }).fill('browserReportManager');
    await page.locator('input[name="password"]').fill('testing-only-password');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await page.waitForURL(url => !url.pathname.endsWith('/login'));
};

const routePath = (url: URL) => url.searchParams.get('p') || url.pathname;
const isFormOptions = (url: URL) => routePath(url).includes('formie/reports/form-options');

test('shows an editable report dialog before forms and existing report data finish loading', async ({ page }) => {
    await signIn(page);
    await page.goto('/admin/formie/reports/create');
    await expect(page.getByRole('dialog', { name: 'New report' })).toBeVisible();
    await page.locator('#formie-create-report-form').getByRole('textbox').first().fill(`Deferred data ${Date.now()}`);
    await expect(page.getByRole('button', { name: 'Create report', exact: true })).toBeEnabled();
    await page.getByRole('button', { name: 'Create report', exact: true }).click();
    await page.waitForURL(url => routePath(url).includes('/reports/') && !routePath(url).endsWith('/create'));

    let release: () => void = () => {};
    const pending = new Promise<void>(resolve => { release = resolve; });
    let requested = false;
    let reportRequested = false;
    await page.route(isFormOptions, async route => {
        requested = true;
        await pending;
        await route.continue();
    });
    await page.route(url => routePath(url).includes('/reports/view-config'), async route => {
        reportRequested = true;
        await pending;
        await route.continue();
    });

    try {
        await page.goto('/admin/formie/reports/create');
        await expect(page.getByRole('dialog', { name: 'New report' })).toBeVisible();
        await expect(page.getByRole('status', { name: 'Loading forms…', exact: true })).toBeVisible();
        await expect(page.getByRole('combobox', { name: 'Forms', exact: true })).toBeVisible();
        await expect.poll(() => requested).toBe(true);
        await expect.poll(() => reportRequested).toBe(true);
        await page.keyboard.type('Typed while loading');
        await expect(page.locator('#formie-create-report-form').getByRole('textbox').first()).toHaveValue('Typed while loading');
        await expect(page.getByRole('button', { name: 'Create report', exact: true })).toBeDisabled();

        release();
        await expect(page.getByRole('button', { name: 'Create report', exact: true })).toBeEnabled();
        await expect(page.locator('#formie-create-report-form').getByRole('textbox').first()).toHaveValue('Typed while loading');
        await page.getByRole('button', { name: 'Cancel', exact: true }).click();
        await expect(page.getByRole('button', { name: 'View', exact: true })).toBeVisible();
    } finally {
        release();
    }
});

test('can reopen immediately after dismissing during the opening animation', async ({ page }) => {
    await signIn(page);
    await page.goto('/admin/formie/reports/create');
    const dialog = page.getByRole('dialog', { name: 'New report' });
    await expect(page.getByRole('button', { name: 'Create report', exact: true })).toBeEnabled();
    const closeBox = await page.getByRole('button', { name: 'Close', exact: true }).boundingBox();
    expect(closeBox).not.toBeNull();
    await page.getByRole('button', { name: 'Cancel', exact: true }).click();
    await expect(dialog).toBeHidden();
    const openBox = await page.getByRole('button', { name: 'New report', exact: true }).boundingBox();
    expect(openBox).not.toBeNull();

    for (let cycle = 0; cycle < 4; cycle++) {
        // Pointer clicks do not wait for animations to settle, unlike locator.click().
        await page.mouse.click(openBox!.x + openBox!.width / 2, openBox!.y + openBox!.height / 2);
        await expect(dialog).toBeVisible();
        await page.mouse.click(closeBox!.x + closeBox!.width / 2, closeBox!.y + closeBox!.height / 2);
        await expect(dialog).toBeHidden();
        await page.mouse.click(openBox!.x + openBox!.width / 2, openBox!.y + openBox!.height / 2);
        await expect(dialog).toBeVisible();
        await page.locator('#formie-create-report-form').getByRole('textbox').first().fill(`Reopened ${cycle}`);
        await expect(page.locator('#formie-create-report-form').getByRole('textbox').first()).toHaveValue(`Reopened ${cycle}`);
        await page.getByRole('button', { name: 'Cancel', exact: true }).click();
        await expect(dialog).toBeHidden();
    }
});

test('keeps one report placeholder until both configuration and rows are ready', async ({ page }) => {
    await signIn(page);
    let releaseConfig: () => void = () => {};
    let releaseRows: () => void = () => {};
    const configPending = new Promise<void>(resolve => { releaseConfig = resolve; });
    const rowsPending = new Promise<void>(resolve => { releaseRows = resolve; });
    let requestedRows = false;
    await page.route(url => routePath(url).includes('/reports/view-config'), async route => {
        await configPending;
        await route.continue();
    });
    await page.route(url => routePath(url).includes('/reports/table-data/'), async route => {
        requestedRows = true;
        await rowsPending;
        await route.continue();
    });

    try {
        await page.goto('/admin/formie/reports');
        const loading = page.getByRole('status', { name: 'Loading report…', exact: true });
        await expect(loading).toBeVisible();
        await expect(page.getByRole('button', { name: 'New report', exact: true })).toBeVisible();
        releaseConfig();
        await expect.poll(() => requestedRows).toBe(true);
        await expect(loading).toBeVisible();
        releaseRows();
        await expect(loading).toBeHidden();
        await expect(page.getByRole('button', { name: 'View', exact: true })).toBeVisible();
    } finally {
        releaseConfig();
        releaseRows();
    }
});

test('retries failed form discovery without losing the report name and reuses loaded options', async ({ page }) => {
    await signIn(page);
    let requests = 0;
    await page.route(isFormOptions, async route => {
        requests += 1;
        if (requests === 1) {
            await route.fulfill({ status: 503, contentType: 'application/json', body: '{}' });
        } else {
            await route.continue();
        }
    });

    await page.goto('/admin/formie/reports/create');
    await expect(page.getByRole('dialog', { name: 'New report' })).toBeVisible();
    await page.locator('#formie-create-report-form').getByRole('textbox').first().fill('Keep this name');
    await expect(page.getByText('Unable to load forms.', { exact: true })).toBeVisible();
    await page.getByRole('button', { name: 'Try Again', exact: true }).click();
    await expect(page.getByRole('button', { name: 'Create report', exact: true })).toBeEnabled();
    await expect(page.locator('#formie-create-report-form').getByRole('textbox').first()).toHaveValue('Keep this name');
    await page.getByRole('button', { name: 'Cancel', exact: true }).click();
    await page.getByRole('button', { name: 'New report', exact: true }).click();
    await expect(page.getByRole('button', { name: 'Create report', exact: true })).toBeEnabled();
    await expect(page.locator('#formie-create-report-form').getByRole('textbox').first()).toHaveValue('');
    expect(requests).toBe(2);
});

test('discards a dismissed form request and can reopen the dialog', async ({ page }) => {
    await signIn(page);
    let release: () => void = () => {};
    const pending = new Promise<void>(resolve => { release = resolve; });
    let requests = 0;
    await page.route(isFormOptions, async route => {
        requests += 1;
        if (requests === 1) {
            await pending;
            await route.fulfill({ contentType: 'application/json', body: JSON.stringify({ formOptions: [] }) });
        } else {
            await route.continue();
        }
    });

    try {
        await page.goto('/admin/formie/reports/create');
        await expect.poll(() => requests).toBe(1);
        await page.getByRole('button', { name: 'Cancel', exact: true }).click();
        await page.getByRole('button', { name: 'New report', exact: true }).click();
        await expect(page.getByRole('button', { name: 'Create report', exact: true })).toBeEnabled();
        release();
        await expect.poll(() => requests).toBe(2);
        await expect(page.getByText('No forms available.', { exact: true })).toHaveCount(0);
    } finally {
        release();
    }
});
