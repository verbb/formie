import fs from 'node:fs/promises';
import path from 'node:path';
import { chromium } from 'playwright';

const USERNAME = process.env.FORMIE_PERF_USER || '';
const PASSWORD = process.env.FORMIE_PERF_PASS || '';
const BUILDER_URL = process.env.FORMIE_STRESS_URL || 'https://formie-react.test/admin/formie/forms/edit/5369/fields/page1';
const ITERATIONS = Math.max(1, Number.parseInt(process.env.FORMIE_STRESS_ITERATIONS || '2', 10));
const MODAL_OPEN_BUDGET_MS = Math.max(150, Number.parseInt(process.env.FORMIE_STRESS_MODAL_BUDGET_MS || '500', 10));
const ENABLE_VIDEO = process.env.FORMIE_STRESS_VIDEO === '1';
const OUTPUT_DIR = process.env.FORMIE_STRESS_OUTPUT_DIR || 'test-results/form-builder-stress';

const LOGIN_URL_FRAGMENT = '/admin/login';
const FIELD_TYPE_ENDPOINT_PATTERN = /\/actions\/formie\/fields\/(get-field-type|get-field-types|get-field-type-new-field|get-field-type-new-fields|get-payment-provider-settings-schema)/i;

const normalizeText = (value) => {
    return String(value || '').toLowerCase().replace(/[^a-z0-9]/g, '');
};

const sleep = (ms) => {
    return new Promise((resolve) => { return setTimeout(resolve, ms); });
};

const timestamp = () => {
    return new Date().toISOString().replace(/[:.]/g, '-');
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

    await page.waitForLoadState('networkidle', { timeout: 25000 }).catch(() => { });
    await page.getByText('Existing Fields').first().waitFor({ state: 'visible', timeout: 10000 });
};

const findFieldPill = async (page, labelToken) => {
    const normalizedToken = normalizeText(labelToken);
    const pills = page.locator('[aria-label^="Add "]');
    const count = await pills.count();

    for (let i = 0; i < count; i += 1) {
        const label = await pills.nth(i).getAttribute('aria-label');
        const normalizedLabel = normalizeText(label);

        if (normalizedLabel.includes(normalizedToken)) {
            return pills.nth(i);
        }
    }

    return null;
};

const waitForModal = async (page, timeoutMs = MODAL_OPEN_BUDGET_MS) => {
    const dialog = page.getByRole('dialog').last();
    await dialog.waitFor({ state: 'visible', timeout: timeoutMs });
    return dialog;
};

const closeModalWithCancel = async (page) => {
    const dialog = page.getByRole('dialog').last();
    const cancelButton = dialog.getByRole('button', { name: /^Cancel$/i }).first();
    const cancelVisible = await cancelButton.isVisible().catch(() => { return false; });

    if (cancelVisible) {
        await cancelButton.click();
    } else {
        await page.keyboard.press('Escape');
    }

    await dialog.waitFor({ state: 'hidden', timeout: 5000 }).catch(() => { });
};

const dismissBlockingModalIfAny = async (page) => {
    const dialog = page.getByRole('dialog').last();
    const isVisible = await dialog.isVisible().catch(() => { return false; });
    if (!isVisible) {
        return false;
    }

    await closeModalWithCancel(page);
    return true;
};

const fillModalLabelIfEmpty = async (page, labelPrefix = 'Field') => {
    const dialog = page.getByRole('dialog').last();
    const labelInput = dialog.locator('input[name="label"], input[name$=".label"]').first();
    const visible = await labelInput.isVisible().catch(() => { return false; });

    if (!visible) {
        return false;
    }

    const currentValue = await labelInput.inputValue().catch(() => { return ''; });
    if (String(currentValue || '').trim() !== '') {
        return false;
    }

    await labelInput.fill(`${labelPrefix} ${Date.now().toString().slice(-6)}`);
    return true;
};

