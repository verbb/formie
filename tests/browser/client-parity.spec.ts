import { test, expect, type Locator, type Page } from '@playwright/test';

// Client-rendered forms must behave like server-rendered ones. Each field is filled the way a visitor would fill the
// server-rendered field, using its enhanced control where it has one, then the saved submissions are compared.
type Surface = 'server' | 'vue' | 'react' | 'web-components';
type Saved = { isSpam: boolean; isIncomplete: boolean; values: Record<string, unknown> };
type Step = {
    handle: string;
    fill?: (field: Locator, page: Page) => Promise<unknown>;
    // Turns a saved value into what must match across surfaces, for values that differ by design, such as asset IDs.
    normalize?: (value: unknown) => unknown;
};

const FORM = 'parityFields';
const upload = { name: 'parity.txt', mimeType: 'text/plain', buffer: Buffer.from('parity upload') };

function surfaceUrl(surface: Surface, form: string): string {
    return surface === 'server'
        ? `/browser-fixture?adapter=vue&product=server&form=${form}`
        : `/browser-fixture?adapter=${surface}&form=${form}`;
}

function fieldLocator(page: Page, handle: string): Locator {
    return page.locator(`#host [data-formie-field-handle="${handle}"]`).first();
}

async function pickOption(field: Locator, label: string) {
    await field.getByRole('combobox').first().selectOption({ label });
}

// Stored file names get a suffix that depends on the upload path, so compare the name the visitor uploaded.
const fileNames = (value: unknown) => (Array.isArray(value) ? value : []).map((item) => {
    const name = String(item && typeof item === 'object' ? (item as Record<string, unknown>).filename ?? (item as Record<string, unknown>).title : item);
    return name.replace(/_[^.]*(\.[a-z0-9]+)$/i, '$1');
});
// Browsers submit textarea line breaks as CRLF in form posts and keep LF in JSON.
const lineBreaks = (value: unknown) => (typeof value === 'string' ? value.replace(/\r\n/g, '\n') : value);
const present = (value: unknown) => value !== null && value !== undefined && value !== '' && !(Array.isArray(value) && value.length === 0);

