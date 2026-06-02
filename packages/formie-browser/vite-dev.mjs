import { resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = fileURLToPath(new URL('.', import.meta.url));
const srcRoot = resolve(__dirname, 'src');

/**
 * Minimal Vite aliases for apps that compile `@verbb/formie-browser` from `src/` during dev.
 * Internal `#…` specifiers resolve via this package's `package.json` `"imports"` (Node standard).
 */
export function getFormieBrowserViteDevAliases() {
    return [
        {
            find: /^@verbb\/formie-browser\/css\/(.*)$/,
            replacement: `${srcRoot}/css/$1`,
        },
        {
            find: /^@verbb\/formie-browser$/,
            replacement: resolve(srcRoot, 'index.ts'),
        },
    ];
}
