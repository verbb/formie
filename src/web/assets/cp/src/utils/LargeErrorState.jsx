import { getErrorMessage } from '@verbb/plugin-kit-core';
import { StatePanel } from './StatePanel';

const LargeErrorState = ({
    error = null,
    title = null,
    message = null,
    detailsLabel = null,
    actionLabel = null,
    onAction = null,
    showDetails = true,
    containerClassName = 'flex flex-1 items-center justify-center py-12',
    contentClassName = 'flex w-[90%] max-w-[560px] flex-col items-center text-center',
}) => {
    const resolvedError = error ? getErrorMessage(error) : null;
    const resolvedTitle = title || resolvedError?.heading || Craft.t('formie', 'Something went wrong');
    const resolvedMessage = resolvedError?.text || message || Craft.t('formie', 'An error has occurred.');
    const resolvedDetailsLabel = detailsLabel || Craft.t('formie', 'Show error details');
    const traceAsString = resolvedError?.traceAsString || '';

    return (
        <StatePanel
            variant="error"
            title={resolvedTitle}
            message={resolvedMessage}
            containerClassName={containerClassName}
            contentClassName={contentClassName}
            primaryAction={actionLabel && onAction
                ? { label: actionLabel, onClick: onAction, variant: 'primary' }
                : null}
        >
            {showDetails && traceAsString ? (
                <details className="mb-4 w-full text-center text-xs text-rose-600">
                    <summary className="cursor-pointer">{resolvedDetailsLabel}</summary>

                    <div className="mt-2 whitespace-pre-wrap text-left">
                        <p className="mb-2">{resolvedError.heading}: {resolvedError.text}</p>
                        <div dangerouslySetInnerHTML={{ __html: traceAsString }} />
                    </div>
                </details>
            ) : null}
        </StatePanel>
    );
};

export { LargeErrorState };
