#!/usr/bin/env node
/**
 * Prepare the five public @verbb/formie-* packages for hosted publication.
 *
 * This command changes package versions, internal dependencies, changelogs,
 * and the lockfile for review. It never publishes, commits, or pushes.
 */
import { execFileSync } from 'node:child_process';
import {
    copyFileSync,
    existsSync,
    mkdirSync,
    mkdtempSync,
    readFileSync,
    readdirSync,
    rmSync,
    writeFileSync,
} from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const repositoryRoot = new URL('../', import.meta.url);
process.chdir(fileURLToPath(repositoryRoot));

const packageDirs = [
    'packages/formie-core',
    'packages/formie-browser',
    'packages/formie-react',
    'packages/formie-vue',
    'packages/formie-web-components',
];

const packages = packageDirs.map((dir) => `@verbb/${dir.split('/').at(-1)}`);
const versionSourceDir = 'packages/formie-browser';
const packageJsonPaths = packageDirs.map((dir) => `${dir}/package.json`);
const internalDependencyManifests = [
    ...packageJsonPaths,
    'packages/docs/package.json',
    'src/web/assets/frontend/package.json',
];

const args = process.argv.slice(2);

if (args.includes('--help') || args.includes('-h')) {
    console.log(
        'Prepare Formie packages: node scripts/prepare-release.mjs [patch|minor|major] [--dry-run]',
    );
    console.log('Maintenance: --check-lockfile or --refresh-lockfile-only');
    console.log('This command never publishes to npm, commits, or pushes.');
    process.exit(0);
}

const dryRun = args.includes('--dry-run');
const checkLockfileOnly = args.includes('--check-lockfile');
const refreshLockfileOnly = args.includes('--refresh-lockfile-only');
const optionNames = new Set([
    '--dry-run',
    '--check-lockfile',
    '--refresh-lockfile-only',
]);
const bump = args.find((arg) => !optionNames.has(arg)) ?? 'patch';
const validBumps = new Set(['patch', 'minor', 'major']);

if (
    [dryRun, checkLockfileOnly, refreshLockfileOnly].filter(Boolean).length > 1
) {
    throw new Error(
        'Use only one of --dry-run, --check-lockfile, or --refresh-lockfile-only.',
    );
}

if (!checkLockfileOnly && !refreshLockfileOnly && !validBumps.has(bump)) {
    throw new Error('Expected patch, minor, or major.');
}

const run = (command, commandArgs, options = {}) => {
    execFileSync(command, commandArgs, {
        stdio: 'inherit',
        ...options,
    });
};

const output = (command, commandArgs) =>
    execFileSync(command, commandArgs, {
        encoding: 'utf8',
    }).trim();

const packageVersion = () =>
    JSON.parse(
        readFileSync(
            new URL(`${versionSourceDir}/package.json`, repositoryRoot),
            'utf8',
        ),
    ).version;

const ensureCleanWorkingTree = () => {
    const status = output('git', ['status', '--porcelain']);

    if (status) {
        throw new Error(
            `Preparation aborted: review existing repository changes first.\n${status}`,
        );
    }
};

const ensureCleanLockfile = () => {
    const status = output('git', [
        'status',
        '--porcelain',
        '--',
        'package-lock.json',
    ]);

    if (status) {
        throw new Error(
            `Lockfile refresh aborted: review the existing package-lock.json change first.\n${status}`,
        );
    }
};

const assertLockstepVersions = () => {
    const expected = packageVersion();
    const mismatches = [];

    for (const relativePath of packageJsonPaths) {
        const pkg = JSON.parse(
            readFileSync(new URL(relativePath, repositoryRoot), 'utf8'),
        );

        if (pkg.version !== expected) {
            mismatches.push(
                `${pkg.name}@${pkg.version} (expected ${expected})`,
            );
        }
    }

    if (mismatches.length > 0) {
        throw new Error(
            `Publishable package versions are not in lockstep:\n${mismatches.join('\n')}`,
        );
    }
};

