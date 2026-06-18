const sleep = (ms) => new Promise((resolve) => {
    window.setTimeout(resolve, ms);
});

const kickCraftQueue = () => {
    if (typeof Craft === 'undefined') {
        return;
    }

    if (Craft.cp && typeof Craft.cp.runQueue === 'function') {
        Craft.cp.runQueue();
        return;
    }

    if (Craft.runQueueAutomatically !== false && typeof Craft.sendActionRequest === 'function') {
        Craft.sendActionRequest('POST', 'queue/run').catch(() => {});
    }
};

const triggerBlobDownload = (blob, filename) => {
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename || 'export.csv';
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
};

const getFilenameFromDisposition = (contentDisposition, fallback) => {
    if (!contentDisposition) {
        return fallback;
    }

    const match = /filename\*=UTF-8''([^;]+)|filename="([^"]+)"|filename=([^;]+)/i.exec(contentDisposition);

    if (!match) {
        return fallback;
    }

    const value = match[1] || match[2] || match[3];

    try {
        return decodeURIComponent(value);
    } catch {
        return value;
    }
};

export const pollExportStatus = async (statusUrl, { intervalMs = 2000, maxAttempts = 300 } = {}) => {
    for (let attempt = 0; attempt < maxAttempts; attempt += 1) {
        kickCraftQueue();

        const response = await fetch(statusUrl, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(Craft.t('formie', 'Couldn’t check export status.'));
        }

        const payload = await response.json();

        if (payload.status === 'ready') {
            return payload;
        }

        if (payload.status === 'failed') {
            throw new Error(payload.error || Craft.t('formie', 'Export failed.'));
        }

        await sleep(intervalMs);
    }

    throw new Error(Craft.t('formie', 'Export timed out.'));
};

export const runReportExport = async ({
    exportUrl,
    csrfTokenName,
    csrfTokenValue,
    format,
    viewerColumns,
    search,
    sort,
    sortDir,
    dateRange,
    onQueued,
    onReady,
}) => {
    const body = new FormData();
    body.append(csrfTokenName, csrfTokenValue);
    body.append('columns', JSON.stringify(viewerColumns.filter((column) => column.enabled !== false)));
    body.append('search', search || '');
    body.append('sort', sort.handle);
    body.append('sortDir', sort.dir);
    body.append('startDate', dateRange.startDate || '');
    body.append('endDate', dateRange.endDate || '');
    body.append('format', format);

    const response = await fetch(`${exportUrl}?format=${encodeURIComponent(format)}`, {
        method: 'POST',
        body,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    const contentType = response.headers.get('content-type') || '';

    if (contentType.includes('application/json')) {
        const payload = await response.json();

        if (!response.ok || payload.error) {
            throw new Error(payload.error || Craft.t('formie', 'Export failed.'));
        }

        if (payload.queued) {
            if (typeof onQueued === 'function') {
                onQueued(payload);
            }

            kickCraftQueue();

            const ready = await pollExportStatus(payload.statusUrl);

            if (!ready.downloadUrl) {
                throw new Error(Craft.t('formie', 'Export download URL missing.'));
            }

            if (typeof onReady === 'function') {
                onReady(ready);
            }

            window.location.href = ready.downloadUrl;

            return {
                queued: true,
                rowCount: payload.rowCount,
            };
        }
    }

    if (!response.ok) {
        throw new Error(Craft.t('formie', 'Export failed.'));
    }

    const blob = await response.blob();
    const filename = getFilenameFromDisposition(
        response.headers.get('content-disposition'),
        `report-export.${format}`,
    );

    triggerBlobDownload(blob, filename);

    return {
        queued: false,
    };
};
