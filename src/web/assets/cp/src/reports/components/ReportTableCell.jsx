import { Button } from '@verbb/plugin-kit-react/components';

import {
    formatReportDateTime,
    formatReportTextCell,
} from '@reports/utils/reportTableCells';

export function ReportTableCell({ cell }) {
    if (!cell || typeof cell !== 'object') {
        return formatReportTextCell(cell);
    }

    if (cell.type === 'datetime') {
        return formatReportDateTime(cell.value) || '—';
    }

    if (cell.type === 'status') {
        const color = cell.color || 'green';
        const name = cell.name || '—';

        return (
            <span className="formie-report-table-status">
                <span className={`formie-report-table-status-dot status ${color}`} />
                <span>{name}</span>
            </span>
        );
    }

    if (cell.type === 'link') {
        const label = formatReportTextCell(cell.value);

        if (!cell.url || label === '—') {
            return label;
        }

        return (
            <Button href={cell.url} variant="link" className="font-normal">
                {label}
            </Button>
        );
    }

    return formatReportTextCell(cell.value);
}