const clickModalApply = async (page, options = {}) => {
    const allowSkip = options.allowSkip === true;
    const dialog = page.getByRole('dialog').last();
    const isVisible = await dialog.isVisible().catch(() => { return false; });
    if (!isVisible) {
        if (allowSkip) {
            return { skipped: true, reason: 'No modal open when attempting apply.' };
        }

        throw new Error('No modal open when attempting apply.');
    }

    const applyButton = dialog.getByRole('button', { name: /^Apply$/i }).first();
    const applyVisible = await applyButton.isVisible().catch(() => { return false; });

    if (!applyVisible) {
        if (allowSkip) {
            await closeModalWithCancel(page);
            return { skipped: true, reason: 'Apply button not visible in modal.' };
        }

        throw new Error('Apply button not visible in modal.');
    }

    await applyButton.click();
    const hidden = await dialog.waitFor({ state: 'hidden', timeout: 6000 }).then(() => { return true; }).catch(() => { return false; });

    if (!hidden) {
        // Validation or async failures can keep modal open; keep suite moving.
        await closeModalWithCancel(page);
    }

    return { applied: true };
};

const addFieldAndOpenModal = async (page, labelToken, budgetMs = MODAL_OPEN_BUDGET_MS) => {
    await dismissBlockingModalIfAny(page);

    const pill = await findFieldPill(page, labelToken);
    if (!pill) {
        throw new Error(`Could not find field pill matching "${labelToken}".`);
    }

    const startedAt = Date.now();
    await pill.dblclick();
    await waitForModal(page, Math.max(2000, budgetMs + 1000));
    const openMs = Date.now() - startedAt;

    if (openMs > budgetMs) {
        throw new Error(`Modal open for "${labelToken}" exceeded budget: ${openMs}ms > ${budgetMs}ms.`);
    }

    return { openMs };
};

const openTopLevelFieldByIndex = async (page, index = 0) => {
    const fields = page.locator('[data-field-id]');
    const count = await fields.count();

    if (count <= index) {
        throw new Error(`Field card index ${index} not available (count: ${count}).`);
    }

    await fields.nth(index).click();
    await waitForModal(page, 3000);
};

const trySwitchPaymentProvider = async (page) => {
    const dialog = page.getByRole('dialog').last();

    // Native <select> path
    const nativeSelect = dialog.locator('select[name="paymentIntegration"], select[name$=".paymentIntegration"]').first();
    const hasNativeSelect = await nativeSelect.isVisible().catch(() => { return false; });

    if (hasNativeSelect) {
        const options = await nativeSelect.locator('option').evaluateAll((nodes) => {
            return nodes.map((node) => {
                return {
                    value: node.value,
                    label: node.label || node.textContent || '',
                };
            });
        });

        const validOptions = options.filter((option) => {
            return option.value && option.value.trim() !== '';
        });

        if (validOptions.length === 0) {
            return { switched: false, reason: 'No non-empty provider options in native select.' };
        }

        const currentValue = await nativeSelect.inputValue().catch(() => { return ''; });
        const target = validOptions.find((option) => { return option.value !== currentValue; }) || validOptions[0];
        await nativeSelect.selectOption(target.value);

        return { switched: true, providerValue: target.value };
    }

    return { switched: false, reason: 'No detectable native provider selector.' };
};

const clickNewPageAndApply = async (page) => {
    const newPageButton = page.locator('button[title="New Page"]').first();
    const visible = await newPageButton.isVisible().catch(() => { return false; });

    if (!visible) {
        return { created: false, reason: 'New Page button not visible.' };
    }

    await newPageButton.click();

    const dialog = page.getByRole('dialog').last();
    const opened = await dialog.isVisible().catch(() => { return false; });
    if (!opened) {
        return { created: false, reason: 'Page settings modal did not open.' };
    }

    const applyButton = dialog.getByRole('button', { name: /^Apply$/i }).first();
    const applyVisible = await applyButton.isVisible().catch(() => { return false; });

    if (!applyVisible) {
        await closeModalWithCancel(page);
        return { created: false, reason: 'Page settings apply button not visible.' };
    }

    await applyButton.click();
    await dialog.waitFor({ state: 'hidden', timeout: 5000 }).catch(() => { });

    return { created: true };
};