/** Reject dependencies captured from the surrounding Verbb workspace. */
const assertStandaloneLockfile = () => {
    const lockfile = JSON.parse(
        readFileSync(new URL('package-lock.json', repositoryRoot), 'utf8'),
    );
    const invalid = [];

    for (const [path, entry] of Object.entries(lockfile.packages ?? {})) {
        if (path.startsWith('../') || entry.resolved?.startsWith('../')) {
            invalid.push(
                `${path || '<root>'}: ${entry.resolved ?? 'outside repository'}`,
            );
        }

        const pluginKitPackage = path.match(
            /(?:^|\/)node_modules\/(@verbb\/plugin-kit-[^/]+)$/,
        )?.[1];

        if (
            pluginKitPackage &&
            (entry.link ||
                !entry.version ||
                !entry.integrity ||
                !entry.resolved?.startsWith('https://registry.npmjs.org/'))
        ) {
            invalid.push(
                `${path}: ${pluginKitPackage} is not pinned to an npm registry artifact`,
            );
        }
    }

    if (invalid.length > 0) {
        throw new Error(
            'package-lock.json contains workspace-local or non-registry dependencies. ' +
                `Run npm run release:refresh-lockfile before review:\n${invalid.join('\n')}`,
        );
    }
};

/** Remove only dependency records that point outside the Formie repository. */
const removeExternalWorkspaceEntries = (lockfile) => {
    for (const [path, entry] of Object.entries(lockfile.packages ?? {})) {
        const pluginKitLink =
            path.match(/(?:^|\/)node_modules\/@verbb\/plugin-kit-[^/]+$/) &&
            entry.link;

        if (
            path.startsWith('../') ||
            entry.resolved?.startsWith('../') ||
            pluginKitLink
        ) {
            delete lockfile.packages[path];
        }
    }
};

const workspaceManifestPaths = () => {
    const manifests = [
        'src/web/assets/cp/package.json',
        'src/web/assets/frontend/package.json',
    ];

    for (const entry of readdirSync(new URL('packages/', repositoryRoot), {
        withFileTypes: true,
    })) {
        if (entry.isDirectory()) {
            const relative = `packages/${entry.name}/package.json`;

            if (existsSync(new URL(relative, repositoryRoot))) {
                manifests.push(relative);
            }
        }
    }

    return manifests;
};

/**
 * Resolve only from manifests and the existing lock in an isolated directory.
 * This prevents npm from discovering sibling Plugin Kit workspaces or retaining
 * their symlinks from the developer's installed node_modules tree.
 */
const refreshLockfile = () => {
    const root = fileURLToPath(repositoryRoot);
    const temporaryRoot = mkdtempSync(join(tmpdir(), 'formie-release-lock-'));

    try {
        copyFileSync(
            join(root, 'package.json'),
            join(temporaryRoot, 'package.json'),
        );
        copyFileSync(
            join(root, 'package-lock.json'),
            join(temporaryRoot, 'package-lock.json'),
        );

        for (const relative of workspaceManifestPaths()) {
            const destination = join(temporaryRoot, relative);
            mkdirSync(dirname(destination), { recursive: true });
            copyFileSync(join(root, relative), destination);
        }

        const temporaryLockfile = join(temporaryRoot, 'package-lock.json');
        const input = JSON.parse(readFileSync(temporaryLockfile, 'utf8'));
        removeExternalWorkspaceEntries(input);
        writeFileSync(
            temporaryLockfile,
            `${JSON.stringify(input, null, 2)}\n`,
            'utf8',
        );

        run(
            'npm',
            [
                'install',
                '--package-lock-only',
                '--ignore-scripts',
                '--legacy-peer-deps',
                '--registry=https://registry.npmjs.org/',
                '--@verbb:registry=https://registry.npmjs.org/',
            ],
            { cwd: temporaryRoot },
        );

        const resolved = JSON.parse(readFileSync(temporaryLockfile, 'utf8'));
        removeExternalWorkspaceEntries(resolved);
        writeFileSync(
            temporaryLockfile,
            `${JSON.stringify(resolved, null, 2)}\n`,
            'utf8',
        );
        copyFileSync(temporaryLockfile, join(root, 'package-lock.json'));
    } finally {
        rmSync(temporaryRoot, { recursive: true, force: true });
    }
};

const releaseDate = () => new Date().toISOString().slice(0, 10);
const changelogUrl = (dir) => new URL(`${dir}/CHANGELOG.md`, repositoryRoot);
const readChangelog = (fileUrl) =>
    readFileSync(fileUrl, 'utf8').replace(/\r\n/g, '\n');

const writeChangelog = (fileUrl, content) => {
    writeFileSync(fileUrl, `${content.replace(/\n+$/, '')}\n`, 'utf8');
};

