#!/usr/bin/env node
/**
 * Sync current @verbb/formie-* and split Plugin Kit npm pins, then rebuild
 * Formie's bundled CP and frontend assets.
 *
 * Run after publishing Formie npm packages and Plugin Kit npm packages.
 * Does not commit, bump composer.json, or edit the plugin CHANGELOG.
 *
 * Usage (from formie-plugin-repo/):
 *   npm run rebuild:assets
 *   npm run rebuild:assets -- --formie-version 1.0.5 --plugin-kit-version 1.0.5
 *   npm run rebuild:assets -- --dry-run
 */
import { execFileSync } from 'node:child_process';
import { readFileSync, writeFileSync } from 'node:fs';

const args = process.argv.slice(2);
const dryRun = args.includes('--dry-run');

const readArg = (name) => {
    const index = args.indexOf(name);

    if (index === -1) {
        return null;
    }

    const value = args[index + 1];

    if (!value || value.startsWith('--')) {
        throw new Error(`Missing value for ${name}.`);
    }

    return value;
};

const paths = {
    rootPackageJson: new URL('../package.json', import.meta.url),
    cpPackageJson: new URL('../src/web/assets/cp/package.json', import.meta.url),
    frontendPackageJson: new URL('../src/web/assets/frontend/package.json', import.meta.url),
    formieBrowserPackageJson: new URL('../packages/formie-browser/package.json', import.meta.url),
};

const run = (command, runArgs, options = {}) => {
    execFileSync(command, runArgs, {
        stdio: 'inherit',
        ...options,
    });
};

const output = (command, runArgs) => execFileSync(command, runArgs, {
    encoding: 'utf8',
}).trim();

const readJson = (fileUrl) => JSON.parse(readFileSync(fileUrl, 'utf8'));

const writeJson = (fileUrl, value) => {
    const indent = fileUrl.pathname.endsWith('cp/package.json') ? 4 : 2;

    writeFileSync(fileUrl, `${JSON.stringify(value, null, indent)}\n`, 'utf8');
};

const npmVersion = (packageName) => output('npm', ['view', packageName, 'version']);

const resolveFormieVersion = () => {
    const arg = readArg('--formie-version');

    if (arg) {
        return arg;
    }

    try {
        return readJson(paths.formieBrowserPackageJson).version;
    } catch {
        return npmVersion('@verbb/formie-browser');
    }
};

const dependencyTypes = ['dependencies', 'devDependencies', 'peerDependencies'];
const manifests = [
    { file: 'package.json', url: paths.rootPackageJson },
    { file: 'src/web/assets/cp/package.json', url: paths.cpPackageJson },
    { file: 'src/web/assets/frontend/package.json', url: paths.frontendPackageJson },
].map((entry) => ({ ...entry, value: readJson(entry.url) }));
const kitPackages = [...new Set(manifests.flatMap(({ value }) => dependencyTypes.flatMap((type) => (
    Object.keys(value[type] ?? {}).filter((name) => name.startsWith('@verbb/plugin-kit-'))
))))];

const resolvePluginKitVersion = () => {
    const arg = readArg('--plugin-kit-version');

    if (arg) {
        return arg;
    }

    const versions = kitPackages.map((name) => [name, npmVersion(name)]);

    if (!versions.length || new Set(versions.map(([, version]) => version)).size !== 1) {
        throw new Error(`Split Plugin Kit releases are out of sync: ${JSON.stringify(versions)}. Pass --plugin-kit-version after verifying a compatible release.`);
    }

    return versions[0][1];
};

const formieVersion = resolveFormieVersion();

if (formieVersion !== readJson(paths.formieBrowserPackageJson).version) {
    throw new Error('The Formie asset version must match this checkout. Check out the matching source before rebuilding.');
}

const pluginKitVersion = resolvePluginKitVersion();
const plannedChanges = [];

for (const manifest of manifests) {
    const updates = [];

    for (const type of dependencyTypes) {
        for (const [name, current] of Object.entries(manifest.value[type] ?? {})) {
            const next = name.startsWith('@verbb/plugin-kit-')
                ? pluginKitVersion
                : (name === '@verbb/formie-browser' && manifest.url === paths.frontendPackageJson ? formieVersion : null);

            if (next !== null) {
                updates.push([`${type}.${name}`, current, next]);
                manifest.value[type][name] = next;
            }
        }
    }

    plannedChanges.push({ ...manifest, updates });
}

console.log('Rebuild Formie assets from current source and published split Plugin Kit packages');
console.log(`  @verbb/formie-browser -> ${formieVersion}`);
console.log(`  ${kitPackages.length} split Plugin Kit packages -> ${pluginKitVersion}`);

for (const { file, updates } of plannedChanges) {
    for (const [label, current, next] of updates) {
        const unchanged = current === next;
        console.log(`  ${file}: ${label} ${current ?? '(missing)'} -> ${next}${unchanged ? ' (unchanged)' : ''}`);
    }
}

if (dryRun) {
    console.log('Dry run complete. Re-run without --dry-run to sync pins, install, and rebuild assets.');
    process.exit(0);
}

for (const { url, value, updates } of plannedChanges) {
    if (updates.length) {
        writeJson(url, value);
    }
}

run('npm', ['install', '--ignore-scripts']);
run('npm', ['run', 'build:cp']);
run('npm', ['run', 'build:frontend']);

console.log('Assets rebuilt. Review dist output, then commit manually when ready.');
