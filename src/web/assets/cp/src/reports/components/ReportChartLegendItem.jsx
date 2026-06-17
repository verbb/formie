export function ReportChartLegendItem({ color, label, hidden = false, onClick }) {
    const interactive = typeof onClick === 'function';
    const className = [
        'flex items-center gap-1.5 text-[11px] leading-snug text-gray-600',
        hidden ? 'opacity-40' : '',
        interactive ? 'cursor-pointer border-0 bg-transparent p-0 hover:opacity-80' : '',
    ].filter(Boolean).join(' ');

    const content = (
        <>
            <span
                className="inline-block size-2 shrink-0 rounded-full"
                style={{ backgroundColor: color }}
            />
            <span className={hidden ? 'line-through' : undefined}>{label}</span>
        </>
    );

    if (interactive) {
        return (
            <button
                type="button"
                className={className}
                onClick={onClick}
                aria-pressed={!hidden}
            >
                {content}
            </button>
        );
    }

    return <div className={className}>{content}</div>;
}
