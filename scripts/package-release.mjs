/** Shared identity and manifest rules for stable and early-access npm artifacts. */
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { isAbsolute, join } from 'node:path';
import { isDeepStrictEqual } from 'node:util';

export const packageDirs = [
    'packages/formie-core',
    'packages/formie-browser',
    'packages/formie-react',
    'packages/formie-vue',
    'packages/formie-web-components',
];

const packageNames = packageDirs.map(
    (dir) => `@verbb/${dir.split('/').at(-1)}`,
);
const stableVersion = /^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)$/;

export function releasePlan(root, env) {
    if (
        env.GITHUB_ACTIONS !== 'true' ||
        env.GITHUB_REF !== 'refs/heads/craft-5'
    ) {
        throw new Error(
            'Prepared publication is restricted to the craft-5 GitHub Actions workflow.',
        );
    }

    const channel = env.RELEASE_CHANNEL || 'latest';
    const sha = env.RELEASE_SHA;
    const tarballDir = env.PACKAGE_TARBALL_DIR;

    if (!['latest', 'next'].includes(channel)) {
        throw new Error('RELEASE_CHANNEL must be latest or next.');
    }

    if (!/^[0-9a-f]{40}$/.test(sha ?? '')) {
        throw new Error('RELEASE_SHA must be a full Git commit SHA.');
    }

    if (!tarballDir || !isAbsolute(tarballDir)) {
        throw new Error('PACKAGE_TARBALL_DIR must be an absolute path.');
    }

    const packages = packageDirs.map((dir, index) => {
        const manifest = JSON.parse(
            readFileSync(join(root, dir, 'package.json'), 'utf8'),
        );

        if (
            manifest.name !== packageNames[index] ||
            manifest.repository?.url !==
                'git+https://github.com/verbb/formie.git'
        ) {
            throw new Error(`${dir} identity does not match verbb/formie.`);
        }

        return { dir, manifest };
    });
    const baseVersion = packages[0].manifest.version;
    const match = stableVersion.exec(baseVersion);

    if (
        !match ||
        packages.some(({ manifest }) => manifest.version !== baseVersion)
    ) {
        throw new Error(
            'Source package versions must be stable and in lockstep.',
        );
    }

    let version = env.RELEASE_VERSION;

    if (channel === 'next') {
        if (version) {
            throw new Error(
                'Leave RELEASE_VERSION empty for next; the workflow generates it.',
            );
        }

        if (!/^[1-9]\d*$/.test(env.GITHUB_RUN_NUMBER ?? '')) {
            throw new Error(
                'A positive GITHUB_RUN_NUMBER is required for next.',
            );
        }

        // A workflow rerun retains its version; a new dispatch gets a new one.
        // Prefix the SHA so an all-numeric hash cannot become invalid semver.
        version = `${match[1]}.${match[2]}.${BigInt(match[3]) + 1n}-next.${env.GITHUB_RUN_NUMBER}.g${sha.slice(0, 12)}`;
    } else if (!stableVersion.test(version ?? '') || version !== baseVersion) {
        throw new Error(
            'RELEASE_VERSION must match the exact stable source package version.',
        );
    }

    for (const pkg of packages) {
        if (channel === 'latest') {
            const changelog = readFileSync(
                join(root, pkg.dir, 'CHANGELOG.md'),
                'utf8',
            );

            if (!changelog.includes(`## ${version} - `)) {
                throw new Error(
                    `${pkg.dir} has no finalized ${version} changelog entry.`,
                );
            }
        }

        pkg.packed = structuredClone(pkg.manifest);
        pkg.packed.version = version;

        for (const kind of [
            'dependencies',
            'devDependencies',
            'peerDependencies',
            'optionalDependencies',
        ]) {
            for (const name of packageNames) {
                if (pkg.packed[kind]?.[name]) {
                    if (
                        channel === 'latest' &&
                        pkg.packed[kind][name] !== version
                    ) {
                        throw new Error(
                            `${pkg.dir} must pin ${name} to ${version}.`,
                        );
                    }

                    pkg.packed[kind][name] = version;
                }
            }
        }

        if (channel === 'next') {
            pkg.packed.publishConfig = {
                ...pkg.packed.publishConfig,
                tag: 'next',
            };
            pkg.packed.gitHead = sha;
        } else if (
            pkg.packed.publishConfig?.tag &&
            pkg.packed.publishConfig.tag !== channel
        ) {
            throw new Error(
                `${pkg.dir} publishConfig.tag conflicts with ${channel}.`,
            );
        }

        pkg.tarball = join(
            tarballDir,
            `verbb-${pkg.dir.split('/').at(-1)}-${version}.tgz`,
        );
    }

    return { root, channel, sha, version, tarballDir, packages };
}

export function assertReviewedCheckout(plan, run = execFileSync) {
    const head = run('git', ['rev-parse', 'HEAD'], {
        cwd: plan.root,
        encoding: 'utf8',
    }).trim();
    const status = run('git', ['status', '--porcelain'], {
        cwd: plan.root,
        encoding: 'utf8',
    }).trim();

    if (head !== plan.sha || status) {
        throw new Error(
            'Checkout must be clean and match the reviewed RELEASE_SHA.',
        );
    }
}

export function assertPackedManifest(pkg, run = execFileSync) {
    const packed = JSON.parse(
        run('tar', ['-xOf', pkg.tarball, 'package/package.json'], {
            encoding: 'utf8',
        }),
    );

    if (!isDeepStrictEqual(packed, pkg.packed)) {
        throw new Error(
            `${pkg.tarball} metadata does not match the reviewed package manifest.`,
        );
    }
}
