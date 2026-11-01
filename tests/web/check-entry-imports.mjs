import assert from 'node:assert/strict';
import { readFile, access } from 'node:fs/promises';
import path from 'node:path';

const manifestPath = process.argv[2];
assert.ok(manifestPath, 'Pass the production Vite manifest path.');
const manifest = JSON.parse(await readFile(manifestPath, 'utf8'));
const entries = new Set(Object.keys(manifest).filter(key => manifest[key].isEntry));
assert.ok(entries.size, 'The manifest must contain page entries.');

for (const [key, chunk] of Object.entries(manifest)) {
    await access(path.resolve(path.dirname(manifestPath), chunk.file));
    for (const css of chunk.css ?? []) {
        await access(path.resolve(path.dirname(manifestPath), css));
    }
    for (const dependency of [...(chunk.imports ?? []), ...(chunk.dynamicImports ?? [])]) {
        assert.ok(manifest[dependency], `Missing dependency: ${dependency}`);
        // Craft adds buildId to page scripts, but relative imports omit it and execute them again.
        assert.ok(!entries.has(dependency), `${key} imports page entry ${dependency}; move shared code into a separate chunk.`);
    }
}
console.log(`PASS: ${entries.size} page entries have no incoming chunk imports; manifest files exist.`);
