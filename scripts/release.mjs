#!/usr/bin/env node
/**
 * Release the five @verbb/formie-* npm packages in lockstep.
 *
 * CHANGELOG.md is the source of truth. While developing, add entries under
 * `## Unreleased` in packages/formie-browser/CHANGELOG.md (primary). Add package-
 * specific entries under `## Unreleased` in other package changelogs when needed.
 *
 * Usage (from formie-plugin-repo/):
 *   npm run release:patch
 *   npm run release:minor
 *   npm run release:major
 *   npm run release:patch -- --dry-run
 */
import { execFileSync } from 'node:child_process';
import { readFileSync, writeFileSync } from 'node:fs';

const args = process.argv.slice(2);
const dryRun = args.includes('--dry-run');
const bump = args.find((arg) => arg !== '--dry-run') ?? 'patch';
const validBumps = new Set(['patch', 'minor', 'major']);

const packages = [
    '@verbb/formie-core',
    '@verbb/formie-browser',
    '@verbb/formie-react',
    '@verbb/formie-vue',
    '@verbb/formie-web-components',
];

const publishOrder = packages;

const paths = {
    formieBrowserChangelog: new URL('../packages/formie-browser/CHANGELOG.md', import.meta.url),
    formieCoreChangelog: new URL('../packages/formie-core/CHANGELOG.md', import.meta.url),
    formieReactChangelog: new URL('../packages/formie-react/CHANGELOG.md', import.meta.url),
    formieVueChangelog: new URL('../packages/formie-vue/CHANGELOG.md', import.meta.url),
    formieWebComponentsChangelog: new URL('../packages/formie-web-components/CHANGELOG.md', import.meta.url),
    formieBrowserPackageJson: new URL('../packages/formie-browser/package.json', import.meta.url),
};

const internalDependencyManifests = [
    'packages/formie-browser/package.json',
    'packages/formie-react/package.json',
    'packages/formie-vue/package.json',
    'packages/formie-web-components/package.json',
    'packages/docs/package.json',
];

const rollbackPaths = [
    'packages/formie-core/package.json',
    ...internalDependencyManifests,
    'packages/formie-core/CHANGELOG.md',
    'packages/formie-browser/CHANGELOG.md',
    'packages/formie-react/CHANGELOG.md',
    'packages/formie-vue/CHANGELOG.md',
    'packages/formie-web-components/CHANGELOG.md',
    'package-lock.json',
];

if (!validBumps.has(bump)) {
    console.error('Usage: npm run release -- <patch|minor|major> [--dry-run]');
    process.exit(1);
}

const run = (command, args, options = {}) => {
    execFileSync(command, args, {
        stdio: 'inherit',
        ...options,
    });
};

const output = (command, args) => execFileSync(command, args, {
    encoding: 'utf8',
}).trim();

const tryOutput = (command, args) => {
    try {
        return output(command, args);
    } catch {
        return null;
    }
};

const ensureCleanWorkingTree = () => {
    const status = output('git', ['status', '--porcelain']);

    if (status) {
        console.error('Release aborted: commit or stash current changes first.');
        console.error(status);
        process.exit(1);
    }
};

const verifyNpmAuth = () => {
    const user = tryOutput('npm', ['whoami']);

    if (!user) {
        throw new Error(
            'Not logged in to npm. Run `npm login` in this terminal, or add a publish token to ~/.npmrc.',
        );
    }

    console.log(`npm publish identity: ${user}`);

    for (const packageName of packages) {
        const access = tryOutput('npm', ['access', 'get', 'status', packageName]);

        if (!access) {
            throw new Error(
                `Cannot verify publish access for ${packageName}. `
                + 'Ensure your npm user or token can publish to the @verbb scope.',
            );
        }
    }
};

const packageVersion = () => JSON.parse(readFileSync(paths.formieBrowserPackageJson, 'utf8')).version;

const releaseDate = () => new Date().toISOString().slice(0, 10);

const readChangelog = (fileUrl) => readFileSync(fileUrl, 'utf8').replace(/\r\n/g, '\n');

const writeChangelog = (fileUrl, content) => {
    writeFileSync(fileUrl, `${content.replace(/\n+$/, '')}\n`, 'utf8');
};

