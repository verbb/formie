import { Alert } from '@verbb/plugin-kit-react/components';

function IntegrationErrorMessage({ error, className = '' }) {
    if (!error) {
        return null;
    }

    return (
        <Alert
            variant="error"
            size="sm"
            heading={error.heading}
            announce="assertive"
            detailsLabel={Craft.t('formie', 'Show details')}
            copyLabel={Craft.t('formie', 'Copy error details')}
            copyable={Boolean(error.traceAsString)}
            className={className}
        >
            {error.text}
            {error.traceAsString && (
                <pre slot="details">{error.traceAsString.replace(/<br\s*\/?>/gi, '\n')}</pre>
            )}
        </Alert>
    );
}

export { IntegrationErrorMessage };