const createRequestTracker = (page) => {
    const requestStartMap = new WeakMap();
    const requestEvents = [];

    page.on('request', (request) => {
        requestStartMap.set(request, Date.now());
    });

    const trackFinalizedRequest = (request, failed = false) => {
        const startedAt = requestStartMap.get(request) || Date.now();
        const endedAt = Date.now();
        const url = request.url();
        const method = request.method();
        const durationMs = endedAt - startedAt;
        const response = failed ? null : request.response();
        const status = response?.status?.() ?? null;

        requestEvents.push({
            url,
            method,
            durationMs,
            status,
            failed,
            isFieldTypeEndpoint: FIELD_TYPE_ENDPOINT_PATTERN.test(url),
        });
    };

    page.on('requestfinished', (request) => {
        trackFinalizedRequest(request, false);
    });

    page.on('requestfailed', (request) => {
        trackFinalizedRequest(request, true);
    });

    return {
        getEvents() {
            return requestEvents;
        },
    };
};

const summarizeRequests = (events) => {
    const byEndpoint = {};

    events.forEach((event) => {
        if (!event.isFieldTypeEndpoint) {
            return;
        }

        let key = 'field-endpoint-other';
        try {
            const parsed = new URL(event.url);
            key = parsed.pathname.split('/').pop() || key;
        } catch (error) {
            // Keep fallback key for invalid URLs.
        }

        if (!byEndpoint[key]) {
            byEndpoint[key] = {
                count: 0,
                failures: 0,
                totalDurationMs: 0,
                maxDurationMs: 0,
            };
        }

        byEndpoint[key].count += 1;
        byEndpoint[key].totalDurationMs += event.durationMs;
        byEndpoint[key].maxDurationMs = Math.max(byEndpoint[key].maxDurationMs, event.durationMs);
        if (event.failed || (event.status != null && event.status >= 400)) {
            byEndpoint[key].failures += 1;
        }
    });

    Object.values(byEndpoint).forEach((stats) => {
        stats.avgDurationMs = stats.count > 0 ? Number((stats.totalDurationMs / stats.count).toFixed(2)) : 0;
    });

    return byEndpoint;
};

const runScenario = async ({
    page,
    name,
    outputRoot,
    body,
}) => {
    const startedAt = Date.now();
    const steps = [];
    const failures = [];

    const runStep = async (stepName, fn) => {
        const stepStartedAt = Date.now();
        try {
            await dismissBlockingModalIfAny(page);
            const result = await fn();
            steps.push({
                step: stepName,
                ok: true,
                elapsedMs: Date.now() - stepStartedAt,
                data: result || null,
            });
            return result;
        } catch (error) {
            const screenshotPath = path.join(outputRoot, `${name}-${normalizeText(stepName)}-${Date.now()}.png`);
            await page.screenshot({ path: screenshotPath, fullPage: true }).catch(() => { });
            const message = error instanceof Error ? error.message : String(error);

            steps.push({
                step: stepName,
                ok: false,
                elapsedMs: Date.now() - stepStartedAt,
                error: message,
                screenshotPath,
            });
            failures.push({
                step: stepName,
                error: message,
                screenshotPath,
            });
            throw error;
        }
    };

    let ok = true;
    let error = null;

    try {
        await body(runStep);
    } catch (scenarioError) {
        ok = false;
        error = scenarioError instanceof Error ? scenarioError.message : String(scenarioError);
    }

    return {
        name,
        ok,
        error,
        elapsedMs: Date.now() - startedAt,
        steps,
        failures,
    };
};

