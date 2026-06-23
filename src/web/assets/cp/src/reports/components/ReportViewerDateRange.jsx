import { DatePicker } from '@verbb/plugin-kit-react/components';

import {
    formatViewerEndDate,
    formatViewerStartDate,
    parseViewerDate,
} from '@reports/utils/reportViewerDates';

export function ReportViewerDateRange({
    startDate = null,
    endDate = null,
    onChange,
}) {
    const startValue = parseViewerDate(startDate);
    const endValue = parseViewerDate(endDate);

    const emitChange = (nextStartDate, nextEndDate) => {
        onChange?.({
            startDate: formatViewerStartDate(nextStartDate),
            endDate: formatViewerEndDate(nextEndDate),
        });
    };

    return (
        <div className="flex flex-wrap items-center gap-2">
            <DatePicker
                value={startValue}
                placeholder={Craft.t('formie', 'Start Date')}
                onValueChange={(nextDate) => {
                    emitChange(nextDate, endValue);
                }}
            />
            <DatePicker
                value={endValue}
                placeholder={Craft.t('formie', 'End Date')}
                onValueChange={(nextDate) => {
                    emitChange(startValue, nextDate);
                }}
            />
        </div>
    );
}
