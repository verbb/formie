import { useEffect, useState } from 'react';
import { cn } from '@verbb/plugin-kit-react/utils';
import { Icon } from '@verbb/plugin-kit-react/components';

function IntegrationErrorMessage({ error, className = '' }) {
    const [showDetails, setShowDetails] = useState(false);

    useEffect(() => {
        setShowDetails(false);
    }, [error]);

    if (!error) {
        return null;
    }

    return (
        <div className={cn('text-error', className)}>
            <div className="flex items-center gap-1">
                <Icon icon="triangle-exclamation" className="size-3 shrink-0" />
                <p className="text-sm font-semibold">{error.heading}</p>
            </div>

            <p className="mt-1 text-xs font-mono">{error.text}</p>

            {error.traceAsString && (
                <div className="w-full">
                    <button
                        type="button"
                        className="mt-1 flex cursor-pointer items-center gap-1 text-xs"
                        onClick={() => {
                            setShowDetails((value) => {
                                return !value;
                            });
                        }}
                    >
                        <Icon
                            icon="chevron-right"
                            className={cn('size-3 transition-transform', showDetails && 'rotate-90')}
                        />
                        {Craft.t('formie', showDetails ? 'Hide details' : 'Show details')}
                    </button>

                    {showDetails ? (
                        <div
                            className="mt-2 max-h-[180px] overflow-auto rounded-md bg-slate-50 p-2 text-left text-xs"
                            dangerouslySetInnerHTML={{ __html: error.traceAsString }}
                        />
                    ) : null}
                </div>
            )}
        </div>
    );
}

export { IntegrationErrorMessage };
