#!/usr/bin/env node
/**
 * Sync published @verbb/formie-* and @verbb/plugin-kit npm pins, then rebuild
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

    return index === -1 ? null : args[index + 1] ?? null;
};

const paths = {
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

const resolvePluginKitVersion = () => {
    const arg = readArg('--plugin-kit-version');

    if (arg) {
        return arg;
    }

    const pluginKitVersion = npmVersion('@verbb/plugin-kit');
    const pluginKitReactVersion = npmVersion('@verbb/plugin-kit-react');

    if (pluginKitVersion !== pluginKitReactVersion) {
        throw new Error(
            `Plugin Kit versions are out of sync on npm (${pluginKitVersion} vs ${pluginKitReactVersion}). `
            + 'Pass --plugin-kit-version explicitly after aligning releases.',
        );
    }

    return pluginKitVersion;
};

const formieVersion = resolveFormieVersion();
const pluginKitVersion = resolvePluginKitVersion();

const cpPackage = readJson(paths.cpPackageJson);
const frontendPackage = readJson(paths.frontendPackageJson);

const plannedChanges = [
    {
        file: 'src/web/assets/cp/package.json',
        updates: [
            ['devDependencies.@verbb/plugin-kit', cpPackage.devDependencies?.['@verbb/plugin-kit'], pluginKitVersion],
            ['dependencies.@verbb/plugin-kit-react', cpPackage.dependencies?.['@verbb/plugin-kit-react'], pluginKitVersion],
        ],
    },
    {
        file: 'src/web/assets/frontend/package.json',
        updates: [
            ['dependencies.@verbb/formie-browser', frontendPackage.dependencies?.['@verbb/formie-browser'], formieVersion],
        ],
    },
];

console.log('Rebuild Formie plugin assets from published npm');
console.log(`  @verbb/formie-browser -> ${formieVersion}`);
console.log(`  @verbb/plugin-kit -> ${pluginKitVersion}`);
console.log(`  @verbb/plugin-kit-react -> ${pluginKitVersion}`);

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

cpPackage.devDependencies['@verbb/plugin-kit'] = pluginKitVersion;
cpPackage.dependencies['@verbb/plugin-kit-react'] = pluginKitVersion;
frontendPackage.dependencies['@verbb/formie-browser'] = formieVersion;

writeJson(paths.cpPackageJson, cpPackage);
writeJson(paths.frontendPackageJson, frontendPackage);

run('npm', ['install', '--ignore-scripts']);
run('npm', ['run', 'build:cp']);
run('npm', ['run', 'build:frontend']);

console.log('Assets rebuilt. Review dist output, then commit manually when ready.');
