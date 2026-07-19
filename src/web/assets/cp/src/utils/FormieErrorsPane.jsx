import { cn } from '@verbb/plugin-kit-react/utils';
import { Icon } from '@verbb/plugin-kit-react/components';

function FormieErrorsPane({
    errors = [],
    className,
    headingId = 'formie-errors-heading',
}) {
    const errorList = (errors || []).filter(Boolean);

    if (!errorList.length) {
        return null;
    }

    const heading = errorList.length === 1
        ? Craft.t('formie', 'Found {num} error', { num: errorList.length })
        : Craft.t('formie', 'Found {num} errors', { num: errorList.length });

    return (
        <div
            className={cn(
                'rounded-lg shadow-[0_0_0_1px_var(--color-gray-200),_0_2px_12px_rgb(205_216_228_/_50%)] bg-white/50 py-4 px-6',
                className,
            )}
            tabIndex={-1}
            role="alert"
            aria-live="assertive"
            aria-atomic="true"
            aria-labelledby={headingId}
        >
            <div className="flex items-center gap-1.5">
                <Icon icon="triangle-exclamation" className="size-3 text-rose-600" />

                <h2 id={headingId} className="text-base font-bold">
                    {heading}
                </h2>
            </div>

            <ul className="mt-1 space-y-1 pl-4.5 text-sm text-gray-700">
                {errorList.map((error, index) => {
                    return (
                        <li
                            key={`${index}-${error}`}
                            className="list-disc font-mono text-xs"
                            dangerouslySetInnerHTML={{ __html: error }}
                        />
                    );
                })}
            </ul>
        </div>
    );
}

export { FormieErrorsPane };
