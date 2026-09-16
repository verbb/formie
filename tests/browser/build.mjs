import { build } from 'esbuild';
import path from 'node:path';
import { readFileSync } from 'node:fs';
const root = process.cwd();
const options = {
    bundle: true, format: 'iife', platform: 'browser', sourcemap: true,
    loader: { '.css': 'text', '.svg': 'text', '.png': 'dataurl', '.webp': 'dataurl' },
    define: { 'process.env.NODE_ENV': '"development"' },
    alias: { ...Object.fromEntries(['core', 'browser', 'react', 'vue', 'web-components'].map(name => [
        `@verbb/formie-${name}`, path.join(root, `packages/formie-${name}/src/index.ts`),
    ])), ...Object.fromEntries(Object.entries(JSON.parse(readFileSync('packages/formie-browser/package.json', 'utf8')).imports).map(([key, value]) => [key.replace('/*', ''), path.resolve('packages/formie-browser', value.replace('/*', ''))])) },
    tsconfigRaw: { compilerOptions: { experimentalDecorators: true, useDefineForClassFields: false, jsx: 'react-jsx' } },
};
await build({ ...options, entryPoints: ['tests/browser/fixture.ts'], outfile: '.cache/verbb-tests/browser/fixture.js' });
