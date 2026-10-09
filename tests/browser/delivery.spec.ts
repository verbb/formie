import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

const fixture = () =>
    JSON.parse(
        readFileSync('.cache/verbb-tests/delivery-browser.json', 'utf8'),
    );

async function login(page, username = 'admin') {
    await page.goto('/admin/login');
    await page
        .getByRole('textbox', { name: 'Username or Email', exact: true })
        .fill(username);
    await page.locator('input[name="password"]').fill('testing-only-password');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await page.waitForURL((url) => !url.pathname.endsWith('/login'));
}

test('opens Formie diagnostics from a real Craft job with unresolved delivery and exports a redacted support bundle', async ({
    page,
}) => {
    await page.setViewportSize({ width: 1440, height: 1000 });
    const data = fixture();
    await login(page);
    await page.goto(`/admin/utilities/queue-manager/${data.integration.jobId}`);
    await expect(page.locator('.readable')).toContainText(
        'Integration delivery unknown. Open the Diagnostics panel for more information.',
    );
    const diagnosticsButton = page.getByRole('button', {
        name: 'Diagnostics',
        exact: true,
    });
    await expect(diagnosticsButton).toBeVisible();
    await expect(diagnosticsButton).toHaveClass('btn submit');
    await expect(diagnosticsButton.locator('xpath=..')).toHaveAttribute(
        'id',
        'toolbar',
    );
    await expect
        .poll(() =>
            diagnosticsButton.evaluate(
                (button) => button === button.parentElement?.lastElementChild,
            ),
        )
        .toBe(true);
    await page
        .locator('#toolbar')
        .evaluate((toolbar) => toolbar.append(document.createElement('span')));
    await expect
        .poll(() =>
            diagnosticsButton.evaluate(
                (button) => button === button.parentElement?.lastElementChild,
            ),
        )
        .toBe(true);
    await diagnosticsButton.click();
    const dialog = page.locator('pk-dialog');
    await expect(
        dialog.getByRole('heading', {
            name: 'Delivery outcome unknown',
            exact: true,
        }),
    ).toBeVisible();
    const dialogWidth = await dialog.evaluate(
        (element) =>
            element.shadowRoot
                ?.querySelector('.dialog')
                ?.getBoundingClientRect().width ?? 0,
    );
    const dialogHeight = await dialog.evaluate(
        (element) =>
            element.shadowRoot
                ?.querySelector('.dialog')
                ?.getBoundingClientRect().height ?? 0,
    );
    expect(dialogWidth).toBeGreaterThanOrEqual(1200);
    expect(dialogHeight).toBeGreaterThanOrEqual(780);
    const dialogBody = await dialog.evaluate((element) => {
        const body = element.shadowRoot?.querySelector('.body');

        return body
            ? {
                  clientHeight: body.clientHeight,
                  scrollHeight: body.scrollHeight,
                  overflowY: getComputedStyle(body).overflowY,
              }
            : null;
    });
    expect(dialogBody).not.toBeNull();
    expect(dialogBody?.overflowY).toBe('hidden');
    expect(dialogBody?.scrollHeight).toBeLessThanOrEqual(
        (dialogBody?.clientHeight ?? 0) + 1,
    );
    await expect(
        dialog.getByText(
            'Formie could not confirm whether the provider completed this delivery. Check the provider before recording an outcome.',
            { exact: true },
        ),
    ).toHaveCount(0);
    const openSubmission = dialog.getByRole('link', {
        name: 'Open submission',
        exact: true,
    });
    const openSubmissionButton = dialog
        .locator('pk-button')
        .filter({ hasText: 'Open submission' });
    await expect(openSubmission).toHaveAttribute('target', '_blank');
    const externalIcon = openSubmissionButton.locator('pk-icon');
    await expect(externalIcon).toHaveCount(1);
    expect(await externalIcon.evaluate((element: any) => element.icon)).toBe(
        'arrow-up-right-from-square',
    );
    const downloadIcon = dialog
        .locator('pk-button')
        .filter({ hasText: 'Export' })
        .locator('pk-icon');
    await expect(downloadIcon).toHaveCount(1);
    expect(await downloadIcon.evaluate((element: any) => element.icon)).toBe(
        'download',
    );
    await expect(
        dialog.getByRole('tab', { name: 'Overview', exact: true }),
    ).toHaveAttribute('aria-selected', 'true');
    await expect(
        dialog.getByText('Where to start', { exact: true }),
    ).toBeVisible();
    await expect(
        dialog.getByRole('heading', {
            name: 'The provider returned an error',
            exact: true,
        }),
    ).toBeVisible();
    await expect(
        dialog.locator('p').filter({ hasText: 'Gateway response lost' }),
    ).toBeVisible();
    await expect(
        dialog.getByRole('tab', { name: /Timeline \(\d+\)/ }),
    ).toBeVisible();
    await expect(
        dialog.getByRole('heading', { name: 'Delivery timeline', exact: true }),
    ).toHaveCount(0);
    await expect(
        dialog.getByRole('heading', {
            name: 'Diagnostic evidence',
            exact: true,
        }),
    ).toHaveCount(0);
    await expect(
        dialog.getByRole('heading', { name: 'Child operations', exact: true }),
    ).toHaveCount(0);
    await expect(
        dialog.getByRole('button', {
            name: 'Copy diagnostic summary',
            exact: true,
        }),
    ).toHaveCount(0);
    await expect(
        dialog.getByRole('button', {
            name: 'Export sensitive evidence',
            exact: true,
        }),
    ).toHaveCount(0);
    await expect(
        dialog.getByRole('heading', { name: 'Delivery actions', exact: true }),
    ).toHaveCount(0);
    await expect(
        dialog.getByRole('button', {
            name: 'Retry safe delivery',
            exact: true,
        }),
    ).toHaveCount(0);
    await expect(dialog.getByRole('checkbox')).toHaveCount(0);
    await expect(dialog.locator('details')).toHaveCount(0);
    await expect(
        dialog.getByRole('tab', { name: 'Errors (2)', exact: true }),
    ).toHaveAttribute('aria-selected', 'false');
    await dialog.getByRole('tab', { name: 'Errors (2)', exact: true }).click();
    await expect(
        dialog.locator('pre').filter({ hasText: 'Gateway response lost' }),
    ).toBeVisible();
    const copyButtons = dialog.getByRole('button', {
        name: 'Copy code',
        exact: true,
    });
    await expect(copyButtons).toHaveCount(2);
    const copyButtonHosts = dialog.locator(
        'pk-tab-panel:not([hidden]) pk-copy-button.formie-delivery-json-block-copy',
    );
    await expect(copyButtonHosts).toHaveCount(2);
    expect(
        await copyButtonHosts
            .first()
            .evaluate((element: any) => element.variant),
    ).toBe('transparent');
    await expect(
        dialog
            .locator(
                'pk-tab-panel:not([hidden]) .formie-delivery-json-block-copy-overlay',
            )
            .first(),
    ).toHaveCSS('position', 'sticky');
    await page
        .context()
        .grantPermissions(['clipboard-read', 'clipboard-write']);
    await copyButtons.first().click();
    const copiedButton = dialog.getByRole('button', {
        name: 'Copied',
        exact: true,
    });
    await expect(copiedButton).toBeVisible();
    const activePanel = dialog.locator('pk-tab-panel:not([hidden])');
    const jsonBlockLayout = await activePanel
        .locator('.formie-delivery-json-block')
        .evaluateAll((blocks) =>
            blocks.map((block) => {
                const scroll = block.querySelector(
                    '.formie-delivery-json-block-scroll',
                );
                const copy = block.querySelector('pk-copy-button');
                const blockRect = block.getBoundingClientRect();
                const scrollRect = scroll?.getBoundingClientRect();
                const copyRect = copy?.getBoundingClientRect();
                const scrollbarWidth = scroll
                    ? scroll.offsetWidth - scroll.clientWidth
                    : 0;

                return {
                    hasVerticalScrollbar:
                        (scroll?.scrollHeight ?? 0) >
                        (scroll?.clientHeight ?? 0),
                    clearOfScrollbar:
                        !!scrollRect &&
                        !!copyRect &&
                        copyRect.right <=
                            scrollRect.right - scrollbarWidth + 0.5,
                    fullWidthScrollArea:
                        !!scrollRect &&
                        Math.abs(scrollRect.width - blockRect.width) <= 1,
                };
            }),
        );
    expect(jsonBlockLayout).toContainEqual({
        hasVerticalScrollbar: false,
        clearOfScrollbar: true,
        fullWidthScrollArea: true,
    });
    expect(jsonBlockLayout).toContainEqual({
        hasVerticalScrollbar: true,
        clearOfScrollbar: true,
        fullWidthScrollArea: true,
    });
    const panelScroll = await activePanel.evaluate((element) => {
        const content = element.shadowRoot?.querySelector('.content');

        return content
            ? {
                  clientHeight: content.clientHeight,
                  scrollHeight: content.scrollHeight,
                  overflowY: getComputedStyle(content).overflowY,
              }
            : null;
    });
    expect(panelScroll).not.toBeNull();
    expect(panelScroll?.overflowY).toBe('auto');
    const codeScroll = await activePanel
        .locator('.formie-delivery-json-block-scroll')
        .last()
        .evaluate((element) => ({
            clientHeight: element.clientHeight,
            maxHeight: Number.parseFloat(getComputedStyle(element).maxHeight),
            overflowY: getComputedStyle(element).overflowY,
            scrollHeight: element.scrollHeight,
        }));
    expect(codeScroll.overflowY).toBe('auto');
    expect(codeScroll.maxHeight).toBeLessThanOrEqual(256);
    expect(codeScroll.scrollHeight).toBeGreaterThan(codeScroll.clientHeight);
    const secondErrorBox = await dialog
        .getByRole('heading', { name: 'Queue job failed', exact: true })
        .boundingBox();
    const panelBottom = await activePanel.evaluate(
        (element) =>
            element.shadowRoot
                ?.querySelector('.content')
                ?.getBoundingClientRect().bottom ?? 0,
    );
    expect(secondErrorBox).not.toBeNull();
    expect(secondErrorBox?.y).toBeLessThan(panelBottom);
    await page.screenshot({
        path: '../context/tasks/10-validation/delivery-modal.png',
        fullPage: true,
    });
    const evidenceFontSize = await dialog
        .locator('pre')
        .first()
        .evaluate((element) =>
            Number.parseFloat(getComputedStyle(element).fontSize),
        );
    expect(evidenceFontSize).toBeLessThanOrEqual(12);
    await dialog
        .getByRole('tab', { name: 'Submission data (2)', exact: true })
        .click();
    await expect(
        dialog.getByText('Delivery browser fixture', { exact: false }),
    ).toBeVisible();
    const resizedHeight = await dialog.evaluate(
        (element) =>
            element.shadowRoot
                ?.querySelector('.dialog')
                ?.getBoundingClientRect().height ?? 0,
    );
    expect(Math.abs(resizedHeight - dialogHeight)).toBeLessThanOrEqual(1);
    await dialog.getByRole('tab', { name: 'Errors (2)', exact: true }).click();
    await expect(
        dialog.locator('pre').filter({ hasText: 'Gateway response lost' }),
    ).toBeVisible();
    await dialog.getByRole('tab', { name: /Timeline \(\d+\)/ }).click();
    await expect(
        dialog.getByText('Delivery prepared', { exact: true }),
    ).toBeVisible();
    await expect(
        dialog.getByText('Queue job failed', { exact: true }).last(),
    ).toBeVisible();
    await expect(dialog).not.toContainText('browser-never-display-secret');
    expect(
        await page.evaluate(() => (window as any).deliveryInjection),
    ).toBeUndefined();
    const download = page.waitForEvent('download');
    await dialog.getByRole('button', { name: 'Export', exact: true }).click();
    expect((await download).suggestedFilename()).toBe(
        `formie-delivery-${data.integration.uid}.json`,
    );
    await expect(openSubmission).toHaveAttribute(
        'href',
        /formie\/submissions\/browserContract\/\d+/,
    );
    await dialog.getByRole('tab', { name: 'Overview', exact: true }).click();
    await dialog
        .getByRole('textbox', { name: 'Reconciliation reason' })
        .fill('Synthetic fixture confirmed not sent.');
    await dialog
        .getByRole('button', { name: 'Confirm not delivered', exact: true })
        .click();
    await expect(
        dialog.getByText('Reconciliation recorded.', { exact: true }),
    ).toBeVisible();
    await expect(
        dialog.getByRole('heading', { name: 'Delivery failed', exact: true }),
    ).toBeVisible();

    await page.goto(data.submissionUrl);
    await expect(
        page.getByRole('heading', { name: 'Submission Delivery History' }),
    ).toHaveCount(0);
    await expect(page.locator('.formie-delivery-history')).toHaveCount(0);
});

