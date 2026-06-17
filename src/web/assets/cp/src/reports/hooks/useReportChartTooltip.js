import { useCallback, useRef, useState } from 'react';

export function useReportChartTooltip() {
    const tooltipRef = useRef(null);
    const [tooltipVisible, setTooltipVisible] = useState(false);
    const [tooltipData, setTooltipData] = useState(null);
    const [tooltipPos, setTooltipPos] = useState({ top: 0, left: 0 });

    const customTooltip = useCallback((context) => {
        if (context.tooltip.opacity === 0) {
            setTooltipVisible(false);
            return;
        }

        const { chart, tooltip } = context;
        const { canvas, chartArea } = chart;

        if (!canvas || !tooltipRef.current) {
            return;
        }

        setTooltipVisible(true);

        const tooltipWidth = tooltipRef.current.offsetWidth || 0;
        const tooltipHeight = tooltipRef.current.offsetHeight || 0;
        let left = tooltip.caretX;
        let top = tooltip.caretY;

        if (left + tooltipWidth > chartArea.right) {
            left = tooltip.caretX - tooltipWidth;

            if (left < chartArea.left) {
                left = chartArea.left;
            }
        }

        if (top + tooltipHeight > chartArea.bottom) {
            top = tooltip.caretY - tooltipHeight;

            if (top < chartArea.top) {
                top = chartArea.top;
            }
        }

        setTooltipPos({ top, left });
        setTooltipData({ tooltipModel: tooltip });
    }, []);

    return {
        tooltipRef,
        tooltipVisible,
        tooltipData,
        tooltipPos,
        customTooltip,
    };
}
