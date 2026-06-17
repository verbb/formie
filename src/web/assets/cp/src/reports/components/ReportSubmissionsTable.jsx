import { Button } from '@verbb/plugin-kit-react/components';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCaretDown, faCaretUp } from '@fortawesome/pro-solid-svg-icons';
import { faChevronLeft, faChevronRight } from '@fortawesome/pro-regular-svg-icons';

import { ReportTableCell } from '@reports/components/ReportTableCell';

const SORTABLE_HANDLES = ['dateCreated', 'dateUpdated', 'id', 'title', 'status', 'formName'];

const getSortableHandle = (column) => {
    if (column.type !== 'attribute') {
        return null;
    }

    return SORTABLE_HANDLES.includes(column.handle) ? column.handle : null;
};

const formatPaginationLabel = (rangeStart, rangeEnd, total) => {
    if (!total) {
        return Craft.t('formie', '0 submissions');
    }

    if (total === 1) {
        return Craft.t('formie', '{start}–{end} of {total} submission', {
            start: rangeStart,
            end: rangeEnd,
            total,
        });
    }

    return Craft.t('formie', '{start}–{end} of {total} submissions', {
        start: rangeStart,
        end: rangeEnd,
        total,
    });
};

export function ReportSubmissionsTable({
    columns = [],
    rows = [],
    sort,
    loading = false,
    pagination,
    onSortChange,
    onPageChange,
}) {
    const handleHeaderClick = (column) => {
        const handle = getSortableHandle(column);

        if (!handle || !onSortChange) {
            return;
        }

        if (sort?.handle === handle) {
            onSortChange(handle, sort.dir === 'asc' ? 'desc' : 'asc');
            return;
        }

        onSortChange(handle, 'desc');
    };

    const rangeStart = pagination?.total
        ? ((pagination.page - 1) * pagination.limit) + 1
        : 0;
    const rangeEnd = pagination
        ? Math.min(pagination.page * pagination.limit, pagination.total)
        : 0;

    return (
        <div className="formie-report-table-wrap">
            <div className="overflow-x-auto">
                <table className="formie-report-table">
                    <thead>
                        <tr>
                            {columns.map((column) => {
                                const sortHandle = getSortableHandle(column);
                                const isActive = sortHandle && sort?.handle === sortHandle;
                                const isSortable = Boolean(sortHandle);

                                return (
                                    <th
                                        key={column.id}
                                        className={[
                                            isSortable ? 'is-sortable' : '',
                                            isActive ? 'is-sorted' : '',
                                        ].filter(Boolean).join(' ')}
                                        onClick={isSortable ? () => { handleHeaderClick(column); } : undefined}
                                    >
                                        <span className="inline-flex items-center gap-1">
                                            {column.header}
                                            {isActive ? (
                                                <FontAwesomeIcon
                                                    icon={sort.dir === 'asc' ? faCaretUp : faCaretDown}
                                                    className="size-3 opacity-70"
                                                />
                                            ) : null}
                                        </span>
                                    </th>
                                );
                            })}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length ? rows.map((row) => (
                            <tr key={row.id}>
                                {row.cells.map((cell, index) => (
                                    <td key={`${row.id}-${columns[index]?.id || index}`}>
                                        <ReportTableCell cell={cell} />
                                    </td>
                                ))}
                            </tr>
                        )) : (
                            <tr>
                                <td
                                    colSpan={Math.max(columns.length, 1)}
                                    className="formie-report-table-empty"
                                >
                                    {loading
                                        ? '\u00a0'
                                        : Craft.t('formie', 'No submissions match this report yet.')}
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            {pagination ? (
                <div className="formie-report-table-footer">
                    <div className="formie-report-table-pagination">
                        <Button
                            type="button"
                            variant="outline"
                            size="xs"
                            className="h-7 w-7 p-0"
                            disabled={loading || pagination.page <= 1}
                            onClick={() => { onPageChange(pagination.page - 1); }}
                        >
                            <FontAwesomeIcon icon={faChevronLeft} className="size-3" />
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="xs"
                            className="h-7 w-7 p-0"
                            disabled={loading || pagination.page >= pagination.totalPages}
                            onClick={() => { onPageChange(pagination.page + 1); }}
                        >
                            <FontAwesomeIcon icon={faChevronRight} className="size-3" />
                        </Button>
                    </div>
                    <div>
                        {formatPaginationLabel(rangeStart, rangeEnd, pagination.total)}
                    </div>
                </div>
            ) : null}
        </div>
    );
}
