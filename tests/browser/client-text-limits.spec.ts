import { test, expect, type Locator, type Page } from '@playwright/test';

// Text limits behave in client-rendered forms as in server-rendered ones: the same counter under each field with a
// maximum, and nothing submitted while an answer is outside its limits.
const SURFACES = ['server', 'vue', 'react', 'web-components'] as const;

function field(page: Page, handle: string) {
    return page.locator(`[data-formie-field-handle="${handle}"]`).first();
}

function counter(page: Page, handle: string): Locator {
    return field(page, handle).locator('[data-formie-limit-text]');
}

async function open(page: Page, surface: string) {
    const errors: string[] = [];
    page.on('pageerror', error => errors.push(error.message || String(error)));
    await page.goto(surface === 'server'
        ? '/browser-fixture?adapter=vue&product=server&form=adapterTextLimits'
        : `/browser-fixture?adapter=${surface}&form=adapterTextLimits`);
    await expect(page.locator('button[type="submit"]').first()).toBeVisible();

    return errors;
}

async function typeRichText(page: Page, text: string) {
    const editor = field(page, 'richStory').locator('[contenteditable="true"]').first();
    await editor.click();
    await page.keyboard.press('ControlOrMeta+a');
    await page.keyboard.press('Backspace');
    await editor.pressSequentially(text);
}

for (const surface of SURFACES) {
    test(`${surface}: counts down to each text limit and past it`, async ({ page }) => {
        const errors = await open(page, surface);

        await expect(counter(page, 'headline')).toHaveText('10 characters allowed');
        await expect(counter(page, 'story')).toHaveText('3 words allowed');
        await expect(counter(page, 'richStory')).toHaveText('5 characters allowed');
        await expect(counter(page, 'surveyNote')).toHaveText('4 characters allowed');

        await field(page, 'headline').getByRole('textbox').fill('Ada Lovela');
        await expect(counter(page, 'headline')).toHaveText('0 characters left');
        await field(page, 'headline').getByRole('textbox').fill('Ada Lovelace');
        await expect(counter(page, 'headline')).toHaveText('2 characters over limit');
        await field(page, 'headline').getByRole('textbox').fill('Ada');
        await expect(counter(page, 'headline')).toHaveText('7 characters left');

        await field(page, 'story').locator('textarea').fill('one two');
        await expect(counter(page, 'story')).toHaveText('1 word left');
        await field(page, 'story').locator('textarea').fill('one two three four');
        await expect(counter(page, 'story')).toHaveText('1 word over limit');

        // Rich text markup isn't counted.
        await typeRichText(page, 'Hi');
        await expect(counter(page, 'richStory')).toHaveText('3 characters left');

        await field(page, 'surveyNote').getByRole('textbox').fill('abcde');
        await expect(counter(page, 'surveyNote')).toHaveText('1 character over limit');

        expect(errors).toEqual([]);
    });

    if (surface === 'server') continue;

    test(`${surface}: submits nothing while an answer is outside its limits`, async ({ page }) => {
        const errors = await open(page, surface);
        let submits = 0;
        page.on('request', request => {
            if (request.method() === 'POST' && /submissions(%2F|\/)submit/.test(request.url())) submits++;
        });
        const submit = page.locator('#host button[type="submit"]');

        await field(page, 'headline').getByRole('textbox').fill('Ad');
        await field(page, 'story').locator('textarea').fill('one two three four');
        await typeRichText(page, 'Too long');
        await field(page, 'surveyNote').getByRole('textbox').fill('abcde');
        await submit.click();

        await expect(field(page, 'headline')).toContainText('Headline must be no less than 3 characters.');
        await expect(field(page, 'story')).toContainText('Story must be no greater than 3 words.');
        await expect(field(page, 'richStory')).toContainText('Rich story must be no greater than 5 characters.');
        await expect(field(page, 'surveyNote')).toContainText('Survey note must be no greater than 4 characters.');
        expect(submits).toBe(0);

        // Focus stays where the visitor types after a failed submit, so every keystroke lands.
        const headline = field(page, 'headline').getByRole('textbox');
        await headline.click();
        await page.keyboard.press('ControlOrMeta+a');
        await page.keyboard.type('Ada Lovela');
        await expect(headline).toBeFocused();
        await expect(headline).toHaveValue('Ada Lovela');
        await field(page, 'story').locator('textarea').fill('one two three');
        await typeRichText(page, 'Short');
        await field(page, 'surveyNote').getByRole('textbox').fill('abcd');

        await page.waitForTimeout(3000);
        const response = page.waitForResponse(r => r.request().method() === 'POST' && /client(%2F|\/)submissions(%2F|\/)submit/.test(r.url()));
        await submit.click();
        const uid = (await (await response).json()).submissionUid;
        const saved = await (await page.request.get(`/browser-parity-saved?uid=${uid}`)).json();

        expect(saved.values.headline).toBe('Ada Lovela');
        expect(saved.values.story).toBe('one two three');
        expect(String(saved.values.richStory)).toContain('Short');
        expect(saved.values.surveyNote).toBe('abcd');
        expect(errors).toEqual([]);
    });
}