const extractUnreleasedBody = (content) => {
    const match = content.match(/^## Unreleased\n([\s\S]*?)(?=^## )/m);

    return match ? match[1] : null;
};

const hasMeaningfulChangelogBody = (body) => {
    if (!body?.trim()) {
        return false;
    }

    return body.split('\n').some((line) => {
        const trimmed = line.trim();

        return /^(-|\*|\d+\.)\s+\S/.test(trimmed) || /^###\s+\S/.test(trimmed);
    });
};

const lockstepBody = (packageName) => (
    '### Changed\n'
    + `- Released alongside the other \`@verbb/formie-*\` packages to keep versions aligned (${packageName}).\n`
);

const finalizeChangelogContent = (content, version, date, { requireContent = true, packageName } = {}) => {
    const normalized = content.replace(/\r\n/g, '\n');

    if (!normalized.startsWith('# Changelog\n')) {
        throw new Error('CHANGELOG must start with "# Changelog"');
    }

    const unreleasedBody = extractUnreleasedBody(normalized);
    let releaseBody = unreleasedBody?.trim() ?? '';

    if (!hasMeaningfulChangelogBody(releaseBody)) {
        if (requireContent) {
            throw new Error('packages/formie-browser/CHANGELOG.md must have change entries under `## Unreleased`');
        }

        releaseBody = lockstepBody(packageName).trim();
    }

    const previousSections = normalized
        .replace(/^# Changelog\n\n/, '')
        .replace(/^## Unreleased\n[\s\S]*?(?=^## )/m, '')
        .trimStart();

    return `# Changelog\n\n## Unreleased\n\n## ${version} - ${date}\n\n${releaseBody}\n\n${previousSections}`.trimEnd();
};

const rollbackReleaseChanges = () => {
    try {
        execFileSync('git', ['checkout', '--', ...rollbackPaths], {
            stdio: 'inherit',
        });
        console.error('Release failed. Restored package versions and changelogs.');
    } catch {
        console.error('Release failed and automatic rollback did not work.');
        console.error(`Run: git checkout -- ${rollbackPaths.join(' ')}`);
    }
};

const syncInternalDependencies = (version) => {
    for (const relativePath of internalDependencyManifests) {
        const fileUrl = new URL(`../${relativePath}`, import.meta.url);
        const pkg = JSON.parse(readFileSync(fileUrl, 'utf8'));
        let changed = false;

        for (const depType of ['dependencies', 'devDependencies', 'peerDependencies']) {
            const deps = pkg[depType];

            if (!deps) {
                continue;
            }

            for (const packageName of packages) {
                if (deps[packageName]) {
                    deps[packageName] = version;
                    changed = true;
                }
            }
        }

        if (changed) {
            writeFileSync(fileUrl, `${JSON.stringify(pkg, null, 2)}\n`, 'utf8');
        }
    }
};

const buildPackages = () => {
    run('npm', ['run', 'build:packages']);
};

const packDryRunAll = () => {
    for (const packageName of packages) {
        run('npm', ['pack', '--dry-run', '-w', packageName]);
    }
};

const publishPackages = () => {
    for (const packageName of publishOrder) {
        try {
            run('npm', ['publish', '-w', packageName, '--access', 'public']);
        } catch (error) {
            throw new Error(
                `npm publish failed for ${packageName}.\n`
                + 'If you see E404, npm auth is usually the cause even when install works.\n'
                + 'Run `npm login` in this terminal, or refresh your publish token in ~/.npmrc.\n'
                + 'Granular tokens need Publish permission for the @verbb scope.',
                { cause: error },
            );
        }
    }
};

const satelliteChangelogs = [
    { fileUrl: paths.formieCoreChangelog, packageName: '@verbb/formie-core' },
    { fileUrl: paths.formieReactChangelog, packageName: '@verbb/formie-react' },
    { fileUrl: paths.formieVueChangelog, packageName: '@verbb/formie-vue' },
    { fileUrl: paths.formieWebComponentsChangelog, packageName: '@verbb/formie-web-components' },
];

if (!dryRun) {
    ensureCleanWorkingTree();
} else {
    console.log('Dry run: skipping clean-tree check, version bump, changelog writes, publish, and commit.');
}

const formieBrowserChangelog = readChangelog(paths.formieBrowserChangelog);

if (!hasMeaningfulChangelogBody(extractUnreleasedBody(formieBrowserChangelog))) {
    console.error('Release aborted: add entries under `## Unreleased` in packages/formie-browser/CHANGELOG.md first.');
    process.exit(1);
}

console.log('Changelog check passed. Running pre-release build…');

try {
    buildPackages();
    packDryRunAll();
} catch (error) {
    console.error('Pre-release build failed. Fix build errors before releasing.');
    console.error(error.message ?? error);
    process.exit(1);
}

if (dryRun) {
    console.log('Dry run complete. Re-run without --dry-run to publish.');
    process.exit(0);
}

verifyNpmAuth();

const currentVersion = packageVersion();
let releaseStarted = false;

try {
    for (const packageName of packages) {
        run('npm', ['version', bump, '-w', packageName, '--no-git-tag-version']);
    }

    const version = packageVersion();

    if (version === currentVersion) {
        throw new Error(`Version did not change after ${bump} bump (still ${currentVersion}).`);
    }

    syncInternalDependencies(version);
    run('npm', ['install', '--ignore-scripts']);

    releaseStarted = true;

    const date = releaseDate();

    writeChangelog(
        paths.formieBrowserChangelog,
        finalizeChangelogContent(formieBrowserChangelog, version, date, {
            requireContent: true,
            packageName: '@verbb/formie-browser',
        }),
    );

    for (const { fileUrl, packageName } of satelliteChangelogs) {
        writeChangelog(
            fileUrl,
            finalizeChangelogContent(readChangelog(fileUrl), version, date, {
                requireContent: false,
                packageName,
            }),
        );
    }

    buildPackages();
    packDryRunAll();
    publishPackages();

    run('git', [
        'add',
        'package-lock.json',
        'packages/formie-core/package.json',
        'packages/formie-browser/package.json',
        'packages/formie-react/package.json',
        'packages/formie-vue/package.json',
        'packages/formie-web-components/package.json',
        'packages/docs/package.json',
        'packages/formie-core/CHANGELOG.md',
        'packages/formie-browser/CHANGELOG.md',
        'packages/formie-react/CHANGELOG.md',
        'packages/formie-vue/CHANGELOG.md',
        'packages/formie-web-components/CHANGELOG.md',
    ]);
    run('git', ['commit', '-m', `Release formie packages ${version}`]);

    console.log(`Released Formie packages ${version} to npm.`);
    console.log('Push the release commit with:');
    console.log('  git push origin beta');
} catch (error) {
    if (releaseStarted) {
        rollbackReleaseChanges();
    }

    console.error(error.message ?? error);
    process.exit(1);
}
