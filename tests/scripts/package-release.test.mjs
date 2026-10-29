import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import {
    existsSync,
    mkdirSync,
    mkdtempSync,
    readFileSync,
    rmSync,
    writeFileSync,
} from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { test } from 'node:test';
import {
    assertReviewedCheckout,
    packageDirs,
    releasePlan,
} from '../../scripts/package-release.mjs';
import { packPrepared } from '../../scripts/pack-prepared.mjs';
import { publishPrepared } from '../../scripts/publish-prepared.mjs';

const sha = '0123456789abcdef0123456789abcdef01234567';

function fixture(t, channel = 'next') {
    const root = mkdtempSync(join(tmpdir(), 'formie-release-test-'));
    t.after(() => rmSync(root, { recursive: true, force: true }));

    for (const [index, dir] of packageDirs.entries()) {
        mkdirSync(join(root, dir, 'dist'), { recursive: true });
        writeFileSync(
            join(root, dir, 'dist/index.js'),
            'export const answer = 42;\n',
        );
        writeFileSync(join(root, dir, 'private.txt'), 'Must not ship.');
        writeFileSync(
            join(root, dir, 'CHANGELOG.md'),
            '# Changelog\n\n## Unreleased\n\n### Fixed\n- Pending fix.\n\n## 1.0.16 - 2026-10-28\n',
        );
        writeFileSync(
            join(root, dir, 'package.json'),
            JSON.stringify({
                name: `@verbb/${dir.split('/').at(-1)}`,
                version: '1.0.16',
                type: 'module',
                main: './dist/index.js',
                files: ['dist', 'CHANGELOG.md'],
                repository: {
                    type: 'git',
                    url: 'git+https://github.com/verbb/formie.git',
                },
                publishConfig: { access: 'public' },
                dependencies: index
                    ? { '@verbb/formie-core': '1.0.16', external: '^2.0.0' }
                    : {},
                peerDependencies: { react: '^19.0.0' },
                scripts: { prepack: 'node -e "process.exit(99)"' },
            }),
        );
    }

    const env = {
        GITHUB_ACTIONS: 'true',
        GITHUB_REF: 'refs/heads/craft-5',
        RELEASE_SHA: sha,
        RELEASE_CHANNEL: channel,
        RELEASE_VERSION: channel === 'latest' ? '1.0.16' : '',
        GITHUB_RUN_NUMBER: '42',
        PACKAGE_TARBALL_DIR: join(root, 'tarballs'),
    };
    const changeManifest = (index, change) => {
        const file = join(root, packageDirs[index], 'package.json');
        const manifest = JSON.parse(readFileSync(file, 'utf8'));
        change(manifest);
        writeFileSync(file, JSON.stringify(manifest));
    };
    return { root, env, changeManifest, plan: () => releasePlan(root, env) };
}

function mockCommands(plan, options = {}) {
    const calls = [];
    const run = (command, args) => {
        calls.push([command, ...args]);
        if (command === 'git') return args[0] === 'rev-parse' ? sha : '';
        if (command === 'tar') {
            const pkg = plan.packages.find((pkg) => pkg.tarball === args[1]);
            return JSON.stringify(
                options.alter ? options.alter(pkg) : pkg.packed,
            );
        }
        if (command === 'npm' && args[0] === 'view') {
            if (options.error)
                throw Object.assign(new Error('registry error'), {
                    stderr: options.error,
                });
            if (options.existing?.includes(args[1])) return plan.version;
            throw Object.assign(new Error('missing'), {
                stderr: 'E404 Not Found',
            });
        }
        if (command === 'npm' && args[0] === 'publish') return '';
        throw new Error(`Unexpected command: ${command} ${args}`);
    };
    return { calls, run };
}

test('next uses a reproducible prerelease without consuming stable versions', (t) => {
    const f = fixture(t);
    const plan = f.plan();
    assert.equal(plan.version, '1.0.17-next.42.g0123456789ab');
    assert.equal(
        releasePlan(f.root, { ...f.env, GITHUB_RUN_ATTEMPT: '2' }).version,
        plan.version,
    );
    assert.equal(
        releasePlan(f.root, { ...f.env, GITHUB_RUN_NUMBER: '43' }).version,
        '1.0.17-next.43.g0123456789ab',
    );
    assert.equal(
        JSON.parse(readFileSync(join(f.root, packageDirs[0], 'package.json')))
            .version,
        '1.0.16',
    );
    assert.match(
        readFileSync(join(f.root, packageDirs[0], 'CHANGELOG.md'), 'utf8'),
        /## Unreleased/,
    );
});

