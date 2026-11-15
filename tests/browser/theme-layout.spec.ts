import { test, expect, type Page } from '@playwright/test';
import { readFileSync } from 'node:fs';

const css = ['formie-base', 'formie-theme']
    .map((name) => readFileSync(`src/web/assets/frontend/dist/css/${name}.css`, 'utf8'))
    .join('\n');

async function render(page: Page, html: string) {
    await page.setViewportSize({ width: 1000, height: 720 });
    await page.route('**/theme-layout-fixture', (route) =>
        route.fulfill({ contentType: 'text/html', body: `<!doctype html><style>${css}</style><form class="formie-form" style="width:900px">${html}</form>` }),
    );
    await page.goto('/theme-layout-fixture');
}

for (const position of ['left', 'right']) {
    test(`a field without a label fills the row when labels are ${position} of the input`, async ({ page }) => {
        await render(page, `
            <div class="formie-field-layout" id="labelled" data-formie-label-position="${position}">
                <label class="formie-label formie-field-label" for="input">Name</label>
                <div class="formie-field-content" id="labelled-content"><input id="input" style="width:100%"></div>
            </div>
            <div class="formie-field-layout" id="heading" data-formie-label-position="${position}">
                <div class="formie-field-content" id="heading-content"><h2>A much longer section heading</h2></div>
            </div>
        `);

        const heading = await page.locator('#heading-content').boundingBox();
        const labelled = await page.locator('#labelled-content').boundingBox();

        expect(heading!.width).toBe(900);
        expect(labelled!.width).toBeLessThan(900);
    });
}

for (const position of ['left', 'right']) {
    test(`a progress value positioned ${position} of the bar shares its line`, async ({ page }) => {
        const value = '<span class="formie-progress-value" id="value">50%</span>';
        const bar = '<div class="formie-progress" id="bar"><div class="formie-progress-bar" style="width:50%"></div></div>';

        await render(page, `<div class="formie-progress-wrapper">${position === 'left' ? value + bar : bar + value}</div>`);

        const valueBox = (await page.locator('#value').boundingBox())!;
        const barBox = (await page.locator('#bar').boundingBox())!;

        expect(valueBox.y + valueBox.height).toBeGreaterThan(barBox.y);
        expect(valueBox.y).toBeLessThan(barBox.y + barBox.height);

        if (position === 'left') {
            expect(valueBox.x + valueBox.width).toBeLessThanOrEqual(barBox.x);
        } else {
            expect(valueBox.x).toBeGreaterThanOrEqual(barBox.x + barBox.width);
        }
    });
}

test('a progress bar without a separate value fills its wrapper', async ({ page }) => {
    await render(page, '<div class="formie-progress-wrapper"><div class="formie-progress" id="bar"><div class="formie-progress-bar" style="width:50%"></div></div></div>');

    expect((await page.locator('#bar').boundingBox())!.width).toBe(900);
});
