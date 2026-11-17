import { test, expect } from '@playwright/test';
import { build } from 'esbuild';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import type { initFormieCombobox } from '../../packages/formie-browser/src/js/modules/fields/combobox';

declare global {
    interface Window {
        comboboxBoundary: { initFormieCombobox: typeof initFormieCombobox };
    }
}

let script: string;

test.beforeAll(async () => {
    const imports = JSON.parse(
        readFileSync('packages/formie-browser/package.json', 'utf8'),
    ).imports;
    const result = await build({
        stdin: {
            contents: `export { initFormieCombobox } from './packages/formie-browser/src/js/modules/fields/combobox';`,
            resolveDir: process.cwd(),
        },
        bundle: true,
        format: 'iife',
        globalName: 'comboboxBoundary',
        alias: Object.fromEntries(
            Object.entries(imports).map(([key, value]) => [
                key.replace('/*', ''),
                path.resolve(
                    'packages/formie-browser',
                    (typeof value === 'string'
                        ? value
                        : (value as { default: string }).default
                    ).replace('/*', ''),
                ),
            ]),
        ),
        plugins: [{
            // Theme styles are irrelevant to the strings being checked.
            name: 'inline-css',
            setup(builder) {
                builder.onResolve({ filter: /\?inline$/ }, (args) => ({ path: args.path, namespace: 'inline-css' }));
                builder.onLoad({ filter: /.*/, namespace: 'inline-css' }, () => ({ contents: 'export default "";', loader: 'js' }));
            },
        }],
        write: false,
    });
    script = result.outputFiles[0].text;
});

test.beforeEach(async ({ page }) => {
    // Translations arrive the way Rendering::getBrowserJsTranslations() seeds them on a German page.
    await page.setContent(`
        <script type="application/json" data-formie-translations>${JSON.stringify({
            'No results found': 'Keine Ergebnisse gefunden',
            Remove: 'Entfernen',
            'Clear selection': 'Auswahl löschen',
        })}</script>
        <form>
            <select name="fields[entries][]" multiple data-formie-combobox-input>
                <option value="1">First entry</option>
                <option value="2">Second entry</option>
            </select>
            <select name="fields[colour]" data-formie-combobox-input>
                <option value="">Pick one</option>
                <option value="red">Red</option>
            </select>
        </form>
    `);
    await page.addScriptTag({ content: script });
    await page.evaluate(() => {
        const [multi, single] = Array.from(document.querySelectorAll('select'));
        window.comboboxBoundary.initFormieCombobox(multi, { multiple: true });
        window.comboboxBoundary.initFormieCombobox(single, { allowClear: true, placeholder: 'Pick one' });
    });
});

test('a search with no matches uses the translated empty message', async ({ page }) => {
    await page.locator('.ts-wrapper.multi input').first().fill('zzzz');

    await expect(page.locator('.ts-dropdown .no-results')).toHaveText('Keine Ergebnisse gefunden');
});

test('remove and clear buttons use translated titles', async ({ page }) => {
    await page.evaluate(() => {
        const [multi, single] = Array.from(document.querySelectorAll('select')) as Array<HTMLSelectElement & { _formieTomSelect: { setValue: (value: string | string[]) => void } }>;
        multi._formieTomSelect.setValue(['1']);
        single._formieTomSelect.setValue('red');
    });

    await expect(page.locator('.ts-wrapper.multi .item .remove')).toHaveAttribute('title', 'Entfernen');
    await expect(page.locator('.ts-wrapper.single .remove')).toHaveAttribute('title', 'Auswahl löschen');
});

test('before-init listeners can still add plugins to a multi-select', async ({ page }) => {
    const plugins = await page.evaluate(() => {
        const select = document.createElement('select');
        select.multiple = true;
        select.setAttribute('data-formie-combobox-input', '');
        document.querySelector('form')!.append(select);
        let seen: unknown = null;
        select.addEventListener('formie:field:combobox:before-init', (event) => {
            const options = (event as CustomEvent).detail.options;
            options.plugins.push('clear_button');
            seen = options.plugins.length;
        });
        window.comboboxBoundary.initFormieCombobox(select, { multiple: true });

        return { seen, clear: !!select.parentElement!.querySelector('.ts-wrapper .clear-button') };
    });

    expect(plugins).toEqual({ seen: 2, clear: true });
});