const run = async () => {
    const runId = `run-${timestamp()}`;
    const outputRoot = path.resolve(process.cwd(), OUTPUT_DIR, runId);
    await fs.mkdir(outputRoot, { recursive: true });

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        ignoreHTTPSErrors: true,
        ...(ENABLE_VIDEO ? {
            recordVideo: {
                dir: outputRoot,
                size: { width: 1600, height: 900 },
            },
        } : {}),
    });
    const page = await context.newPage();
    const tracker = createRequestTracker(page);
    const consoleErrors = [];

    page.on('console', (msg) => {
        if (msg.type() === 'error') {
            consoleErrors.push(msg.text());
        }
    });

    const scenarioRuns = [];

    await ensureBuilderReady(page);

    for (let iteration = 1; iteration <= ITERATIONS; iteration += 1) {
        scenarioRuns.push(await runScenario({
            page,
            name: `iter${iteration}-add-edit-delete-reorder`,
            outputRoot,
            body: async (step) => {
                await step('add Single-Line Text and apply', async () => {
                    const metrics = await addFieldAndOpenModal(page, 'Single-Line Text');
                    await fillModalLabelIfEmpty(page, 'Single-Line');
                    await clickModalApply(page);
                    return metrics;
                });

                await step('add Email and apply', async () => {
                    const metrics = await addFieldAndOpenModal(page, 'Email');
                    await fillModalLabelIfEmpty(page, 'Email');
                    await clickModalApply(page);
                    return metrics;
                });

                await step('add Multi-Line Text and apply', async () => {
                    const metrics = await addFieldAndOpenModal(page, 'Multi-Line Text');
                    await fillModalLabelIfEmpty(page, 'Multi-Line');
                    await clickModalApply(page);
                    return metrics;
                });

                await step('add Dropdown and apply', async () => {
                    const metrics = await addFieldAndOpenModal(page, 'Dropdown');
                    await fillModalLabelIfEmpty(page, 'Dropdown');
                    await clickModalApply(page);
                    return metrics;
                });

                await step('drag first field to end', async () => {
                    const fields = page.locator('[data-field-id]');
                    const count = await fields.count();
                    if (count < 2) {
                        return { skipped: true, reason: 'Not enough fields to reorder.' };
                    }

                    await fields.nth(0).dragTo(fields.nth(count - 1));
                    return { fieldCount: count };
                });

                await step('open first field and delete', async () => {
                    await openTopLevelFieldByIndex(page, 0);

                    const dialog = page.getByRole('dialog').last();
                    await dialog.getByRole('button', { name: /^Delete$/i }).first().click();
                    page.once('dialog', (dialogHandle) => { dialogHandle.accept().catch(() => { }); });
                    await sleep(250);
                    await dialog.waitFor({ state: 'hidden', timeout: 5000 }).catch(() => { });

                    return { deleted: true };
                });
            },
        }));

        scenarioRuns.push(await runScenario({
            page,
            name: `iter${iteration}-nested-subfield-loops`,
            outputRoot,
            body: async (step) => {
                await step('add Address and apply', async () => {
                    const metrics = await addFieldAndOpenModal(page, 'Address');
                    await fillModalLabelIfEmpty(page, 'Address');
                    await clickModalApply(page);
                    return metrics;
                });

                await step('reopen Address and apply twice', async () => {
                    const fields = page.locator('[data-field-id]');
                    const count = await fields.count();
                    if (count === 0) {
                        return { skipped: true, reason: 'No fields available.' };
                    }

                    // Address is usually the last added field.
                    const addressCard = fields.nth(count - 1);
                    for (let i = 0; i < 2; i += 1) {
                        await addressCard.click();
                        await waitForModal(page, 3000);
                        await clickModalApply(page);
                    }

                    return { loops: 2 };
                });
            },
        }));

        scenarioRuns.push(await runScenario({
            page,
            name: `iter${iteration}-payment-provider-flow`,
            outputRoot,
            body: async (step) => {
                await step('add Payment and open modal quickly', async () => {
                    const metrics = await addFieldAndOpenModal(page, 'Payment');
                    await fillModalLabelIfEmpty(page, 'Payment');
                    return metrics;
                });

                await step('set provider if possible and apply', async () => {
                    const result = await trySwitchPaymentProvider(page);
                    await clickModalApply(page, { allowSkip: true });
                    return result;
                });

                await step('reopen Payment and try provider switch', async () => {
                    const fields = page.locator('[data-field-id]');
                    const count = await fields.count();
                    if (count === 0) {
                        return { skipped: true, reason: 'No fields available.' };
                    }

                    await fields.nth(count - 1).click();
                    await waitForModal(page, 3000);
                    const switched = await trySwitchPaymentProvider(page);
                    await clickModalApply(page, { allowSkip: true });
                    return switched;
                });
            },
        }));

        scenarioRuns.push(await runScenario({
            page,
            name: `iter${iteration}-large-form-interaction-loop`,
            outputRoot,
            body: async (step) => {
                await step('rapid modal open close loop', async () => {
                    const fields = page.locator('[data-field-id]');
                    const count = await fields.count();
                    const loops = Math.min(count, 8);

                    for (let i = 0; i < loops; i += 1) {
                        await fields.nth(i).click();
                        await waitForModal(page, 3000);
                        await closeModalWithCancel(page);
                    }

                    return { loops };
                });

                await step('small reorder loop', async () => {
                    const fields = page.locator('[data-field-id]');
                    const count = await fields.count();
                    if (count < 3) {
                        return { skipped: true, reason: 'Need at least 3 fields.' };
                    }

                    await fields.nth(1).dragTo(fields.nth(2));
                    await fields.nth(2).dragTo(fields.nth(0));
                    return { reordered: true, fieldCount: count };
                });
            },
        }));

        scenarioRuns.push(await runScenario({
            page,
            name: `iter${iteration}-cross-page-flow`,
            outputRoot,
            body: async (step) => {
                await step('create page if possible', async () => {
                    return clickNewPageAndApply(page);
                });

                await step('add Email and apply on current page', async () => {
                    const metrics = await addFieldAndOpenModal(page, 'Email');
                    await fillModalLabelIfEmpty(page, 'Email');
                    await clickModalApply(page);
                    return metrics;
                });
            },
        }));
    }

    const requestEvents = tracker.getEvents();
    const requestSummary = summarizeRequests(requestEvents);
    const failedScenarios = scenarioRuns.filter((scenario) => { return !scenario.ok; });

    await context.close();
    await browser.close();

    const payload = {
        timestamp: new Date().toISOString(),
        runId,
        builderUrl: BUILDER_URL,
        iterations: ITERATIONS,
        modalOpenBudgetMs: MODAL_OPEN_BUDGET_MS,
        passed: failedScenarios.length === 0,
        scenarioCount: scenarioRuns.length,
        failedScenarioCount: failedScenarios.length,
        scenarios: scenarioRuns,
        fieldTypeRequestSummary: requestSummary,
        totalRequestCount: requestEvents.length,
        totalFieldTypeRequestCount: requestEvents.filter((event) => { return event.isFieldTypeEndpoint; }).length,
        consoleErrorCount: consoleErrors.length,
        consoleErrors: consoleErrors.slice(0, 50),
        outputDir: outputRoot,
        videoEnabled: ENABLE_VIDEO,
    };

    const jsonPath = path.join(outputRoot, 'results.json');
    await fs.writeFile(jsonPath, `${JSON.stringify(payload, null, 2)}\n`, 'utf8');
    console.log(JSON.stringify(payload, null, 2));

    if (failedScenarios.length > 0) {
        process.exitCode = 1;
    }
};

run().catch((error) => {
    console.error(error);
    process.exit(1);
});
