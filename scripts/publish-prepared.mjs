#!/usr/bin/env node
/** Publish only validated Formie tarballs from the reviewed GitHub Actions checkout. */
import { execFileSync } from 'node:child_process';
import { existsSync, readFileSync } from 'node:fs';
import { isAbsolute, join } from 'node:path';

const packageDirs = [
    'packages/formie-core',
    'packages/formie-browser',
    'packages/formie-react',
    'packages/formie-vue',
    'packages/formie-web-components',
];

const expectedSha = process.env.RELEASE_SHA;
const expectedVersion = process.env.RELEASE_VERSION;
const tarballDir = process.env.PACKAGE_TARBALL_DIR;

if (
    process.env.GITHUB_ACTIONS !== 'true' ||
    process.env.GITHUB_REF !== 'refs/heads/beta'
) {
    throw new Error(
        'Prepared publication is restricted to the beta-branch GitHub Actions workflow.',
    );
}

if (
    !/^[0-9a-f]{40}$/.test(expectedSha ?? '') ||
    !/^\d+\.\d+\.\d+$/.test(expectedVersion ?? '')
) {
    throw new Error(
        'RELEASE_SHA must be a full Git commit SHA and RELEASE_VERSION must be an exact version.',
    );
}

if (!tarballDir || !isAbsolute(tarballDir)) {
    throw new Error(
        'PACKAGE_TARBALL_DIR must be the absolute path to validated tarballs.',
    );
}

const output = (command, args) =>
    execFileSync(command, args, {
        encoding: 'utf8',
        stdio: ['ignore', 'pipe', 'pipe'],
    }).trim();

const head = output('git', ['rev-parse', 'HEAD']);

if (head !== expectedSha) {
    throw new Error(
        `Checkout is ${head}, not the reviewed release commit ${expectedSha}.`,
    );
}

const status = output('git', ['status', '--porcelain']);

if (status) {
    throw new Error(`Repository files changed before publication:\n${status}`);
}

const tarballs = new Map();

for (const dir of packageDirs) {
    const slug = dir.split('/').at(-1);
    const packageName = `@verbb/${slug}`;
    const packageRoot = new URL(`../${dir}/`, import.meta.url);
    const pkg = JSON.parse(
        readFileSync(new URL('package.json', packageRoot), 'utf8'),
    );
    const changelog = readFileSync(
        new URL('CHANGELOG.md', packageRoot),
        'utf8',
    );
    const tarball = join(tarballDir, `verbb-${slug}-${expectedVersion}.tgz`);

    if (pkg.name !== packageName || pkg.version !== expectedVersion) {
        throw new Error(
            `${dir} is not the expected lockstep package/version ${expectedVersion}.`,
        );
    }

    if (pkg.repository?.url !== 'git+https://github.com/verbb/formie.git') {
        throw new Error(`${dir} repository.url does not match verbb/formie.`);
    }

    if (!changelog.includes(`## ${expectedVersion} - `)) {
        throw new Error(
            `${dir} has no finalized ${expectedVersion} changelog entry.`,
        );
    }

    if (!existsSync(tarball)) {
        throw new Error(`Validated tarball is missing: ${tarball}`);
    }

    const packed = JSON.parse(
        execFileSync('tar', ['-xOf', tarball, 'package/package.json'], {
            encoding: 'utf8',
        }),
    );

    if (
        packed.name !== pkg.name ||
        packed.version !== pkg.version ||
        packed.repository?.url !== pkg.repository.url
    ) {
        throw new Error(
            `${tarball} metadata does not match the reviewed checkout.`,
        );
    }

    tarballs.set(packageName, tarball);
}

const registry = 'https://registry.npmjs.org/';
const publishedVersion = (packageName) => {
    try {
        return output('npm', [
            'view',
            `${packageName}@${expectedVersion}`,
            'version',
            '--registry',
            registry,
            '--prefer-online',
        ]);
    } catch (error) {
        const diagnostic = `${error.stderr ?? ''}\n${error.stdout ?? ''}`;

        if (/E404|404 Not Found/.test(diagnostic)) {
            return null;
        }

        throw new Error(
            `Could not check ${packageName}@${expectedVersion} on npm; publication stopped.`,
            {
                cause: error,
            },
        );
    }
};

for (const [packageName, tarball] of tarballs) {
    const existing = publishedVersion(packageName);

    if (existing && existing !== expectedVersion) {
        throw new Error(
            `${packageName} registry returned unexpected version ${existing}.`,
        );
    }

    if (existing === expectedVersion) {
        console.log(
            `Already visible on npm: ${packageName}@${expectedVersion}`,
        );
        continue;
    }

    console.log(`Publishing ${packageName}@${expectedVersion} from ${head}...`);
    execFileSync(
        'npm',
        [
            'publish',
            tarball,
            '--access',
            'public',
            '--ignore-scripts',
            '--registry',
            registry,
        ],
        { stdio: 'inherit' },
    );
    console.log(
        `Accepted by npm: ${packageName}@${expectedVersion}. Registry scanning may delay installation.`,
    );
}

console.log(
    `All five Formie packages were accepted by npm at ${expectedVersion}.`,
);
