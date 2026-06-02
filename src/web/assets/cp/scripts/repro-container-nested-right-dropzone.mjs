import fs from 'node:fs/promises';
import path from 'node:path';
import { chromium } from 'playwright';

const TARGET_URL = process.env.FORMIE_DND_URL || 'https://formie-react.test/admin/formie/forms/edit/5368/fields/page1';
const USERNAME = process.env.FORMIE_DND_USER || '';
const PASSWORD = process.env.FORMIE_DND_PASS || '';
const ITERATIONS = Math.max(1, Number.parseInt(process.env.FORMIE_DND_ITERATIONS || '12', 10));
const OUTPUT_DIR = process.env.FORMIE_DND_OUTPUT_DIR || 'test-results/playwright-dnd/container-right-dropzone';
const HEADLESS = process.env.FORMIE_DND_HEADLESS !== '0';

const sleep = (ms) => new Promise((resolve) => { setTimeout(resolve, ms); });

const normalize = (value) => {
    return String(value || '').replace(/\s+/g, ' ').trim();
};

async function maybeLogin(page) {
    const emailField = page.locator('input[type="email"], input[name="loginName"], #loginName').first();
    const hasLogin = await emailField.isVisible().catch(() => false);
    if (!hasLogin) {
        return false;
    }

    if (!USERNAME || !PASSWORD) {
        throw new Error('Login page detected. Set FORMIE_DND_USER and FORMIE_DND_PASS.');
    }

    await emailField.fill(USERNAME);
    await page.locator('input[type="password"], input[name="password"], #password').first().fill(PASSWORD);
    await Promise.all([
        page.waitForLoadState('networkidle', { timeout: 20000 }).catch(() => { }),
        page.locator('button[type="submit"], input[type="submit"]').first().click(),
    ]);

    return true;
}

async function getContainerBlocks(page) {
    // Container preview wrapper used by group/repeater nested layouts.
    // Keep selector resilient to spacing/padding debug tweaks.
    return page.locator('div.mt-1.border.border-gray-100.bg-white.rounded-md');
}

async function readNestedOrder(container) {
    const cards = container.locator('[data-field-id]');
    const count = await cards.count();
    const labels = [];

    for (let i = 0; i < count; i += 1) {
        const text = normalize(await cards.nth(i).innerText());
        labels.push(text.split('\n')[0] || text);
    }

    return labels;
}

async function dragFirstToSecondRightDropzone(page, container) {
    const cards = container.locator('[data-field-id]');
    const count = await cards.count();
    if (count < 2) {
        throw new Error(`Need at least 2 nested fields, got ${count}`);
    }

    const source = cards.nth(0);
    const target = cards.nth(1);
    const sourceBox = await source.boundingBox();
    const targetBox = await target.boundingBox();

    if (!sourceBox || !targetBox) {
        throw new Error('Missing bounding box for source/target nested cards');
    }

    const startX = sourceBox.x + (sourceBox.width * 0.65);
    const startY = sourceBox.y + (sourceBox.height * 0.55);

    // Aim just outside the right edge of the second card where the side dropzone sits.
    const endX = targetBox.x + targetBox.width + 10;
    const endY = targetBox.y + (targetBox.height * 0.55);

    await page.mouse.move(startX, startY);
    await page.mouse.down();
    await page.mouse.move(endX, endY, { steps: 16 });
    await sleep(80);
    await page.mouse.up();
    await sleep(260);
}

async function main() {
    await fs.mkdir(OUTPUT_DIR, { recursive: true });

    const browser = await chromium.launch({ headless: HEADLESS });
    const context = await browser.newContext({ ignoreHTTPSErrors: true });
    const page = await context.newPage();

    const dndLogs = [];
    page.on('console', (msg) => {
        const text = msg.text();
        if (text.includes('[DND][')) {
            dndLogs.push(text);
        }
    });

    try {
        await page.goto(TARGET_URL, { waitUntil: 'domcontentloaded' });
        await maybeLogin(page);
        await page.goto(TARGET_URL, { waitUntil: 'networkidle' });

        const containers = await getContainerBlocks(page);
        const containerCount = await containers.count();
        if (containerCount === 0) {
            throw new Error('No container/group/repeater preview block found on page.');
        }

        const container = containers.first();
        const beforeInitial = await readNestedOrder(container);

        const runs = [];
        for (let i = 1; i <= ITERATIONS; i += 1) {
            const before = await readNestedOrder(container);
            await dragFirstToSecondRightDropzone(page, container);
            const after = await readNestedOrder(container);
            const changed = JSON.stringify(before) !== JSON.stringify(after);

            runs.push({
                iteration: i,
                before,
                after,
                changed,
            });

            await page.screenshot({
                path: path.join(OUTPUT_DIR, `iter-${String(i).padStart(2, '0')}.png`),
                fullPage: true,
            });
        }

        const summary = {
            targetUrl: TARGET_URL,
            beforeInitial,
            iterations: ITERATIONS,
            runs,
            changedCount: runs.filter((r) => r.changed).length,
            failedIterations: runs.filter((r) => !r.changed).map((r) => r.iteration),
        };

        await fs.writeFile(path.join(OUTPUT_DIR, 'results.json'), JSON.stringify(summary, null, 2));
        await fs.writeFile(path.join(OUTPUT_DIR, 'dnd-console.log'), dndLogs.join('\n'));

        console.log(`Saved repro artifacts to ${OUTPUT_DIR}`);
        console.log(`Failed iterations (no change): ${summary.failedIterations.join(', ') || 'none'}`);
    } finally {
        await context.close();
        await browser.close();
    }
}

main().catch((error) => {
    console.error(error);
    process.exit(1);
});
