import { test, expect } from '@playwright/test';

test('native file input hydration retains CSRF and form context', async ({ page }) => {
    await page.goto('/browser-fixture');
    await page.waitForFunction(() => !!(window as any).fileUploadBoundary);
    const result = await page.evaluate(async () => {
        const form = document.createElement('form');
        form.innerHTML = `
            <input type="hidden" name="customCsrf" value="current-csrf" data-formie-csrf>
            <input type="hidden" name="handle" value="uploadForm">
            <input type="hidden" name="submissionUid" value="submission-uid">
            <input type="hidden" name="siteId" value="2">
            <input type="hidden" name="renderId" value="render-id">
            <input type="hidden" name="draftContextToken" value="draft-token">
            <input type="hidden" name="submissionEditToken" value="edit-token">
            <div data-formie-field-handle="documents">
                <input type="file" name="fields[documents][]" data-formie-file-input>
            </div>
        `;
        document.body.append(form);
        const originalFetch = window.fetch;
        let payload: Record<string, FormDataEntryValue> = {};
        window.fetch = async (_input, init) => {
            payload = Object.fromEntries((init?.body as FormData).entries());
            return new Response(JSON.stringify({ assets: [{ assetId: 42, filename: 'retained.txt' }] }), { headers: { 'Content-Type': 'application/json' } });
        };
        const module = await (window as any).fileUploadBoundary.setup({ form, target: form, emit: async () => {} });
        try {
            const hydrated = new Promise<void>(resolve => form.addEventListener('formie:field:file-upload:uploaded-assets-sync', () => resolve(), { once: true }));
            form.dispatchEvent(new CustomEvent('formie:file-upload:uploaded', { detail: { fieldHandle: 'documents', assetIds: [42] } }));
            await hydrated;
            return { payload, summary: form.querySelector('[data-formie-file-summary]')?.textContent };
        } finally {
            module.destroy();
            window.fetch = originalFetch;
            form.remove();
        }
    });
    expect(result.payload).toEqual({ customCsrf: 'current-csrf', handle: 'uploadForm', fieldHandle: 'documents', submissionUid: 'submission-uid', siteId: '2', renderId: 'render-id', draftContextToken: 'draft-token', submissionEditToken: 'edit-token', 'assetIds[]': '42' });
    expect(result.summary).toContain('retained.txt');
});