const STEPS: Step[] = [
    { handle: 'pSingleLine', fill: (f) => f.getByRole('textbox').first().fill('Ada Lovelace') },
    { handle: 'pMultiLine', fill: (f) => f.locator('textarea').first().fill('Line one\nLine two'), normalize: lineBreaks },
    {
        handle: 'pRichText',
        fill: async (f) => {
            const editor = f.locator('[contenteditable="true"]').first();
            await editor.click();
            await editor.pressSequentially('Rich ');
            await f.page().keyboard.press('ControlOrMeta+b');
            await editor.pressSequentially('bold');
        },
    },
    { handle: 'pEmail', fill: (f) => f.getByRole('textbox').first().fill('ada@example.test') },
    { handle: 'pNumber', fill: (f) => f.getByRole('spinbutton').first().fill('42') },
    {
        handle: 'pPhoneCountry',
        fill: async (f, page) => {
            await f.locator('.iti__selected-country').click();
            await page.locator('.iti__country[data-country-code="nz"]').first().click();
            await f.locator('input[type="tel"]').fill('211234567');
        },
    },
    { handle: 'pPhonePlain', fill: (f) => f.locator('input[type="tel"]').fill('0400111222') },
    { handle: 'pPassword', fill: (f) => f.locator('input[type="password"]').fill('correct-horse-1'), normalize: present },
    { handle: 'pHidden' },
    { handle: 'pDropdown', fill: (f) => pickOption(f, 'Beta') },
    { handle: 'pDropdownMulti', fill: (f) => f.getByRole('listbox').first().selectOption([{ label: 'Alpha' }, { label: 'Gamma' }]) },
    {
        handle: 'pDropdownSearch',
        fill: async (f, page) => {
            await f.locator('.ts-control').first().click();
            await page.keyboard.type('Gam');
            await page.locator('.ts-dropdown .option', { hasText: 'Gamma' }).first().click();
        },
    },
    {
        handle: 'pDropdownSearchMulti',
        fill: async (f, page) => {
            await f.locator('.ts-control').first().click();
            await page.locator('.ts-dropdown:visible .option', { hasText: 'Alpha' }).first().click();
            await page.locator('.ts-dropdown:visible .option', { hasText: 'Gamma' }).first().click();
            await page.keyboard.press('Escape');
        },
    },
    {
        handle: 'pEntriesSearch',
        fill: async (f, page) => {
            await f.locator('.ts-control').first().click();
            await page.locator('.ts-dropdown:visible .option').first().click();
        },
    },
    { handle: 'pRadio', fill: (f) => f.getByRole('radio', { name: 'Beta' }).check() },
    {
        handle: 'pCheckboxes',
        fill: async (f) => {
            await f.getByRole('checkbox', { name: 'Alpha' }).check();
            await f.getByRole('checkbox', { name: 'Gamma' }).check();
        },
    },
    { handle: 'pCheckboxesToggle', fill: (f) => f.getByRole('checkbox', { name: 'Select all' }).check() },
    { handle: 'pAgree', fill: (f) => f.getByRole('checkbox').first().check() },
    {
        handle: 'pDateCalendar',
        fill: async (f) => {
            await f.locator('input[type="date"]').fill('2026-03-04');
            await f.locator('input[type="time"]').fill('09:30');
        },
    },
    {
        handle: 'pDatePicker',
        fill: async (f, page) => {
            await f.locator('input.flatpickr-input, input[data-formie-date-picker]').first().click();
            await page.locator('.flatpickr-calendar.open .flatpickr-day:not(.prevMonthDay):not(.nextMonthDay)', { hasText: /^15$/ }).first().click();
        },
    },
    {
        handle: 'pDateDropdowns',
        fill: async (f) => {
            const selects = f.getByRole('combobox');
            await selects.nth(0).selectOption('2001');
            await selects.nth(1).selectOption('2');
            await selects.nth(2).selectOption('3');
        },
    },
    {
        handle: 'pDateInputs',
        fill: async (f) => {
            const inputs = f.getByRole('spinbutton');
            await inputs.nth(0).fill('1999');
            await inputs.nth(1).fill('12');
            await inputs.nth(2).fill('31');
        },
    },
    { handle: 'pNameSingle', fill: (f) => f.getByRole('textbox').first().fill('Grace Hopper') },
    {
        handle: 'pNameMulti',
        fill: async (f) => {
            await f.getByRole('textbox', { name: 'First Name' }).fill('Grace');
            await f.getByRole('textbox', { name: 'Last Name' }).fill('Hopper');
        },
    },
    {
        handle: 'pAddress',
        fill: async (f) => {
            await f.getByRole('textbox', { name: 'Address 1' }).fill('1 Example St');
            await f.getByRole('textbox', { name: 'City' }).fill('Melbourne');
            await f.getByRole('textbox', { name: /ZIP/ }).fill('3000');
            await f.getByRole('combobox', { name: 'Country' }).selectOption('AU');
        },
    },
    { handle: 'pGroup', fill: (f) => f.getByRole('textbox').first().fill('In a group') },
    { handle: 'pRepeater', fill: (f) => f.getByRole('textbox').first().fill('In a row') },
    { handle: 'pTable', fill: (f) => f.getByRole('checkbox').first().check() },
    {
        handle: 'pSignature',
        fill: async (f, page) => {
            const canvas = f.locator('canvas').first();
            await canvas.evaluate((element) => element.scrollIntoView({ block: 'center' }));
            const box = await canvas.boundingBox();
            if (!box) throw new Error('No signature canvas.');
            // Stay inside the visible part of the pad, whatever its size.
            const top = Math.max(box.y, 0) + 20;
            await page.mouse.move(box.x + 20, top);
            await page.mouse.down();
            await page.mouse.move(box.x + Math.min(box.width, 300) - 20, top + 60, { steps: 8 });
            await page.mouse.up();
        },
        normalize: present,
    },
    { handle: 'pFileInput', fill: (f) => f.locator('input[type="file"]').first().setInputFiles(upload), normalize: fileNames },
    { handle: 'pFileManager', fill: (f) => f.locator('input[type="file"]').first().setInputFiles(upload), normalize: fileNames },
    { handle: 'pCalcSource', fill: (f) => f.getByRole('spinbutton').first().fill('4') },
    { handle: 'pCalculations' },
    { handle: 'pRecipientsDropdown', fill: (f) => pickOption(f, 'Support') },
    { handle: 'pRecipientsRadio', fill: (f) => f.getByRole('radio', { name: 'Support' }).check() },
    {
        handle: 'pRecipientsCheckboxes',
        fill: async (f) => {
            await f.getByRole('checkbox', { name: 'Sales' }).check();
            await f.getByRole('checkbox', { name: 'Support' }).check();
        },
    },
    { handle: 'pRecipientsHidden' },
    { handle: 'pEntries', fill: (f) => f.getByRole('combobox').first().selectOption({ index: 1 }) },
    { handle: 'pEntriesCheckboxes', fill: (f) => f.getByRole('checkbox').first().check() },
    { handle: 'pCategories', fill: (f) => f.getByRole('combobox').first().selectOption({ index: 1 }) },
    // Server-rendered Tags fields in the default display render no control a visitor can use yet, so they stay empty.
    { handle: 'pTags' },
    { handle: 'pUsers', fill: (f) => f.getByRole('combobox').first().selectOption({ index: 1 }) },
    // The radios are visually hidden behind their styled options, as in server-rendered forms.
    { handle: 'pSurveyLikert', fill: (f) => f.getByRole('radio', { name: 'Beta' }).check({ force: true }) },
    {
        handle: 'pSurveyLikertRows',
        fill: async (f) => {
            await f.getByRole('radio', { name: 'Beta' }).nth(0).check({ force: true });
            await f.getByRole('radio', { name: 'Gamma' }).nth(1).check({ force: true });
        },
    },
    {
        handle: 'pSurveyRank',
        // Drag Gamma above Alpha.
        fill: async (f, page) => {
            const gamma = f.locator('li', { hasText: 'Gamma' }).first();
            await gamma.scrollIntoViewIfNeeded();
            const from = await gamma.boundingBox();
            const to = await f.locator('li', { hasText: 'Alpha' }).first().boundingBox();
            if (!from || !to) throw new Error('No rank items.');
            await page.mouse.move(from.x + 20, from.y + from.height / 2);
            await page.mouse.down();
            await page.mouse.move(from.x + 20, from.y + from.height / 2 - 5, { steps: 3 });
            await page.mouse.move(to.x + 20, to.y + 2, { steps: 12 });
            await page.mouse.up();
        },
    },
    { handle: 'pSurveyRating', fill: (f) => f.getByRole('radio').nth(1).check({ force: true }) },
    { handle: 'pSurveyDropdown', fill: (f) => pickOption(f, 'Gamma') },
    { handle: 'pSurveyRadio', fill: (f) => f.getByRole('radio', { name: 'Alpha' }).check() },
    { handle: 'pSurveyCheckboxes', fill: (f) => f.getByRole('checkbox', { name: 'Beta' }).check() },
    { handle: 'pSurveySingleLineText', fill: (f) => f.getByRole('textbox').first().fill('Survey answer') },
    { handle: 'pSurveyMultiLineText', fill: (f) => f.locator('textarea').first().fill('Survey long answer\nSecond line'), normalize: lineBreaks },
    { handle: 'pQuizDropdown', fill: (f) => pickOption(f, 'Right') },
    { handle: 'pQuizRadio', fill: (f) => f.getByRole('radio', { name: 'Wrong' }).check() },
    { handle: 'pQuizCheckboxes', fill: (f) => f.getByRole('checkbox', { name: 'Right' }).check() },
];

