#!/usr/bin/env node
/** Publish only validated Formie tarballs from the reviewed GitHub Actions checkout. */
import { execFileSync } from 'node:child_process';
import { resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import {
    assertPackedManifest,
    assertReviewedCheckout,
    releasePlan,
} from './package-release.mjs';

export function publishPrepared(plan, run = execFileSync) {
    assertReviewedCheckout(plan, run);

    // Validate every package before the first registry write. This catches a
    // partial or mismatched artifact set without publishing half a release.
    for (const pkg of plan.packages) {
        assertPackedManifest(pkg, run);
    }

    const output = (command, args) =>
        run(command, args, {
            cwd: plan.root,
            encoding: 'utf8',
            stdio: ['ignore', 'pipe', 'pipe'],
        }).trim();

    const registry = 'https://registry.npmjs.org/';
    const publishedVersion = (packageName) => {
        try {
            return output('npm', [
                'view',
                `${packageName}@${plan.version}`,
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
                `Could not check ${packageName}@${plan.version} on npm; publication stopped.`,
                {
                    cause: error,
                },
            );
        }
    };

    for (const {
        manifest: { name: packageName },
        tarball,
    } of plan.packages) {
        const existing = publishedVersion(packageName);

        if (existing && existing !== plan.version) {
            throw new Error(
                `${packageName} registry returned unexpected version ${existing}.`,
            );
        }

        if (existing === plan.version) {
            console.log(
                `Already visible on npm: ${packageName}@${plan.version}`,
            );
            continue;
        }

        console.log(
            `Publishing ${packageName}@${plan.version} from ${plan.sha}...`,
        );
        run(
            'npm',
            [
                'publish',
                tarball,
                '--tag',
                plan.channel,
                '--access',
                'public',
                '--ignore-scripts',
                '--registry',
                registry,
            ],
            { stdio: 'inherit' },
        );
        console.log(
            `Accepted by npm: ${packageName}@${plan.version}. Registry scanning may delay installation.`,
        );
    }

    console.log(
        `All five Formie packages were accepted by npm at ${plan.version} on ${plan.channel}.`,
    );
}

if (
    process.argv[1] &&
    resolve(process.argv[1]) === fileURLToPath(import.meta.url)
) {
    publishPrepared(
        releasePlan(
            fileURLToPath(new URL('../', import.meta.url)),
            process.env,
        ),
    );
}
