import { useCallback, useMemo, useRef, useState } from 'react';

import {
    Chart as ChartJS,
    CategoryScale,
    Filler,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';
import { Line } from 'react-chartjs-2';

import { ReportChartLegendItem } from '@reports/components/ReportChartLegendItem';
import { ReportChartTooltip } from '@reports/components/ReportChartTooltip';
import { useReportChartTooltip } from '@reports/hooks/useReportChartTooltip';

ChartJS.register(
    CategoryScale,
    Filler,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Tooltip,
);

Tooltip.positioners.cursor = function cursorPosition(_items, eventPosition) {
    return eventPosition;
};

const CHART_AXIS_COLOR = '#6B7280';

const CHART_SERIES = [
    {
        key: 'complete',
        label: Craft.t('formie', 'Complete'),
        color: '#4299E1',
    },
    {
        key: 'incomplete',
        label: Craft.t('formie', 'Incomplete'),
        color: '#A0AEC0',
    },
    {
        key: 'spam',
        label: Craft.t('formie', 'Spam'),
        color: '#ED8936',
    },
];

const hexToRgba = (hex, alpha) => {
    const normalized = hex.replace('#', '');
    const value = Number.parseInt(normalized, 16);
    const red = (value >> 16) & 255;
    const green = (value >> 8) & 255;
    const blue = value & 255;

    return `rgba(${red}, ${green}, ${blue}, ${alpha})`;
};

const formatChartDate = (value, style = 'label') => {
    if (!value) {
        return '';
    }

    const date = new Date(`${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    if (style === 'tooltip') {
        return date.toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        });
    }

    return date.toLocaleDateString(undefined, {
        month: 'numeric',
        day: 'numeric',
    });
};

const formatChartValue = (value) => {
    const count = Number(value) || 0;

    if (count === 1) {
        return Craft.t('formie', '1 submission');
    }

    return Craft.t('formie', '{count} submissions', { count });
};

const createAreaDataset = (series, values) => {
    return {
        label: series.label,
        data: values,
        borderColor: series.color,
        pointBackgroundColor: series.color,
        pointHoverBackgroundColor: series.color,
        borderWidth: 3,
        pointHoverBorderColor: '#fff',
        pointHoverBorderWidth: 2,
        pointHoverRadius: 6,
        pointRadius: 2.5,
        pointHitRadius: 6,
        fill: true,
        tension: 0.4,
        backgroundColor(context) {
            const { chart } = context;
            const { ctx, chartArea } = chart;

            if (!chartArea) {
                return hexToRgba(series.color, 0.12);
            }

            const gradient = ctx.createLinearGradient(
                0,
                chartArea.top,
                0,
                chartArea.bottom,
            );
            gradient.addColorStop(0, hexToRgba(series.color, 0.22));
            gradient.addColorStop(1, hexToRgba(series.color, 0));

            return gradient;
        },
    };
};

export function ReportSubmissionsChart({ data = [] }) {
    const chartRef = useRef(null);
    const [hiddenSeries, setHiddenSeries] = useState(() => new Set());
    const {
        tooltipRef,
        tooltipVisible,
        tooltipData,
        tooltipPos,
        customTooltip,
    } = useReportChartTooltip();

    const toggleSeries = useCallback((index) => {
        const chart = chartRef.current?.chart ?? chartRef.current;

        if (!chart) {
            return;
        }

        const isVisible = chart.isDatasetVisible(index);
        chart.setDatasetVisibility(index, !isVisible);
        chart.update();

        const key = CHART_SERIES[index]?.key;

        if (!key) {
            return;
        }

        setHiddenSeries((current) => {
            const next = new Set(current);

            if (isVisible) {
                next.add(key);
            } else {
                next.delete(key);
            }

            return next;
        });
    }, []);

    const chartConfig = useMemo(() => {
        const labels = data.map((item) => item.date);

        return {
            data: {
                labels,
                datasets: CHART_SERIES.map((series) => {
                    return createAreaDataset(
                        series,
                        data.map((item) => item[series.key] ?? 0),
                    );
                }),
            },
            options: {
                animation: false,
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: -8,
                        right: 2,
                        bottom: 0,
                    },
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        enabled: false,
                        mode: 'index',
                        intersect: false,
                        position: 'cursor',
                        external: customTooltip,
                    },
                },
                elements: {
                    line: { tension: 0.4, borderWidth: 3 },
                    point: { radius: 2.5, hitRadius: 6, hoverRadius: 6 },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        border: {
                            display: false,
                        },
                        ticks: {
                            mirror: true,
                            precision: 0,
                            maxTicksLimit: 6,
                            z: 1,
                            color: CHART_AXIS_COLOR,
                            textStrokeColor: '#fff',
                            textStrokeWidth: 3,
                            padding: 5,
                            font: {
                                size: 10,
                            },
                            callback(value, index) {
                                if (index === 0) {
                                    return '';
                                }

                                return value;
                            },
                        },
                        grid: {
                            display: false,
                            drawTicks: false,
                            drawBorder: false,
                        },
                    },
                    x: {
                        border: {
                            display: false,
                        },
                        ticks: {
                            mirror: true,
                            autoSkip: true,
                            maxTicksLimit: 8,
                            color: CHART_AXIS_COLOR,
                            textStrokeColor: '#fff',
                            textStrokeWidth: 3,
                            padding: 0,
                            font: {
                                size: 10,
                            },
                            callback(value, index, ticks) {
                                if (index === 0 || index === ticks.length - 1) {
                                    return '';
                                }

                                return formatChartDate(this.getLabelForValue(value));
                            },
                        },
                        grid: {
                            display: false,
                        },
                    },
                },
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
            },
        };
    }, [customTooltip, data]);

    if (!data.length) {
        return (
            <p className="m-0 text-sm text-gray-500">
                {Craft.t('formie', 'No submissions match this report yet.')}
            </p>
        );
    }

    return (
        <div className="w-full pt-2">
            <div className="relative h-[252px] w-full">
                <Line ref={chartRef} {...chartConfig} />

                <ReportChartTooltip
                    ref={tooltipRef}
                    data={tooltipData}
                    position={tooltipPos}
                    visibility={tooltipVisible}
                    formatDate={(value) => formatChartDate(value, 'tooltip')}
                    formatValue={formatChartValue}
                />
            </div>

            <div className="flex flex-wrap items-center justify-center gap-x-3.5 gap-y-1 pt-2">
                {CHART_SERIES.map((series, index) => (
                    <ReportChartLegendItem
                        key={series.key}
                        color={series.color}
                        label={series.label}
                        hidden={hiddenSeries.has(series.key)}
                        onClick={() => toggleSeries(index)}
                    />
                ))}
            </div>
        </div>
    );
}