test('exposes Diagnostics for notification delivery jobs but not unrelated Formie queue jobs', async ({
    page,
}) => {
    const data = fixture();
    await login(page);
    await page.goto(
        `/admin/utilities/queue-manager/${data.notification.jobId}`,
    );
    await expect(page.locator('.readable')).toContainText(
        'Notification delivery failed. Open the Diagnostics panel for more information.',
    );
    const diagnosticsButton = page.getByRole('button', {
        name: 'Diagnostics',
        exact: true,
    });
    await expect(diagnosticsButton).toBeVisible();
    await expect(diagnosticsButton).toHaveClass('btn submit');
    await diagnosticsButton.click();
    const dialog = page.locator('pk-dialog');
    await expect(
        dialog.getByRole('heading', {
            name: 'The email was not sent',
            exact: true,
        }),
    ).toBeVisible();
    await expect(
        dialog.getByRole('tab', {
            name: 'Provider responses (1)',
            exact: true,
        }),
    ).toBeVisible();

    await page.goto(`/admin/utilities/queue-manager/${data.nonDelivery.jobId}`);
    await expect(page.locator('.readable')).toContainText('No file provided.');
    await expect(
        page.getByRole('button', { name: 'Diagnostics', exact: true }),
    ).toHaveCount(0);
});

