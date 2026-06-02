import { AppErrorBoundary } from '@utils';

const FormBuilderErrorBoundary = ({ children }) => {
    return (
        <AppErrorBoundary
            consoleLabel="FormBuilder crashed:"
            title={Craft.t('formie', 'Something went wrong')}
            message={Craft.t('formie', 'The form builder failed to load. Please refresh the page or try again.')}
            detailsLabel={Craft.t('formie', 'Show error details')}
            reloadLabel={Craft.t('formie', 'Reload')}
            containerClassName="flex flex-1 items-center justify-center py-12"
        >
            {children}
        </AppErrorBoundary>
    );
};

export { FormBuilderErrorBoundary };