// Fields that don't match the server-rendered form yet, with why. Fixing one makes its test fail until it's removed
// here, so this list can only shrink. The plan is in the private Formie context (plans/client-rendered-parity.md).
const KNOWN_GAPS: Record<string, string> = {
    pSignature: 'The server-rendered signature pad doesn’t save the drawn signature in this fixture, so the baseline is empty.',
};

type Run = { saved: Saved | null; problems: string[]; errors: string[] };

async function fillAndSubmit(page: Page, surface: Surface): Promise<Run> {
    const problems: string[] = [];
    const errors: string[] = [];
    // A field that can't be filled fails on its own instead of holding up the rest.
    page.setDefaultTimeout(8000);
    page.on('pageerror', (error) => errors.push(error.message || String(error)));
    page.on('console', (message) => {
        if (message.type() === 'error') errors.push(message.text());
    });
    // The fixture bundle loads the phone picker's CSS as text, so its flag image URL isn't rewritten as in the builds.
    await page.route('**/img/flags*.webp', (route) => route.fulfill({ status: 200, contentType: 'image/webp', body: '' }));
    await page.goto(surfaceUrl(surface, FORM));
    await expect(page.locator('#host button[type="submit"]').last()).toBeVisible({ timeout: 20000 });
    // Server-rendered enhancements load their libraries after the markup, so let every surface settle the same way.
    await page.waitForTimeout(1500);

    for (const step of STEPS) {
        const field = fieldLocator(page, step.handle);

        if (await field.getByText(/Unsupported field type/).count()) {
            problems.push(`${step.handle}: shows “Unsupported field type”`);
            continue;
        }

        if (!step.fill) continue;

        try {
            await step.fill(field, page);
        } catch (error) {
            problems.push(`${step.handle}: couldn’t be filled in like the server-rendered field (${String((error as Error).message).split('\n')[0].slice(0, 160)})`);
        }
    }

    // Uploads finish in the background before the submit is allowed.
    await page.waitForTimeout(1000);
    const response = page.waitForResponse((r) => r.request().method() === 'POST' && /submissions(%2F|\/)submit/.test(r.url()), { timeout: 30000 });
    await page.locator('#host button[type="submit"]').last().click();
    const body = await (await response).json().catch(() => ({}));
    const uid = typeof body.submissionUid === 'string' ? body.submissionUid : null;

    if (!uid) {
        problems.push(`submission wasn’t saved: ${JSON.stringify(body.errors ?? body).slice(0, 400)}`);
        return { saved: null, problems, errors };
    }

    const saved = await (await page.request.get(`/browser-parity-saved?uid=${uid}`)).json() as Saved;
    return { saved, problems, errors };
}