test('shows provider evidence produced by a real TriggerIntegration worker run', async ({
    page,
}) => {
    const data = fixture();
    await login(page);
    await page.goto(
        `/admin/utilities/queue-manager/${data.triggerIntegration.jobId}`,
    );
    await expect(page.locator('.readable')).toContainText(
        'Integration delivery failed. Open the Diagnostics panel for more information.',
    );
    await page
        .getByRole('button', { name: 'Diagnostics', exact: true })
        .click();

    const dialog = page.locator('pk-dialog');
    await expect(
        dialog.getByRole('heading', {
            name: 'The provider returned an error',
            exact: true,
        }),
    ).toBeVisible();
    await expect(
        dialog.getByText('The browser fixture provider rejected the payload.', {
            exact: true,
        }),
    ).toBeVisible();
    await dialog.getByRole('tab', { name: 'Errors (2)', exact: true }).click();
    await expect(
        dialog.locator('pre').filter({
            hasText: 'The browser fixture provider rejected the payload.',
        }),
    ).toBeVisible();
    await expect(
        dialog
            .locator('pk-tab-panel:not([hidden]) .light')
            .filter({ hasText: 'browserRuntimeFailure' })
            .first(),
    ).toBeVisible();
    await dialog.getByRole('tab', { name: /Timeline \(\d+\)/ }).click();
    await expect(
        dialog.getByText(/Provider returned an error · browserRuntimeFailure/),
    ).toBeVisible();
});

test('denies delivery diagnostics without diagnostics and form permissions', async ({
    page,
}) => {
    const data = fixture();
    await login(page, 'browserScopedEditor');
    const status = await page.evaluate(async (uid) => {
        try {
            await (window as any).Craft.sendActionRequest(
                'GET',
                'formie/delivery/bundle',
                { params: { uid } },
            );
            return 200;
        } catch (error: any) {
            return error.response.status;
        }
    }, data.integration.uid);
    expect(status).toBe(403);
});
