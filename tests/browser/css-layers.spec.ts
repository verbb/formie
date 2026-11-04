import { expect, test } from '@playwright/test';
import { readFileSync, readdirSync } from 'node:fs';
import { createRequire } from 'node:module';
import path from 'node:path';

// Use the actual Tailwind version installed for Formie's CP, including Preflight.
const requireCp = createRequire(path.resolve('src/web/assets/cp/package.json'));
const { compile } = requireCp('tailwindcss');
const dist = path.resolve('src/web/assets/frontend/dist');
const origin = 'https://css-layers.example.test';
const layerOrder = '@layer theme, base, formie-base, formie-theme-base, formie-theme, components, utilities;';
let tailwind: string;

test.beforeAll(async () => {
    const theme = readFileSync(requireCp.resolve('tailwindcss/theme.css'), 'utf8');
    const preflight = readFileSync(requireCp.resolve('tailwindcss/preflight.css'), 'utf8');
    const compiler = await compile(`@layer theme, base, components, utilities; @layer theme {${theme}} @layer base {${preflight}} @layer utilities {@tailwind utilities;}`);
    tailwind = compiler.build(['border-0', 'rounded-none', 'bg-transparent']);
});

for (const layered of [false, true]) {
    for (const tailwindFirst of [false, true]) {
        test(`${layered ? 'layered' : 'unlayered'} CSS with Tailwind ${tailwindFirst ? 'before' : 'after'} Formie`, async ({ page }) => {
            const errors: string[] = [];
            const requests: string[] = [];
            page.on('pageerror', (error) => errors.push(error.message));
            page.on('request', (request) => requests.push(request.url()));
            const prefix = `/assets/${layered ? 'layered/' : ''}`;
            const css = `<link rel="stylesheet" href="${prefix}css/formie-base.css"><link rel="stylesheet" href="${prefix}css/formie-theme.css">`;
            const tw = '<link rel="stylesheet" href="/tailwind.css">';
            const manifest = JSON.stringify({ contractVersion: 2, surface: 'server-rendered', entries: [
                { key: 'signature', moduleId: 'formie:signature', kind: 'field', targets: [{ type: 'field', uid: 'signature' }], config: {}, required: true },
            ] });
            await page.route(`${origin}/**`, async (route) => {
                const url = new URL(route.request().url());
                if (url.pathname.startsWith('/assets/')) {
                    await route.fulfill({ path: path.resolve('src/web/assets/frontend/dist', url.pathname.slice('/assets/'.length)), contentType: url.pathname.endsWith('.css') ? 'text/css' : 'application/javascript' });
                } else if (url.pathname === '/tailwind.css') {
                    await route.fulfill({ body: tailwind, contentType: 'text/css' });
                } else {
                    await route.fulfill({ contentType: 'text/html', body: `<!doctype html><html><head>
                        ${layered ? `<style>${layerOrder}</style>` : ''}${tailwindFirst ? tw + css : css + tw}
                        </head><body><form class="formie-form" data-formie data-formie-handle="contact"
                        data-formie-automatic-submission-state="false" data-formie-modules='${manifest}'>
                        <label>Name<input id="name" class="formie-input" name="fields[name]"></label>
                        <select class="formie-select"><option>Option</option></select>
                        <textarea class="formie-textarea"></textarea>
                        <button type="button" class="formie-button">Send</button>
                        <div data-formie-field data-formie-field-uid="signature" data-formie-field-type="signature">
                        <div class="formie-field-control"><input type="hidden" name="fields[signature]" data-formie-signature-input>
                        <canvas class="formie-signature-canvas" data-formie-signature-canvas></canvas></div></div>
                        </form><button id="load" type="button">Load field</button>
                        <script>document.querySelector('#load').addEventListener('click', () => {
                            const script = document.createElement('script'); script.type = 'module';
                            script.dataset.formieStartup = ''; script.src = '${prefix}js/formie.js'; document.body.append(script);
                        });</script></body></html>` });
                }
            });
            await page.goto(origin);
            const input = page.locator('#name');
            await expect(input).toHaveCSS('border-top-width', '1px');
            await expect(input).toHaveCSS('background-color', 'rgb(255, 255, 255)');
            for (const selector of ['.formie-select', '.formie-textarea']) {
                await expect(page.locator(selector)).toHaveCSS('border-top-width', '1px');
                await expect(page.locator(selector)).toHaveCSS('background-color', 'rgb(255, 255, 255)');
            }
            await expect(page.locator('.formie-button')).not.toHaveCSS('background-color', 'rgba(0, 0, 0, 0)');
            await expect(page.locator('style[data-formie-module-style="signature"]')).toHaveCount(0);
            await page.getByRole('button', { name: 'Load field' }).click();
            const injected = page.locator('style[data-formie-module-style="signature"]');
            await expect(injected).toHaveCount(1);
            expect((await injected.textContent())?.includes('@layer formie-theme')).toBe(layered);
            const canvas = page.locator('canvas');
            await expect(canvas).toHaveCSS('border-top-width', '1px');
            await expect(canvas).toHaveCSS('background-color', 'rgb(248, 250, 252)');
            // Normal Tailwind utilities override the opted-in layers, not the unlayered default.
            await input.evaluate((element) => element.classList.add('border-0', 'rounded-none', 'bg-transparent'));
            await canvas.evaluate((element) => element.classList.add('border-0', 'rounded-none', 'bg-transparent'));
            for (const [element, background] of [[input, 'rgb(255, 255, 255)'], [canvas, 'rgb(248, 250, 252)']] as const) {
                await expect(element).toHaveCSS('border-top-width', layered ? '0px' : '1px');
                await expect(element).toHaveCSS('background-color', layered ? 'rgba(0, 0, 0, 0)' : background);
            }
            expect(requests.some((url) => url.includes(`${prefix}js/chunks/signature-`))).toBe(true);
            expect(errors).toEqual([]);
            await expect(page.locator('[data-formie-module-error]')).toHaveCount(0);
        });
    }
}

// Inline field and payment styles are embedded in lazy JS chunks, not just .css files.
test('every production chunk uses the selected Formie CSS mode', () => {
    for (const layered of [false, true]) {
        const directory = layered ? path.join(dist, 'layered') : dist;
        const files = readdirSync(directory, { recursive: true, encoding: 'utf8' })
            .filter((file) => !file.startsWith('layered/') && /\.(css|js)$/.test(file));
        expect(files.length).toBeGreaterThan(20);
        let layeredChunks = 0;
        for (const file of files) {
            const cssLayers = /@layer\s+formie-(?:base|theme)/.test(readFileSync(path.join(directory, file), 'utf8'));
            if (cssLayers && file.endsWith('.js')) layeredChunks++;
            if (!layered) expect(cssLayers, file).toBe(false);
        }
        if (layered) expect(layeredChunks).toBeGreaterThan(10);
    }
});
