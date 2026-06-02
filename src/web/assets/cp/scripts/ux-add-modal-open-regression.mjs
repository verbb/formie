import { chromium } from 'playwright';

const USERNAME = process.env.FORMIE_PERF_USER || '';
const PASSWORD = process.env.FORMIE_PERF_PASS || '';
const BUILDER_URL = process.env.FORMIE_UX_URL || 'https://formie-react.test/admin/formie/forms/edit/5369/fields/page1';
const FIELD_LABELS = (process.env.FORMIE_UX_FIELD_LABELS || 'Payment')
    .split(',')
    .map((value) => { return value.trim(); })
    .filter(Boolean);
const ATTEMPTS_PER_FIELD = Math.max(1, Number.parseInt(process.env.FORMIE_UX_ATTEMPTS || '3', 10));
const MODAL_OPEN_BUDGET_MS = Math.max(100, Number.parseInt(process.env.FORMIE_UX_MODAL_BUDGET_MS || '450', 10));

const LOGIN_URL_FRAGMENT = '/admin/login';

const escapeRegex = (value) => {
    return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
};

const maybeLogin = async (page) => {
    const currentUrl = page.url();
    const isOnLoginPage = currentUrl.includes(LOGIN_URL_FRAGMENT);
    const hasLoginField = await page.locator('#loginName, input[name="loginName"], input[name="username"], input[type="email"]').first().isVisible().catch(() => { return false; });

    if (!isOnLoginPage && !hasLoginField) {
        return false;
    }

    if (!USERNAME || !PASSWORD) {
        throw new Error('Missing FORMIE_PERF_USER or FORMIE_PERF_PASS environment variable.');
    }

    const userField = page.locator('#loginName, input[name="loginName"], input[name="username"], input[type="email"]').first();
    const passField = page.locator('#password, input[name="password"], input[type="password"]').first();
    const submitButton = page.locator('button[type="submit"], input[type="submit"]').first();

    await userField.fill(USERNAME);
    await passField.fill(PASSWORD);
    await Promise.all([
        page.waitForLoadState('networkidle', { timeout: 20000 }).catch(() => { }),
        submitButton.click(),
    ]);

    return true;
};

const ensureBuilderReady = async (page) => {
    await page.goto(BUILDER_URL, { waitUntil: 'domcontentloaded', timeout: 30000 });
    await maybeLogin(page);

    if (!page.url().includes('/admin/formie/forms/edit/')) {
        await page.goto(BUILDER_URL, { waitUntil: 'domcontentloaded', timeout: 30000 });
    }

    await page.waitForLoadState('networkidle', { timeout: 20000 }).catch(() => { });
    await page.getByText('Existing Fields').first().waitFor({ state: 'visible', timeout: 10000 });
};

const closeEditModal = async (page) => {
    const dialog = page.getByRole('dialog').last();
    const cancelButton = dialog.getByRole('button', { name: /^Cancel$/i }).first();
    const closeButtonVisible = await cancelButton.isVisible().catch(() => { return false; });

    if (closeButtonVisible) {
        await cancelButton.click();
    } else {
        await page.keyboard.press('Escape');
    }

    await dialog.waitFor({ state: 'hidden', timeout: 3000 }).catch(() => { });
};

const measureModalOpenForField = async (page, fieldLabel) => {
    const addButton = page.getByLabel(new RegExp(`^Add\\s+${escapeRegex(fieldLabel)}$`, 'i')).first();
    const buttonVisible = await addButton.isVisible().catch(() => { return false; });

    if (!buttonVisible) {
        return {
            ok: false,
            fieldLabel,
            openMs: null,
            error: `Could not find field pill for "${fieldLabel}".`,
        };
    }

    const openedAt = Date.now();
    await addButton.dblclick();

    const dialog = page.getByRole('dialog').last();
    let openMs = null;

    try {
        await dialog.waitFor({ state: 'visible', timeout: MODAL_OPEN_BUDGET_MS });
        openMs = Date.now() - openedAt;
    } catch (error) {
        return {
            ok: false,
            fieldLabel,
            openMs: Date.now() - openedAt,
            error: `Modal did not open within ${MODAL_OPEN_BUDGET_MS}ms.`,
        };
    }

    await closeEditModal(page);

    return {
        ok: openMs <= MODAL_OPEN_BUDGET_MS,
        fieldLabel,
        openMs,
        error: openMs <= MODAL_OPEN_BUDGET_MS ? null : `Modal opened in ${openMs}ms (budget ${MODAL_OPEN_BUDGET_MS}ms).`,
    };
};

const run = async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ ignoreHTTPSErrors: true });
    const page = await context.newPage();
    const consoleErrors = [];

    page.on('console', (msg) => {
        if (msg.type() === 'error') {
            consoleErrors.push(msg.text());
        }
    });

    await ensureBuilderReady(page);

    const attempts = [];

    for (const fieldLabel of FIELD_LABELS) {
        for (let i = 0; i < ATTEMPTS_PER_FIELD; i += 1) {
            const result = await measureModalOpenForField(page, fieldLabel);
            attempts.push({
                ...result,
                attempt: i + 1,
            });
        }
    }

    const failedAttempts = attempts.filter((attempt) => { return !attempt.ok; });
    const groupedStats = FIELD_LABELS.map((fieldLabel) => {
        const fieldAttempts = attempts.filter((attempt) => { return attempt.fieldLabel === fieldLabel && attempt.openMs != null; });
        const openTimes = fieldAttempts.map((attempt) => { return attempt.openMs; });
        const max = openTimes.length ? Math.max(...openTimes) : null;
        const min = openTimes.length ? Math.min(...openTimes) : null;
        const avg = openTimes.length ? Number((openTimes.reduce((sum, value) => { return sum + value; }, 0) / openTimes.length).toFixed(2)) : null;

        return {
            fieldLabel,
            attempts: fieldAttempts.length,
            minOpenMs: min,
            avgOpenMs: avg,
            maxOpenMs: max,
        };
    });

    await context.close();
    await browser.close();

    const payload = {
        timestamp: new Date().toISOString(),
        builderUrl: BUILDER_URL,
        modalOpenBudgetMs: MODAL_OPEN_BUDGET_MS,
        attemptsPerField: ATTEMPTS_PER_FIELD,
        labels: FIELD_LABELS,
        passed: failedAttempts.length === 0,
        failures: failedAttempts,
        stats: groupedStats,
        consoleErrorCount: consoleErrors.length,
        consoleErrors: consoleErrors.slice(0, 20),
    };

    console.log(JSON.stringify(payload, null, 2));

    if (failedAttempts.length > 0) {
        process.exitCode = 1;
    }
};

run().catch((error) => {
    console.error(error);
    process.exit(1);
});
