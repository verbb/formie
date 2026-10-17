import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { runReportExport } from './reportExport';

describe('runReportExport', () => {
    let link;
    let createObjectURL;
    const options = {
        exportUrl: '/export',
        csrfTokenName: 'csrf',
        csrfTokenValue: 'test-token',
        viewerColumns: [],
        sort: { handle: 'dateCreated', dir: 'desc' },
        dateRange: {},
    };

    beforeEach(() => {
        link = { click: vi.fn(), remove: vi.fn() };
        createObjectURL = vi.fn(() => {
            return 'blob:export';
        });
        vi.stubGlobal('document', {
            createElement: vi.fn(() => {
                return link;
            }),
            body: { appendChild: vi.fn() },
        });
        vi.stubGlobal('window', {
            URL: { createObjectURL, revokeObjectURL: vi.fn() },
            location: { href: '' },
        });
        vi.stubGlobal('Craft', { t: (_category, message) => {
            return message;
        } });
        vi.stubGlobal('fetch', vi.fn());
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it.each([
        ['json', 'application/json', '[{"Customer":"Zoë","Score":0}]'],
        ['csv', 'text/csv', 'Customer,Score\nZoë,0'],
    ])('downloads a synchronous %s attachment without consuming its body twice', async (format, contentType, content) => {
        fetch.mockResolvedValue(new Response(content, {
            headers: {
                'Content-Type': contentType,
                'Content-Disposition': `attachment; filename="report.${format}"`,
            },
        }));

        await expect(runReportExport({ ...options, format })).resolves.toEqual({ queued: false });
        expect(link.download).toBe(`report.${format}`);
        expect(link.click).toHaveBeenCalledOnce();
        expect(await createObjectURL.mock.calls[0][0].text()).toBe(content);
    });

    it('polls queued JSON exports and navigates to the completed download', async () => {
        fetch.mockResolvedValueOnce(Response.json({ queued: true, rowCount: 1000, statusUrl: '/status' }));
        fetch.mockResolvedValueOnce(Response.json({ status: 'ready', downloadUrl: '/download' }));
        const onQueued = vi.fn();
        const onReady = vi.fn();

        await expect(runReportExport({ ...options, format: 'json', onQueued, onReady })).resolves.toEqual({ queued: true, rowCount: 1000 });
        expect(onQueued).toHaveBeenCalledOnce();
        expect(onReady).toHaveBeenCalledOnce();
        expect(window.location.href).toBe('/download');
        expect(link.click).not.toHaveBeenCalled();
    });

    it('reports JSON API errors instead of downloading them', async () => {
        fetch.mockResolvedValue(Response.json({ error: 'Export denied.' }, { status: 403 }));

        await expect(runReportExport({ ...options, format: 'json' })).rejects.toThrow('Export denied.');
        expect(link.click).not.toHaveBeenCalled();
    });
});