function normalized(values: Record<string, unknown>): Record<string, unknown> {
    return Object.fromEntries(STEPS.filter((step) => step.handle in values).map((step) => [step.handle, step.normalize ? step.normalize(values[step.handle]) : values[step.handle]]));
}

test.describe('client-rendered parity', () => {
    let baseline: Record<string, unknown> | null = null;

    // The server-rendered form is filled first, and must take every step, so a failing adapter can't hide another.
    test.beforeAll(async ({ browser }) => {
        test.setTimeout(120000);
        const page = await browser.newPage({ ignoreHTTPSErrors: true, baseURL: test.info().project.use.baseURL });
        const run = await fillAndSubmit(page, 'server');
        await page.close();

        expect(run.problems, 'the drivers must work on the server-rendered form').toEqual([]);
        expect(run.saved?.isSpam).toBe(false);
        expect(run.errors).toEqual([]);
        baseline = normalized(run.saved!.values);
    });

    for (const surface of ['vue', 'react', 'web-components'] as const) {
        test(`${surface} matches the server-rendered form`, async ({ page }) => {
            test.setTimeout(120000);
            expect(baseline, 'the server-rendered baseline must run first').not.toBeNull();
            const run = await fillAndSubmit(page, surface);
            const mismatches = [...run.problems];

            if (run.saved) {
                const values = normalized(run.saved.values);

                for (const [handle, expected] of Object.entries(baseline!)) {
                    if (JSON.stringify(values[handle]) !== JSON.stringify(expected)) {
                        mismatches.push(`${handle}: saved ${JSON.stringify(values[handle])}, server-rendered saved ${JSON.stringify(expected)}`);
                    }
                }
            }

            const gapOf = (line: string) => line.slice(0, line.indexOf(':'));
            const unexpected = mismatches.filter((line) => !(gapOf(line) in KNOWN_GAPS));
            const stillFailing = new Set(mismatches.map(gapOf));
            const fixed = Object.keys(KNOWN_GAPS).filter((handle) => !stillFailing.has(handle));

            expect(unexpected, 'fields that no longer match the server-rendered form').toEqual([]);
            expect(fixed, 'known gaps that now match; remove them from KNOWN_GAPS').toEqual([]);
            expect(run.errors).toEqual([]);
        });
    }
});

// Summary fields show the answers saved so far, fetched from the server once a page is submitted.
test.describe('client-rendered summary', () => {
    async function summaryText(page: Page, surface: Surface): Promise<string> {
        await page.goto(surfaceUrl(surface, 'paritySummary'));
        await page.locator('#host [data-formie-field-handle="visitorName"] input').fill('Ada Lovelace');
        await page.locator('#host [data-formie-field-handle="visitorEmail"] input').fill('ada@example.test');
        await page.locator('#host [data-formie-field-handle="topic"] select').selectOption('support');
        // Faster submissions look accepted but are kept as spam, and spam never grants summary access.
        await page.waitForTimeout(3500);
        await page.locator('#host button[type="submit"]').last().click();
        const blocks = page.locator('#host [data-formie-field-handle="review"] [data-formie-summary-blocks]');
        await expect(blocks).toContainText('Ada Lovelace', { timeout: 15000 });

        return (await blocks.innerText()).replace(/\s+/g, ' ').trim();
    }

    test('shows the same summary as the server-rendered form', async ({ browser }) => {
        test.setTimeout(120000);
        const texts: Record<string, string> = {};

        for (const surface of ['server', 'vue', 'react', 'web-components'] as const) {
            const page = await browser.newPage({ ignoreHTTPSErrors: true, baseURL: test.info().project.use.baseURL });
            texts[surface] = await summaryText(page, surface);
            await page.close();
        }

        expect(texts.server).toContain('Support');

        for (const surface of ['vue', 'react', 'web-components'] as const) {
            expect(texts[surface], surface).toBe(texts.server);
        }
    });
});
