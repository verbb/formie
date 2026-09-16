import { test, expect } from '@playwright/test';

test('loads the shipped dashboard chart libraries and keeps tooltip labels as text', async ({ page }) => {
    const errors: string[] = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('/admin/login');
    await page.getByRole('textbox', { name: 'Username or Email', exact: true }).fill('admin');
    await page.locator('input[name="password"]').fill('testing-only-password');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await page.waitForURL(url => !url.pathname.endsWith('/login'));
    // Login already lands on the dashboard; another navigation cancels host requests.
    await expect(page).toHaveURL(url => (url.searchParams.get('p') ?? url.pathname).replace(/^\//, '') === 'admin/dashboard');
    await expect(page.locator('.fui-recent-submissions-container canvas')).toBeVisible();
    await expect.poll(() => page.evaluate(() => Object.keys((window as any).Chart?.instances ?? {}).length)).toBeGreaterThan(0);
    const chartState = await page.evaluate(() => {
        const globals = window as any;
        const chart: any = Object.values(globals.Chart.instances).find((item: any) => item.canvas.closest('.fui-recent-submissions-container'));
        const label = chart.data.labels[0];
        chart.options.tooltips.custom.call({ _chart: chart }, {
            opacity: 1, title: [label], body: [{ lines: [label] }],
            labelColors: [{ backgroundColor: '#4299E1', borderColor: '#4299E1' }],
            caretX: 10, caretY: 10, bodyFontSize: 12,
        });
        return { momentVersion: globals.moment.version, total: Number(chart.data.datasets[0].data[0]), injected: !!globals.widgetInjection };
    });
    expect(chartState).toEqual({ momentVersion: '2.30.1', total: 1, injected: false });
    await expect(page.locator('#chartjs-tooltip')).toContainText('<img src=x onerror="window.widgetInjection=true">');
    await expect(page.locator('#chartjs-tooltip img')).toHaveCount(0);
    expect(errors).toEqual([]);
});
