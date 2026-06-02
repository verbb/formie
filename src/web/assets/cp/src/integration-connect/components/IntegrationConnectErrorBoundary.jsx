import { AppErrorBoundary } from '@utils';

const IntegrationConnectErrorBoundary = ({ children }) => {
    return (
        <AppErrorBoundary
            consoleLabel="IntegrationConnect crashed:"
            title={Craft.t('formie', 'Something went wrong')}
            message={Craft.t('formie', 'The integration connection panel failed to load. Please refresh the page and try again.')}
            detailsLabel={Craft.t('formie', 'Show error details')}
            reloadLabel={Craft.t('formie', 'Reload')}
            containerClassName="py-2 -mx-3"
            contentClassName="text-left"
        >
            {children}
        </AppErrorBoundary>
    );
};

export { IntegrationConnectErrorBoundary };
