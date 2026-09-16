import { defineConfig } from '@playwright/test';
export default defineConfig({
    testDir: '.', testMatch: '*.spec.ts', workers: 1, fullyParallel: false,
    retries: 0, timeout: 30000,
    use: { baseURL: 'https://formie-react-tests.ddev.site', ignoreHTTPSErrors: true, trace: 'retain-on-failure' },
    reporter: [['list'], ['html', { outputFolder: '../../.cache/verbb-tests/browser-report', open: 'never' }]],
});
