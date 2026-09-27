import fs from 'node:fs/promises';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const sourcePath = resolve(root, 'src/config/browser-theme-state.json');
const typescriptPath = resolve(root, 'packages/formie-browser/src/js/theme/browser-theme-state.generated.ts');
const docsPath = resolve(root, 'docs/reference/browser-theme-state.md');
const manifest = JSON.parse(await fs.readFile(sourcePath, 'utf8'));
const typescriptEntries = Object.entries(manifest).map(([key, value]) => {
    return `    ${key}: ${JSON.stringify(value.classes)},`;
}).join('\n');
const typescript = `// Generated from src/config/browser-theme-state.json. Do not edit by hand.\nexport const BROWSER_THEME_STATE_DEFAULTS = {\n${typescriptEntries}\n} as const;\n\nexport type BrowserThemeStateKey = keyof typeof BROWSER_THEME_STATE_DEFAULTS;\n`;
const rows = Object.entries(manifest).map(([key, value]) => {
    return `| \`${key}\` | ${value.classes.map((className) => `\`${className}\``).join(', ') || 'None'} | ${value.description} |`;
}).join('\n');
const docs = `# Browser Theme State\n\n<!-- Generated from src/config/browser-theme-state.json. Do not edit by hand. -->\n\nThese semantic keys are the shared contract used by PHP-rendered markup and browser-created state. Configure the keys at the root of \`themeConfig\`; the resolved map is emitted as \`data-formie-theme-classes\`.\n\n| Key | Default classes | Purpose |\n| --- | --- | --- |\n${rows}\n`;

await Promise.all([
    fs.writeFile(typescriptPath, typescript),
    fs.writeFile(docsPath, docs),
]);
