import { Alert } from '@verbb/plugin-kit-react/components';

function FormieErrorsPane({
    errors = [],
    className,
}) {
    const errorList = (errors || []).filter(Boolean);

    if (!errorList.length) {
        return null;
    }

    const heading = errorList.length === 1
        ? Craft.t('formie', 'Found {num} error', { num: errorList.length })
        : Craft.t('formie', 'Found {num} errors', { num: errorList.length });

    return (
        <Alert
            variant="error"
            heading={heading}
            announce="assertive"
            className={className}
            tabIndex={-1}
        >
            <ul className="m-0 space-y-1 pl-4.5">
                {errorList.map((error, index) => {
                    return (
                        <li
                            key={`${index}-${error}`}
                            className="list-disc font-mono text-xs"
                        >
                            {error}
                        </li>
                    );
                })}
            </ul>
        </Alert>
    );
}

export { FormieErrorsPane };