const extractUnreleasedBody = (content) => {
    const match = content.match(/^## Unreleased\n([\s\S]*?)(?=^## )/m);
    return match ? match[1] : null;
};

const hasMeaningfulChangelogBody = (body) =>
    body?.split('\n').some((line) => {
        const trimmed = line.trim();
        return /^(-|\*|\d+\.)\s+\S/.test(trimmed) || /^###\s+\S/.test(trimmed);
    }) ?? false;

const lockstepBody = (packageName) =>
    '### Changed\n' +
    `- Released alongside the other \`@verbb/formie-*\` packages to keep versions aligned (${packageName}).\n`;

const finalizeChangelogContent = (content, version, date, packageName) => {
    const normalized = content.replace(/\r\n/g, '\n');

    if (!normalized.startsWith('# Changelog\n')) {
        throw new Error(
            `${packageName} CHANGELOG.md must start with "# Changelog".`,
        );
    }

    const unreleasedBody = extractUnreleasedBody(normalized);
    const releaseBody = hasMeaningfulChangelogBody(unreleasedBody)
        ? unreleasedBody.trim()
        : lockstepBody(packageName).trim();
    const previousSections = normalized
        .replace(/^# Changelog\n\n/, '')
        .replace(/^## Unreleased\n[\s\S]*?(?=^## )/m, '')
        .trimStart();

    return `# Changelog\n\n## Unreleased\n\n## ${version} - ${date}\n\n${releaseBody}\n\n${previousSections}`.trimEnd();
};

const syncInternalDependencies = (version) => {
    for (const relativePath of internalDependencyManifests) {
        const fileUrl = new URL(relativePath, repositoryRoot);
        const pkg = JSON.parse(readFileSync(fileUrl, 'utf8'));
        let changed = false;

        for (const depType of [
            'dependencies',
            'devDependencies',
            'peerDependencies',
        ]) {
            const deps = pkg[depType];

            if (!deps) {
                continue;
            }

            for (const packageName of packages) {
                if (deps[packageName] && deps[packageName] !== version) {
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
    for (const packageName of packages) {
        console.log(`::manager-package::${packageName}::build`);
        run('npm', ['run', 'build', '-w', packageName]);
    }
};

const packDryRunAll = () => {
    for (const packageName of packages) {
        console.log(`::manager-package::${packageName}::pack`);
        run('npm', ['pack', '--dry-run', '-w', packageName]);
    }
};

if (checkLockfileOnly) {
    assertStandaloneLockfile();
    console.log(
        'package-lock.json is standalone and uses npm registry artifacts for Plugin Kit.',
    );
    process.exit(0);
}

if (refreshLockfileOnly) {
    ensureCleanLockfile();
    refreshLockfile();
    assertStandaloneLockfile();
    console.log(
        'Refreshed package-lock.json without workspace-local dependencies. Review the generated diff.',
    );
    process.exit(0);
}

if (!dryRun) {
    ensureCleanWorkingTree();
} else {
    console.log(
        'Dry run: skipping clean-tree check and version/changelog writes.',
    );
}

const changelogContents = Object.fromEntries(
    packageDirs.map((dir) => [dir, readChangelog(changelogUrl(dir))]),
);
const dirsWithEntries = packageDirs.filter((dir) =>
    hasMeaningfulChangelogBody(extractUnreleasedBody(changelogContents[dir])),
);

if (dirsWithEntries.length === 0) {
    throw new Error(
        'Add entries under ## Unreleased in at least one Formie package CHANGELOG.md first.',
    );
}

assertLockstepVersions();
assertStandaloneLockfile();
console.log(
    `Unreleased changelog entries found in: ${dirsWithEntries.join(', ')}`,
);

buildPackages();
packDryRunAll();

if (dryRun) {
    console.log(
        'Dry run complete. No version changes or publication occurred.',
    );
    process.exit(0);
}

const generatedStatus = output('git', ['status', '--porcelain']);

if (generatedStatus) {
    throw new Error(
        `Build or pack regenerated repository files. Review them before preparing:\n${generatedStatus}`,
    );
}

const currentVersion = packageVersion();

for (const packageName of packages) {
    run('npm', ['version', bump, '-w', packageName, '--no-git-tag-version']);
}

const version = packageVersion();

if (version === currentVersion) {
    throw new Error(
        `Version did not change after ${bump} bump (still ${currentVersion}).`,
    );
}

assertLockstepVersions();
syncInternalDependencies(version);
refreshLockfile();
assertStandaloneLockfile();

const date = releaseDate();

for (const dir of packageDirs) {
    const packageName = `@verbb/${dir.split('/').at(-1)}`;
    writeChangelog(
        changelogUrl(dir),
        finalizeChangelogContent(
            changelogContents[dir],
            version,
            date,
            packageName,
        ),
    );
}

console.log(
    `Prepared Formie packages ${currentVersion} → ${version}. Nothing was published or committed.`,
);
console.log(
    'Review the version, dependency, lockfile, and changelog changes before committing and pushing.',
);
