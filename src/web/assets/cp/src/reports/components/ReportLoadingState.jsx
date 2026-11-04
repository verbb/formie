export function ReportLoadingState({ chartEnabled = true }) {
    return (
        <div
            className="formie-report-loading space-y-4"
            role="status"
            aria-label={Craft.t('formie', 'Loading report…')}
        >
            <div
                className="flex flex-wrap items-center gap-2 rounded-lg border border-gray-200 bg-white p-2"
                aria-hidden="true"
            >
                <div className="h-[var(--pk-control-height-default)] min-w-[220px] flex-1 rounded bg-gray-100" />
                <div className="flex flex-wrap items-center gap-2">
                    <div className="h-[var(--pk-control-height-default)] w-32 rounded bg-gray-100" />
                    <div className="h-[var(--pk-control-height-default)] w-32 rounded bg-gray-100" />
                </div>
                <div className="h-[var(--pk-control-height-default)] w-16 rounded bg-gray-100" />
                <div className="h-[var(--pk-control-height-default)] w-20 rounded bg-gray-100" />
            </div>
            <div
                className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"
                aria-hidden="true"
            >
                {[0, 1, 2, 3].map((key) => {
                    return (
                        <div
                            key={key}
                            className="rounded-lg border border-gray-200 bg-white p-4"
                        >
                            <div className="h-4 w-28 rounded bg-gray-100" />
                            <div className="mt-3 h-7 w-16 rounded bg-gray-100" />
                        </div>
                    );
                })}
            </div>
            {chartEnabled ? (
                <div
                    className="h-64 rounded-lg border border-gray-200 bg-gray-50"
                    aria-hidden="true"
                />
            ) : null}
            <div
                className="rounded-lg border border-gray-200 bg-white"
                aria-hidden="true"
            >
                <div className="h-10 border-b border-gray-200 bg-gray-100" />
                {[0, 1, 2, 3, 4].map((key) => {
                    return (
                        <div
                            key={key}
                            className="flex gap-8 border-b border-gray-100 p-4 last:border-0"
                        >
                            <div className="h-4 w-48 rounded bg-gray-100" />
                            <div className="h-4 w-32 rounded bg-gray-100" />
                            <div className="h-4 w-24 rounded bg-gray-100" />
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