test('next pins every internal dependency kind and preserves external ranges', (t) => {
    const f = fixture(t);
    f.changeManifest(1, (pkg) => {
        for (const kind of [
            'dependencies',
            'devDependencies',
            'peerDependencies',
            'optionalDependencies',
        ]) {
            pkg[kind] = { ...pkg[kind], '@verbb/formie-core': '^1.0.0' };
        }
    });
    const plan = f.plan();
    for (const pkg of plan.packages) {
        assert.equal(pkg.packed.version, plan.version);
        assert.equal(pkg.packed.publishConfig.tag, 'next');
        assert.equal(pkg.packed.gitHead, sha);
        assert.equal(pkg.packed.peerDependencies.react, '^19.0.0');
    }
    for (const kind of [
        'dependencies',
        'devDependencies',
        'peerDependencies',
        'optionalDependencies',
    ]) {
        assert.equal(
            plan.packages[1].packed[kind]['@verbb/formie-core'],
            plan.version,
        );
    }
    assert.equal(plan.packages[1].packed.dependencies.external, '^2.0.0');
});

for (const [key, value, message] of [
    ['GITHUB_ACTIONS', 'false', /restricted/],
    ['GITHUB_REF', 'refs/heads/other', /restricted/],
    ['RELEASE_SHA', 'HEAD', /full Git/],
    ['RELEASE_CHANNEL', 'beta', /latest or next/],
    ['RELEASE_VERSION', '1.0.17', /Leave RELEASE_VERSION empty/],
    ['GITHUB_RUN_NUMBER', '0', /positive/],
    ['GITHUB_RUN_NUMBER', '01', /positive/],
    ['PACKAGE_TARBALL_DIR', './artifacts', /absolute/],
]) {
    test(`rejects invalid next ${key}=${value}`, (t) => {
        const f = fixture(t);
        assert.throws(
            () => releasePlan(f.root, { ...f.env, [key]: value }),
            message,
        );
    });
}

test('latest retains exact version and finalized changelog requirements', (t) => {
    const f = fixture(t, 'latest');
    assert.equal(f.plan().version, '1.0.16');
    assert.equal(
        releasePlan(f.root, { ...f.env, RELEASE_CHANNEL: undefined }).channel,
        'latest',
    );
    for (const version of ['', '1.0.17', '1.0.17-next.1', '01.0.16']) {
        assert.throws(
            () => releasePlan(f.root, { ...f.env, RELEASE_VERSION: version }),
            /exact stable/,
        );
    }
    writeFileSync(
        join(f.root, packageDirs[4], 'CHANGELOG.md'),
        '# Changelog\n\n## Unreleased\n',
    );
    assert.throws(() => f.plan(), /no finalized/);
    assert.doesNotThrow(() =>
        releasePlan(f.root, {
            ...f.env,
            RELEASE_CHANNEL: 'next',
            RELEASE_VERSION: '',
        }),
    );
});

test('rejects mixed source versions and package identities', (t) => {
    const f = fixture(t);
    f.changeManifest(4, (pkg) => {
        pkg.version = '1.0.15';
    });
    assert.throws(() => f.plan(), /lockstep/);
    f.changeManifest(4, (pkg) => {
        pkg.version = '1.0.16';
        pkg.name = '@other/package';
    });
    assert.throws(() => f.plan(), /identity/);
});

test('latest rejects inconsistent internal dependencies and conflicting tags', (t) => {
    const f = fixture(t, 'latest');
    f.changeManifest(1, (pkg) => {
        pkg.dependencies['@verbb/formie-core'] = '1.0.15';
    });
    assert.throws(() => f.plan(), /must pin/);
    f.changeManifest(1, (pkg) => {
        pkg.dependencies['@verbb/formie-core'] = '1.0.16';
        pkg.publishConfig.tag = 'next';
    });
    assert.throws(() => f.plan(), /conflicts/);
});

