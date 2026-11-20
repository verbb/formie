import { test, expect, type Locator, type Page } from '@playwright/test';

// Labels, instructions and errors sit around a field's control where its positions put them, in client-rendered forms
// as in server-rendered ones, and the field says which positions it uses so themes can lay them out.
const SURFACES = ['server', 'vue', 'react', 'web-components'] as const;

async function order(page: Page, handle: string): Promise<string[]> {
    return page.locator(`[data-formie-field-handle="${handle}"]`).first().evaluate((field) => {
        const parts: Array<[string, Element | null]> = [
            ['label', field.querySelector('label')],
            ['instructions', field.querySelector('[id$="-instructions"]')],
            ['input', field.querySelector('input')],
            ['errors', field.querySelector('[data-formie-field-errors]')],
        ];

        return parts.filter((part): part is [string, Element] => part[1] !== null)
            .sort((a, b) => (a[1].compareDocumentPosition(b[1]) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1))
            .map(([name]) => name);
    });
}

function positions(field: Locator) {
    return field.evaluate((element) => {
        const holder = element.querySelector('[data-formie-label-position]') ?? element;

        return [holder.getAttribute('data-formie-label-position'), holder.getAttribute('data-formie-instructions-position') ?? holder.closest('[data-formie-instructions-position]')?.getAttribute('data-formie-instructions-position')];
    });
}

for (const surface of SURFACES) {
    test(`${surface}: places labels, instructions and errors by their positions`, async ({ page }) => {
        await page.goto(surface === 'server'
            ? '/browser-fixture?adapter=vue&product=server&form=adapterPositions'
            : `/browser-fixture?adapter=${surface}&form=adapterPositions`);
        await expect(page.locator('button[type="submit"]').first()).toBeVisible();
        // Errors show where they go once the empty required fields are submitted.
        await page.locator('button[type="submit"]').first().click();
        await expect(page.locator('[data-formie-field-handle="belowLabel"] [data-formie-field-errors]').first()).toContainText('cannot be blank');

        expect(await order(page, 'leftLabel')).toEqual(['label', 'instructions', 'input', 'errors']);
        expect(await order(page, 'rightLabel')).toEqual(['input', 'errors', 'label']);
        expect(await order(page, 'belowLabel')).toEqual(['errors', 'input', 'instructions', 'label']);

        expect(await positions(page.locator('[data-formie-field-handle="leftLabel"]').first())).toEqual(['left', 'above']);
        expect(await positions(page.locator('[data-formie-field-handle="belowLabel"]').first())).toEqual(['below', 'below']);
    });
}
