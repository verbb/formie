import { AppErrorBoundary } from '@verbb/plugin-kit-react/utils';

const NewFormErrorBoundary = ({ children }) => {
    return (
        <AppErrorBoundary
            consoleLabel="NewForm crashed:"
            heading={Craft.t('formie', 'Something went wrong')}
            message={Craft.t('formie', 'The new form page failed to load. Please refresh the page or try again.')}
            detailsLabel={Craft.t('formie', 'Show error details')}
            reloadLabel={Craft.t('formie', 'Reload')}
            size="lg"
            className="min-h-[calc(100vh-80px)] py-12"
        >
            {children}
        </AppErrorBoundary>
    );
};

export { NewFormErrorBoundary };