test('rejects dirty or mismatched checkouts before packing or publishing', (t) => {
    const plan = fixture(t).plan();
    assert.throws(
        () => assertReviewedCheckout(plan, () => 'wrong'),
        /clean and match/,
    );
    assert.throws(
        () =>
            packPrepared(plan, (cmd, args) =>
                args[0] === 'rev-parse' ? sha : ' M file',
            ),
        /clean and match/,
    );
    assert.equal(existsSync(plan.tarballDir), false);
    assert.throws(
        () => publishPrepared(plan, () => 'wrong'),
        /clean and match/,
    );
});

for (const channel of ['latest', 'next']) {
    test(`${channel} publication explicitly selects its tag in dependency order`, (t) => {
        const plan = fixture(t, channel).plan();
        const mock = mockCommands(plan);
        publishPrepared(plan, mock.run);
        const writes = mock.calls.filter((call) => call[1] === 'publish');
        assert.equal(writes.length, 5);
        assert.deepEqual(
            writes.map((call) => call[2]),
            plan.packages.map((pkg) => pkg.tarball),
        );
        for (const call of writes) {
            assert.equal(call[call.indexOf('--tag') + 1], channel);
            assert.ok(call.includes('--ignore-scripts'));
            assert.equal(
                call[call.indexOf('--registry') + 1],
                'https://registry.npmjs.org/',
            );
        }
    });

    test(`${channel} real npm tarballs retain built code and source manifests`, (t) => {
        const f = fixture(t, channel);
        const plan = f.plan();
        const before = packageDirs.map((dir) =>
            ['package.json', 'CHANGELOG.md'].map((file) =>
                readFileSync(join(f.root, dir, file), 'utf8'),
            ),
        );
        // Only Git is simulated: npm and tar exercise the real packing boundary.
        packPrepared(plan, (command, args, options) => {
            if (command === 'git') return args[0] === 'rev-parse' ? sha : '';
            return execFileSync(command, args, {
                ...options,
                stdio: 'pipe',
                env: {
                    ...process.env,
                    npm_config_cache: join(f.root, 'npm-cache'),
                },
            });
        });
        for (const [index, pkg] of plan.packages.entries()) {
            const contents = execFileSync('tar', ['-tf', pkg.tarball], {
                encoding: 'utf8',
            });
            assert.match(contents, /package\/dist\/index.js/);
            assert.doesNotMatch(contents, /private.txt|node_modules/);
            assert.equal(
                execFileSync(
                    'tar',
                    ['-xOf', pkg.tarball, 'package/dist/index.js'],
                    { encoding: 'utf8' },
                ),
                'export const answer = 42;\n',
            );
            assert.deepEqual(
                ['package.json', 'CHANGELOG.md'].map((file) =>
                    readFileSync(join(f.root, pkg.dir, file), 'utf8'),
                ),
                before[index],
            );
        }
    });
}

test('a malformed final tarball blocks all registry operations', (t) => {
    const plan = fixture(t).plan();
    const mock = mockCommands(plan, {
        alter: (pkg) =>
            pkg === plan.packages[4]
                ? {
                      ...pkg.packed,
                      dependencies: { '@verbb/formie-core': '1.0.16' },
                  }
                : pkg.packed,
    });
    assert.throws(() => publishPrepared(plan, mock.run), /metadata/);
    assert.equal(
        mock.calls.some((call) => call[0] === 'npm'),
        false,
    );
});

test('rerunning a partial publication skips accepted versions', (t) => {
    const plan = fixture(t).plan();
    const mock = mockCommands(plan, {
        existing: plan.packages
            .slice(0, 2)
            .map((pkg) => `${pkg.manifest.name}@${plan.version}`),
    });
    publishPrepared(plan, mock.run);
    assert.equal(mock.calls.filter((call) => call[1] === 'publish').length, 3);
});

test('registry authentication failures stop publication', (t) => {
    const plan = fixture(t).plan();
    const mock = mockCommands(plan, { error: 'E401 Unauthorized' });
    assert.throws(() => publishPrepared(plan, mock.run), /publication stopped/);
    assert.equal(
        mock.calls.some((call) => call[1] === 'publish'),
        false,
    );
});
