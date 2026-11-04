import { test, expect } from '@playwright/test';
import { matchesAction } from './action-url';

test.beforeEach(async ({ page }) => {
    await page.addInitScript(() => {
        document.addEventListener('formie:mount:after', () => {
            document.documentElement.dataset.formieReady = 'true';
        });
    });
});

for (const mode of ['advanced', 'simple']) {
    test(`Twig-rendered ${mode} upload survives validation and Back, then clears after completion`, async ({
        page,
        request,
    }) => {
        test.setTimeout(60000);
        const errors: string[] = [];
        page.on('pageerror', (error) => errors.push(error.message));
        await page.goto(`/browser-upload-reset?mode=${mode}`);
        await expect(page.locator('html')).toHaveAttribute(
            'data-formie-ready',
            'true',
        );
        await expect(
            page.locator('script[data-formie-startup]'),
        ).toHaveAttribute('src', /\/cpresources\/.+\/js\/formie\.js$/);

        const name = `Upload ${mode} ${Date.now()}`;
        const contents = 'Retained browser upload contents';
        const form = page.locator('form[data-formie]');
        const next = form.locator('[data-formie-action="submit"]:visible');
        const attachmentIds = form.locator(
            'input[data-formie-file-upload-asset-id]',
        );
        await page
            .getByRole('textbox', { name: 'Visitor name', exact: true })
            .fill(name);
        await form.locator('input[type="file"]').setInputFiles({
            name: 'retained.txt',
            mimeType: 'text/plain',
            buffer: Buffer.from(contents),
        });
        if (mode === 'advanced') {
            await expect(
                form.locator('[data-formie-upload-manager-item].is-complete'),
            ).toHaveCount(1);
            await expect(attachmentIds).toHaveCount(1);
        }
        // Respect the real endpoint's minimum elapsed-time guard.
        await page.waitForFunction(
            () =>
                Date.now() -
                    Number(
                        document.querySelector<HTMLInputElement>(
                            'input[name="formStartedAt"]',
                        )?.value,
                    ) >=
                3500,
        );
        await next.click();
        await expect(
            page.getByRole('textbox', { name: 'Confirmation', exact: true }),
        ).toBeVisible();
        const rejected = page.waitForResponse(
            (response) =>
                response.request().method() === 'POST' &&
                response
                    .request()
                    .postData()
                    ?.includes('formie/submissions/submit') === true,
        );
        await next.click();
        await rejected;
        await expect(
            page.getByRole('textbox', { name: 'Confirmation', exact: true }),
        ).toHaveAttribute('aria-invalid', 'true');
        await form.locator('[data-formie-action="back"]:visible').click();
        await expect(
            page.getByRole('textbox', { name: 'Visitor name', exact: true }),
        ).toHaveValue(name);
        await expect(attachmentIds).toHaveCount(1);
        const assetId = await attachmentIds.getAttribute(
            'data-formie-file-upload-asset-id',
        );
        if (mode === 'advanced') {
            // Soft so the final reset and saved-asset checks still run on a regression.
            await expect
                .soft(form.locator('[data-formie-upload-manager-item]'))
                .toHaveCount(1);
        } else {
            await expect(
                form.locator('[data-formie-file-summary]'),
            ).toContainText('retained');
        }
        await next.click();
        await page
            .getByRole('textbox', { name: 'Confirmation', exact: true })
            .fill('Confirmed');
        await next.click();
        await expect(
            page.getByText('Submission saved.', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByRole('textbox', { name: 'Visitor name', exact: true }),
        ).toHaveValue('');
        await expect(form.locator('input[name="expectedVersion"]')).toHaveValue(
            '0',
        );
        await expect(form.locator('input[name="submissionUid"]')).toHaveCount(
            0,
        );

        const saved = (
            await (await request.get('/browser-upload-saved')).json()
        ).filter((row: { name: string }) => row.name === name);
        expect(saved).toEqual([
            {
                id: expect.any(Number),
                name,
                confirmation: 'Confirmed',
                files: [
                    {
                        id: Number(assetId),
                        filename: expect.stringMatching(
                            /^retained(?:_[a-zA-Z0-9_-]+)?\.txt$/,
                        ),
                        contents,
                    },
                ],
            },
        ]);
        await expect.soft(attachmentIds).toHaveCount(0);
        await expect
            .soft(form.locator('[data-formie-upload-manager-item]'))
            .toHaveCount(0);
        if (mode === 'simple')
            await expect
                .soft(form.locator('[data-formie-file-summary]'))
                .toHaveText('');
        if (mode === 'advanced')
            await expect(
                form.locator('[data-formie-upload-manager-status]'),
            ).toHaveValue('');

        const nextName = name + ' second';
        await page
            .getByRole('textbox', { name: 'Visitor name', exact: true })
            .fill(nextName);
        await form.locator('input[type="file"]').setInputFiles({
            name: 'second.txt',
            mimeType: 'text/plain',
            buffer: Buffer.from('A separate submission file'),
        });
        if (mode === 'advanced')
            await expect(
                form.locator('[data-formie-upload-manager-item].is-complete'),
            ).toHaveCount(1);
        await page.waitForFunction(
            () =>
                Date.now() -
                    Number(
                        document.querySelector<HTMLInputElement>(
                            'input[name="formStartedAt"]',
                        )?.value,
                    ) >=
                3500,
        );
        await next.click();
        await page
            .getByRole('textbox', { name: 'Confirmation', exact: true })
            .fill('Second confirmation');
        await next.click();
        await expect(
            page.getByRole('textbox', { name: 'Visitor name', exact: true }),
        ).toHaveValue('');
        const afterSecond = await (
            await request.get('/browser-upload-saved')
        ).json();
        expect(
            afterSecond.filter((row: { name: string }) => row.name === name),
        ).toEqual(saved);
        const second = afterSecond.filter(
            (row: { name: string }) => row.name === nextName,
        );
        expect(second).toEqual([
            {
                id: expect.any(Number),
                name: nextName,
                confirmation: 'Second confirmation',
                files: [
                    {
                        id: expect.any(Number),
                        filename: expect.stringMatching(
                            /^second(?:_[a-zA-Z0-9_-]+)?\.txt$/,
                        ),
                        contents: 'A separate submission file',
                    },
                ],
            },
        ]);
        expect(second[0].files[0].id).not.toBe(Number(assetId));
        expect(errors).toEqual([]);
    });
}

test('advanced upload removes the retained file after Back and saves only its replacement', async ({
    page,
    request,
}) => {
    const previous = await (await request.get('/browser-upload-saved')).json();
    let deleteRequests = 0;
    page.on('request', (request) => {
        if (matchesAction(request.url(), 'formie/file-upload/delete'))
            deleteRequests += 1;
    });
    await page.goto('/browser-upload-reset');
    await expect(page.locator('html')).toHaveAttribute(
        'data-formie-ready',
        'true',
    );
    const name = `Upload replacement ${Date.now()}`;
    const form = page.locator('form[data-formie]');
    const next = form.locator('[data-formie-action="submit"]:visible');
    const assetInputs = form.locator('input[data-formie-file-upload-asset-id]');
    await page
        .getByRole('textbox', { name: 'Visitor name', exact: true })
        .fill(name);
    await form
        .locator('input[type="file"]')
        .setInputFiles({
            name: 'staged.txt',
            mimeType: 'text/plain',
            buffer: Buffer.from('Remove before attachment'),
        });
    await expect(
        form.locator('[data-formie-upload-manager-item].is-complete'),
    ).toHaveCount(1);
    const stagedId = await assetInputs.getAttribute(
        'data-formie-file-upload-asset-id',
    );
    const stagedDelete = page.waitForResponse((response) =>
        matchesAction(response.url(), 'formie/file-upload/delete'),
    );
    await form
        .getByRole('button', { name: 'Remove file', exact: true })
        .click();
    const stagedResponse = await stagedDelete;
    expect(stagedResponse.ok()).toBe(true);
    expect(stagedResponse.request().postData()).toContain(
        `\r\n${stagedId}\r\n`,
    );
    await expect(assetInputs).toHaveCount(0);
    await form.locator('input[type="file"]').setInputFiles({
        name: 'remove.txt',
        mimeType: 'text/plain',
        buffer: Buffer.from('Remove this file'),
    });
    await expect(
        form.locator('[data-formie-upload-manager-item].is-complete'),
    ).toHaveCount(1);
    const removedId = await assetInputs.getAttribute(
        'data-formie-file-upload-asset-id',
    );
    await page.waitForFunction(
        () =>
            Date.now() -
                Number(
                    document.querySelector<HTMLInputElement>(
                        'input[name="formStartedAt"]',
                    )?.value,
                ) >=
            3500,
    );
    await next.click();
    await expect(
        page.getByRole('textbox', { name: 'Confirmation', exact: true }),
    ).toBeVisible();
    await next.click();
    await expect(
        page.getByRole('textbox', { name: 'Confirmation', exact: true }),
    ).toHaveAttribute('aria-invalid', 'true');
    await form.locator('[data-formie-action="back"]:visible').click();
    await expect(
        form.locator('[data-formie-upload-manager-item].is-complete'),
    ).toHaveCount(1);
    await form
        .getByRole('button', { name: 'Remove file', exact: true })
        .click();
    await expect(assetInputs).toHaveCount(0);
    await expect(form.locator('[data-formie-upload-manager-item]')).toHaveCount(
        0,
    );
    await form.locator('input[type="file"]').setInputFiles({
        name: 'replacement.txt',
        mimeType: 'text/plain',
        buffer: Buffer.from('Keep only this replacement'),
    });
    await expect(
        form.locator('[data-formie-upload-manager-item].is-complete'),
    ).toHaveCount(1);
    const replacementId = await assetInputs.getAttribute(
        'data-formie-file-upload-asset-id',
    );
    expect(replacementId).not.toBe(removedId);
    await next.click();
    await page
        .getByRole('textbox', { name: 'Confirmation', exact: true })
        .fill('Replacement confirmed');
    await next.click();
    await expect(
        page.getByRole('textbox', { name: 'Visitor name', exact: true }),
    ).toHaveValue('');
    const allSaved = await (await request.get('/browser-upload-saved')).json();
    const saved = allSaved.filter((row: { name: string }) => row.name === name);
    expect(saved).toEqual([
        {
            id: expect.any(Number),
            name,
            confirmation: 'Replacement confirmed',
            files: [
                {
                    id: Number(replacementId),
                    filename: expect.stringMatching(
                        /^replacement(?:_[a-zA-Z0-9_-]+)?\.txt$/,
                    ),
                    contents: 'Keep only this replacement',
                },
            ],
        },
    ]);
    expect(deleteRequests).toBe(1);
    const previousIds = new Set(previous.map((row: { id: number }) => row.id));
    expect(
        allSaved.filter((row: { id: number }) => previousIds.has(row.id)),
    ).toEqual(previous);
});

for (const mode of ['advanced', 'simple']) {
    test(`${mode} upload ignores a real hydration response that arrives after completion`, async ({
        page,
        request,
    }) => {
        test.setTimeout(60000);
        await page.goto(`/browser-upload-reset?mode=${mode}`);
        await expect(page.locator('html')).toHaveAttribute(
            'data-formie-ready',
            'true',
        );
        const name = `Late upload ${mode} ${Date.now()}`;
        const form = page.locator('form[data-formie]');
        const next = form.locator('[data-formie-action="submit"]:visible');
        await page
            .getByRole('textbox', { name: 'Visitor name', exact: true })
            .fill(name);
        await form.locator('input[type="file"]').setInputFiles({
            name: 'late.txt',
            mimeType: 'text/plain',
            buffer: Buffer.from('Retain the completed file, not its old UI'),
        });
        if (mode === 'advanced')
            await expect(
                form.locator('[data-formie-upload-manager-item].is-complete'),
            ).toHaveCount(1);

        let release = () => {};
        let markHeld = () => {};
        let markDelivered = () => {};
        const gate = new Promise<void>((resolve) => {
            release = resolve;
        });
        const held = new Promise<void>((resolve) => {
            markHeld = resolve;
        });
        const delivered = new Promise<void>((resolve) => {
            markDelivered = resolve;
        });
        let captured = false;
        await page.route('**/*', async (route) => {
            if (
                !captured &&
                matchesAction(
                    route.request().url(),
                    'formie/file-upload/hydrate',
                )
            ) {
                captured = true;
                // Fetch the actual authorized response now; delay only its delivery.
                const response = await route.fetch();
                expect(response.ok()).toBe(true);
                expect((await response.json()).assets).toHaveLength(1);
                markHeld();
                await gate;
                await route.fulfill({ response });
                markDelivered();
            } else {
                await route.continue();
            }
        });
        try {
            await page.waitForFunction(
                () =>
                    Date.now() -
                        Number(
                            document.querySelector<HTMLInputElement>(
                                'input[name="formStartedAt"]',
                            )?.value,
                        ) >=
                    3500,
            );
            await next.click();
            await expect(
                page.getByRole('textbox', {
                    name: 'Confirmation',
                    exact: true,
                }),
            ).toBeVisible();
            await form.locator('[data-formie-action="back"]:visible').click();
            await expect(
                page.getByRole('textbox', {
                    name: 'Visitor name',
                    exact: true,
                }),
            ).toHaveValue(name);
            await held;
            await expect(
                form.locator('input[data-formie-file-upload-asset-id]'),
            ).toHaveCount(1);
            await next.click();
            await page
                .getByRole('textbox', { name: 'Confirmation', exact: true })
                .fill('Late response confirmed');
            await next.click();
            await expect(
                page.getByRole('textbox', {
                    name: 'Visitor name',
                    exact: true,
                }),
            ).toHaveValue('');
            const hydrated = page.waitForResponse((response) =>
                matchesAction(response.url(), 'formie/file-upload/hydrate'),
            );
            release();
            await delivered;
            await (await hydrated).finished();
            // Let response parsing and DOM updates finish before checking absence.
            await page.evaluate(
                () =>
                    new Promise<void>((resolve) =>
                        requestAnimationFrame(() =>
                            requestAnimationFrame(() => resolve()),
                        ),
                    ),
            );
            await expect(
                form.locator('input[data-formie-file-upload-asset-id]'),
            ).toHaveCount(0);
            await expect(
                form.locator('[data-formie-upload-manager-item]'),
            ).toHaveCount(0);
            if (mode === 'simple')
                await expect(
                    form.locator('[data-formie-file-summary]'),
                ).toHaveText('');
            const saved = (
                await (await request.get('/browser-upload-saved')).json()
            ).filter((row: { name: string }) => row.name === name);
            expect(saved).toEqual([
                {
                    id: expect.any(Number),
                    name,
                    confirmation: 'Late response confirmed',
                    files: [
                        {
                            id: expect.any(Number),
                            filename: expect.stringMatching(
                                /^late(?:_[a-zA-Z0-9_-]+)?\.txt$/,
                            ),
                            contents:
                                'Retain the completed file, not its old UI',
                        },
                    ],
                },
            ]);
        } finally {
            release();
        }
    });
}
