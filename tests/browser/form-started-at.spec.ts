import { test, expect } from '@playwright/test';
import { build } from 'esbuild';
import { readFileSync } from 'node:fs';
import path from 'node:path';

let script: string;
test.beforeAll(async () => {
    const imports = JSON.parse(readFileSync('packages/formie-browser/package.json', 'utf8')).imports;
    const result = await build({
        stdin: { contents: `
            export { ensureFormStartedAt } from './packages/formie-browser/src/js/utils/form-started-at';
            export { applySubmitResultState } from './packages/formie-browser/src/js/core/submit-result-state';
        `, resolveDir: process.cwd() },
        bundle: true, format: 'iife', globalName: 'timerBoundary', write: false,
        alias: Object.fromEntries(Object.entries(imports).map(([key, value]) => [
            key.replace('/*', ''),
            path.resolve('packages/formie-browser', (typeof value === 'string' ? value : (value as { default: string }).default).replace('/*', '')),
        ])),
    });
    script = result.outputFiles[0].text;
});

test.beforeEach(async ({ page }) => {
    await page.route('**/timer-fixture', route => route.fulfill({ contentType: 'text/html', body: `
        <form><input type="hidden" name="renderId" value="timer-form"><input type="hidden" name="formStartedAt" value="">
        <label>Message<input name="message"></label><button>Submit</button></form>
    ` }));
    await page.goto('/timer-fixture');
    await page.addScriptTag({ content: script });
});

test('a fresh visit starts a new timer instead of inheriting an expired submission', async ({ page }) => {
    const result = await page.evaluate(() => {
        sessionStorage.setItem('formie:formStartedAt:timer-form', String(Date.now() - 120000));
        const before = Date.now();
        (window as any).timerBoundary.ensureFormStartedAt(document.querySelector('form'));
        return { before, started: Number(document.querySelector<HTMLInputElement>('[name=formStartedAt]')!.value) };
    });
    expect(result.started).toBeGreaterThanOrEqual(result.before);
});

for (const identity of ['submissionUid', 'progressId']) {
    test(`continuing ${identity} preserves elapsed time across native page loads`, async ({ page }) => {
        const result = await page.evaluate(identity => {
            const original = String(Date.now() - 120000);
            sessionStorage.setItem('formie:formStartedAt:timer-form', original);
            const form = document.querySelector('form')!;
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = identity; input.value = 'existing-draft'; form.append(input);
            (window as any).timerBoundary.ensureFormStartedAt(form);
            return { original, actual: form.querySelector<HTMLInputElement>('[name=formStartedAt]')!.value };
        }, identity);
        expect(result.actual).toBe(result.original);
    });
}

test('remounting the same live form preserves its original timer', async ({ page }) => {
    const result = await page.evaluate(() => {
        const form = document.querySelector('form')!;
        const original = String(Date.now() - 120000);
        form.querySelector<HTMLInputElement>('[name=formStartedAt]')!.value = original;
        (window as any).timerBoundary.ensureFormStartedAt(form);
        return { original, actual: form.querySelector<HTMLInputElement>('[name=formStartedAt]')!.value };
    });
    expect(result.actual).toBe(result.original);
});

test('successful Ajax completion resets the timer for the next submission', async ({ page }) => {
    const result = await page.evaluate(() => {
        const form = document.querySelector('form')!;
        const old = String(Date.now() - 120000);
        sessionStorage.setItem('formie:formStartedAt:timer-form', old);
        form.querySelector<HTMLInputElement>('[name=formStartedAt]')!.value = old;
        const before = Date.now();
        (window as any).timerBoundary.applySubmitResultState(form, {
            ok: true, action: 'submit', completion: { behavior: 'message', message: 'Accepted', url: null, target: 'same-tab' },
        }, 'submit');
        return { before, started: Number(form.querySelector<HTMLInputElement>('[name=formStartedAt]')!.value), stored: sessionStorage.getItem('formie:formStartedAt:timer-form') };
    });
    expect(result.started).toBeGreaterThanOrEqual(result.before);
    expect(result.stored).toBe(String(result.started));
});
