import { AppErrorBoundary } from '@verbb/plugin-kit-react/utils';

const FormBuilderErrorBoundary = ({ children }) => {
    return (
        <AppErrorBoundary
            consoleLabel="FormBuilder crashed:"
            heading={Craft.t('formie', 'Something went wrong')}
            message={Craft.t('formie', 'The form builder failed to load. Please refresh the page or try again.')}
            detailsLabel={Craft.t('formie', 'Show error details')}
            reloadLabel={Craft.t('formie', 'Reload')}
            size="lg"
            className="flex-1 py-12"
        >
            {children}
        </AppErrorBoundary>
    );
};

export { FormBuilderErrorBoundary };
