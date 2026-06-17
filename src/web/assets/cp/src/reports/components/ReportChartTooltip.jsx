import { forwardRef } from 'react';

import { ReportChartLegendItem } from '@reports/components/ReportChartLegendItem';

const getPointColor = (point) => {
    return point.dataset?.borderColor
        || point.labelColors?.borderColor
        || point.labelColors?.backgroundColor
        || '#6B7280';
};

const ReportChartTooltip = forwardRef(({ data, position, visibility, formatDate, formatValue }, ref) => {
    const dataPoints = data?.tooltipModel?.dataPoints || [];
    const metric = formatDate(dataPoints[0]?.label || '');
    const visiblePoints = dataPoints.filter((point) => {
        if (point.chart && !point.chart.isDatasetVisible(point.datasetIndex)) {
            return false;
        }

        return Number(point.raw ?? 0) > 0;
    });

    return (
        <div
            ref={ref}
            className="pointer-events-none absolute z-10 max-w-[220px] rounded px-2 py-1.5 text-[11px] leading-snug text-gray-600 shadow-sm ring-1 ring-gray-200/80 bg-white/95"
            style={{
                top: position?.top || 0,
                left: position?.left || 0,
                display: visibility ? 'block' : 'none',
            }}
        >
            <div className="mb-0.5 font-medium text-gray-700">{metric}</div>
            {visiblePoints.length ? (
                <div className="flex flex-col gap-0.5">
                    {visiblePoints.map((point) => (
                        <ReportChartLegendItem
                            key={point.datasetIndex}
                            color={getPointColor(point)}
                            label={`${point.dataset?.label}: ${formatValue(point.raw ?? 0)}`}
                        />
                    ))}
                </div>
            ) : (
                <div className="text-gray-500">
                    {Craft.t('formie', 'No submissions')}
                </div>
            )}
        </div>
    );
});

ReportChartTooltip.displayName = 'ReportChartTooltip';

export { ReportChartTooltip };
