import { AppErrorBoundary } from '@utils';

const DefaultsErrorBoundary = ({ children }) => {
    return (
        <AppErrorBoundary
            consoleLabel="Formie Defaults crashed:"
            title={Craft.t('formie', 'Something went wrong')}
            message={Craft.t('formie', 'The defaults settings failed to load. Please refresh the page or try again.')}
            detailsLabel={Craft.t('formie', 'Show error details')}
            reloadLabel={Craft.t('formie', 'Reload')}
        >
            {children}
        </AppErrorBoundary>
    );
};

export { DefaultsErrorBoundary };
