const QuestionnaireResultsBar = ({ option }) => {
    const percentage = option.percentage ?? 0;

    return (
        <div className="space-y-1">
            <div className="flex items-baseline justify-between gap-3 text-xs">
                <span className="font-medium text-gray-900">{option.label}</span>
                <span className="shrink-0 text-muted">
                    {Craft.t('formie', '{count} ({percentage}%)', {
                        count: option.count ?? 0,
                        percentage,
                    })}
                </span>
            </div>
            <div className="h-1.5 overflow-hidden rounded-full bg-gray-100">
                <div
                    className="h-full rounded-full bg-blue-500 transition-all duration-300"
                    style={{ width: `${Math.min(100, Math.max(0, percentage))}%` }}
                />
            </div>
        </div>
    );
};

export { QuestionnaireResultsBar };
