import { test, expect, type Locator, type Page } from '@playwright/test';
import { createHash } from 'node:crypto';

async function chooseAsset(page: Page, field: Locator, assetId: number) {
    await field.getByRole('button', { name: 'Choose', exact: true }).click();
    const modal = page.locator('.elementselectormodal:visible');
    await expect(modal).toHaveCount(1);
    await expect(modal).not.toHaveClass(/velocity-animating/);
    await modal.locator(`[data-id="${assetId}"]`).first().click();
    await modal.getByRole('button', { name: 'Select', exact: true }).click();
    await expect(modal).toHaveCount(0);
}

test('CP file pickers keep attachment identity after adding and removing repeater rows', async ({
    page,
    request,
}) => {
    test.setTimeout(60000);
    const fixture = await (
        await request.post('/browser-cp-repeater-upload')
    ).json();
    const errors: string[] = [];
    await page.addInitScript(() => {
        document.addEventListener('formie:field:repeater:init-row', (event) => {
            const row = (event as CustomEvent<{ row: HTMLElement }>).detail.row;
            row.dataset.cpRowReady = 'true';
        });
    });
    page.on('pageerror', (error) => errors.push(error.message));
    await page.goto('/admin/login');
    await page
        .getByRole('textbox', { name: 'Username or Email', exact: true })
        .fill('admin');
    await page.locator('input[name="password"]').fill('testing-only-password');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await page.waitForURL((url) => !url.pathname.endsWith('/login'));
    await page.waitForLoadState('networkidle');
    await page.goto(fixture.editUrl);
    const rows = page.locator(
        '#fields-people-field [data-formie-repeater-item]',
    );
    const name = page.locator('input[name="fields[visitorName]"]');
    await expect(name).toHaveValue('Original visitor');
    await expect(rows).toHaveCount(2);

    // The saved-row picker is the control for the dynamically appended rows.
    await rows
        .first()
        .getByRole('button', { name: 'Choose', exact: true })
        .click();
    await expect(page.locator('.elementselectormodal:visible')).toHaveCount(1);
    await expect(
        page.locator('.elementselectormodal:visible [data-id]').first(),
    ).toBeVisible();
    await page.waitForLoadState('networkidle');
    await page
        .locator('.elementselectormodal:visible')
        .getByRole('button', { name: 'Cancel', exact: true })
        .click();
    await expect(page.locator('.elementselectormodal:visible')).toHaveCount(0);
    await page.waitForLoadState('networkidle');
    await page.getByRole('button', { name: 'Add person', exact: true }).click();
    await expect(rows).toHaveCount(3);
    await expect(rows.nth(2)).toHaveAttribute('data-cp-row-ready', 'true');
    await rows
        .nth(2)
        .getByRole('textbox', { name: 'Row label', exact: true })
        .fill('Person three');
    await chooseAsset(page, rows.nth(2), fixture.assets.replacement);
    await page.getByRole('button', { name: 'Add person', exact: true }).click();
    await expect(rows).toHaveCount(4);
    await expect(rows.nth(3)).toHaveAttribute('data-cp-row-ready', 'true');
    await rows
        .nth(3)
        .getByRole('textbox', { name: 'Row label', exact: true })
        .fill('Person four');
    await chooseAsset(page, rows.nth(3), fixture.assets.first);

    // Adding rows must not register additional handlers on existing pickers.
    await rows
        .nth(1)
        .getByRole('button', { name: 'Choose', exact: true })
        .click();
    await expect(page.locator('.elementselectormodal:visible')).toHaveCount(1);
    await expect(
        page.locator('.elementselectormodal:visible [data-id]').first(),
    ).toBeVisible();
    await page.waitForLoadState('networkidle');
    await page
        .locator('.elementselectormodal:visible')
        .getByRole('button', { name: 'Cancel', exact: true })
        .click();
    await expect(page.locator('.elementselectormodal:visible')).toHaveCount(0);
    await page.waitForLoadState('networkidle');
    await rows
        .first()
        .getByRole('button', { name: 'Remove row', exact: true })
        .click();
    await expect(rows).toHaveCount(3);
    const reviewers = page.locator(
        '#fields-reviewers-field [data-formie-repeater-item]',
    );
    await page
        .getByRole('button', { name: 'Add reviewer', exact: true })
        .click();
    await expect(reviewers).toHaveCount(2);
    await expect(reviewers.nth(1)).toHaveAttribute('data-cp-row-ready', 'true');
    await reviewers
        .nth(1)
        .getByRole('textbox', { name: 'Row label', exact: true })
        .fill('Reviewer two');
    await chooseAsset(page, reviewers.nth(1), fixture.assets.top);
    await name.fill('');
    await page.getByRole('button', { name: 'Save', exact: true }).click();
    await expect(
        page.getByText('Visitor name cannot be blank.', { exact: false }),
    ).toBeVisible();
    const unchanged = await (
        await request.get(`/browser-cp-repeater-upload?id=${fixture.id}`)
    ).json();
    expect(unchanged.name).toBe('Original visitor');
    await expect(rows).toHaveCount(3);
    // Validation rerenders must retain the selected assets and working controls.
    await rows
        .nth(1)
        .getByRole('button', { name: 'Choose', exact: true })
        .click();
    await expect(page.locator('.elementselectormodal:visible')).toHaveCount(1);
    await expect(
        page.locator('.elementselectormodal:visible [data-id]').first(),
    ).toBeVisible();
    await page.waitForLoadState('networkidle');
    await page
        .locator('.elementselectormodal:visible')
        .getByRole('button', { name: 'Cancel', exact: true })
        .click();
    await expect(page.locator('.elementselectormodal:visible')).toHaveCount(0);
    await page.waitForLoadState('networkidle');
    await name.fill('Saved CP attachments');
    // Save and continue editing keeps this regression on the attachment editor.
    await page.keyboard.press('ControlOrMeta+s');
    await expect
        .poll(async () => {
            const state = await (
                await request.get(
                    `/browser-cp-repeater-upload?id=${fixture.id}`,
                )
            ).json();
            return state.name;
        })
        .toBe('Saved CP attachments');
    await page.waitForLoadState('networkidle');
    await page.goto(fixture.editUrl);
    await expect(rows).toHaveCount(3);
    await expect(
        rows.nth(0).getByRole('textbox', { name: 'Row label', exact: true }),
    ).toHaveValue('Person two');
    await expect(
        rows.nth(1).getByRole('textbox', { name: 'Row label', exact: true }),
    ).toHaveValue('Person three');
    await expect(
        rows.nth(2).getByRole('textbox', { name: 'Row label', exact: true }),
    ).toHaveValue('Person four');

    const state = await (
        await request.get(`/browser-cp-repeater-upload?id=${fixture.id}`)
    ).json();
    expect(state.people).toEqual(['Person two', 'Person three', 'Person four']);
    for (const [fieldPath, assetName] of Object.entries({
        topDocument: 'top',
        'details.document': 'group',
        'people.0.document': 'second',
        'people.1.document': 'replacement',
        'people.2.document': 'first',
        'reviewers.0.document': 'group',
        'reviewers.1.document': 'top',
    })) {
        expect(state.files[fieldPath]).toEqual([
            {
                id: fixture.assets[assetName],
                sha256: createHash('sha256')
                    .update(`CP audit ${assetName} contents`)
                    .digest('hex'),
            },
        ]);
    }
    expect(errors).toEqual([]);
});
