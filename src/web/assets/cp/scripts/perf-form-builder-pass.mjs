import { chromium } from 'playwright';

const USERNAME = process.env.FORMIE_PERF_USER || '';
const PASSWORD = process.env.FORMIE_PERF_PASS || '';
const SIMPLE_FORM_URL = process.env.FORMIE_PERF_SIMPLE_URL || 'https://formie-react.test/admin/formie/forms/edit/5368/fields/page1';
const COMPLEX_FORM_URL = process.env.FORMIE_PERF_COMPLEX_URL || 'https://formie-react.test/admin/formie/forms/edit/11';

const LOGIN_URL_FRAGMENT = '/admin/login';

const sleep = (ms) => {
    return new Promise((resolve) => { return setTimeout(resolve, ms); });
};

const maybeLogin = async (page) => {
    const currentUrl = page.url();
    const isOnLoginPage = currentUrl.includes(LOGIN_URL_FRAGMENT);
    const hasLoginField = await page.locator('#loginName, input[name=\"loginName\"], input[name=\"username\"], input[type=\"email\"]').first().isVisible().catch(() => { return false; });

    if (!isOnLoginPage && !hasLoginField) {
        return false;
    }

    if (!USERNAME || !PASSWORD) {
        throw new Error('Missing FORMIE_PERF_USER or FORMIE_PERF_PASS environment variable.');
    }

    const userField = page.locator('#loginName, input[name=\"loginName\"], input[name=\"username\"], input[type=\"email\"]').first();
    const passField = page.locator('#password, input[name=\"password\"], input[type=\"password\"]').first();
    const submitButton = page.locator('button[type=\"submit\"], input[type=\"submit\"]').first();

    await userField.fill(USERNAME);
    await passField.fill(PASSWORD);
    await Promise.all([
        page.waitForLoadState('networkidle', { timeout: 20000 }).catch(() => { }),
        submitButton.click(),
    ]);

    return true;
};

const exerciseBuilderPage = async (page, url) => {
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 });
    await maybeLogin(page);

    if (!page.url().includes('/admin/formie/forms/edit/')) {
        await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 });
    }

    await page.waitForLoadState('networkidle', { timeout: 20000 }).catch(() => { });
    await sleep(5000);

    // Trigger light interaction without mutating data.
    const clickableTargets = [
        '.fui-builder',
        '.fui-page-row',
        '[data-rbd-droppable-id]',
        '.formie-field-list',
    ];

    for (const selector of clickableTargets) {
        const locator = page.locator(selector).first();
        const isVisible = await locator.isVisible().catch(() => { return false; });

        if (isVisible) {
            await locator.click({ force: true }).catch(() => { });
            break;
        }
    }

    await sleep(2000);
};

const run = async () => {
    const browser = await chromium.launch({
        headless: true,
    });

    const context = await browser.newContext({
        ignoreHTTPSErrors: true,
    });
    const page = await context.newPage();
    const consoleErrors = [];

    page.on('console', (msg) => {
        if (msg.type() === 'error') {
            consoleErrors.push(msg.text());
        }
    });

    const results = [];
    const targets = [SIMPLE_FORM_URL, COMPLEX_FORM_URL];

    for (const targetUrl of targets) {
        const startedAt = Date.now();
        let ok = true;
        let error = null;

        try {
            await exerciseBuilderPage(page, targetUrl);
        } catch (err) {
            ok = false;
            error = err instanceof Error ? err.message : String(err);
        }

        results.push({
            url: targetUrl,
            ok,
            elapsedMs: Date.now() - startedAt,
            error,
        });
    }

    await context.close();
    await browser.close();

    const payload = {
        timestamp: new Date().toISOString(),
        results,
        consoleErrorCount: consoleErrors.length,
        consoleErrors: consoleErrors.slice(0, 20),
    };

    console.log(JSON.stringify(payload, null, 2));
};

run().catch((error) => {
    console.error(error);
    process.exit(1);
});
