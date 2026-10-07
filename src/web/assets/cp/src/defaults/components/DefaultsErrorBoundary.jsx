import { AppErrorBoundary } from '@verbb/plugin-kit-react/utils';

const DefaultsErrorBoundary = ({ children }) => {
    return (
        <AppErrorBoundary
            consoleLabel="Formie Defaults crashed:"
            heading={Craft.t('formie', 'Something went wrong')}
            message={Craft.t('formie', 'The defaults settings failed to load. Please refresh the page or try again.')}
            detailsLabel={Craft.t('formie', 'Show error details')}
            reloadLabel={Craft.t('formie', 'Reload')}
            size="lg"
        >
            {children}
        </AppErrorBoundary>
    );
};

export { DefaultsErrorBoundary };
