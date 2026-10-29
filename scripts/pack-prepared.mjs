#!/usr/bin/env node
/** Pack reviewed builds without changing source versions, changelogs or locks. */
import { execFileSync } from 'node:child_process';
import { mkdirSync, mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import {
    assertPackedManifest,
    assertReviewedCheckout,
    releasePlan,
} from './package-release.mjs';

export function packPrepared(plan, run = execFileSync) {
    assertReviewedCheckout(plan, run);
    const staging = mkdtempSync(join(tmpdir(), 'formie-package-release-'));

    try {
        mkdirSync(plan.tarballDir, { recursive: true });

        for (const pkg of plan.packages) {
            // Start with npm's real publishable file set, excluding workspace
            // dependencies and private build inputs. Only the manifest changes.
            const original = JSON.parse(
                run(
                    'npm',
                    [
                        'pack',
                        join(plan.root, pkg.dir),
                        '--ignore-scripts',
                        '--json',
                        '--pack-destination',
                        staging,
                    ],
                    { cwd: plan.root, encoding: 'utf8' },
                ),
            )[0];
            const unpacked = mkdtempSync(join(staging, 'package-'));
            run('tar', [
                '-xf',
                join(staging, original.filename),
                '-C',
                unpacked,
            ]);
            writeFileSync(
                join(unpacked, 'package/package.json'),
                `${JSON.stringify(pkg.packed, null, 2)}\n`,
            );
            run(
                'npm',
                [
                    'pack',
                    join(unpacked, 'package'),
                    '--ignore-scripts',
                    '--pack-destination',
                    plan.tarballDir,
                ],
                { cwd: staging, stdio: 'inherit' },
            );
            assertPackedManifest(pkg, run);
        }

        assertReviewedCheckout(plan, run);
    } finally {
        rmSync(staging, { recursive: true, force: true });
    }

    console.log(
        `Prepared ${plan.version} for npm's ${plan.channel} channel. Nothing was published.`,
    );
}

if (
    process.argv[1] &&
    resolve(process.argv[1]) === fileURLToPath(import.meta.url)
) {
    packPrepared(
        releasePlan(
            fileURLToPath(new URL('../', import.meta.url)),
            process.env,
        ),
    );
}
