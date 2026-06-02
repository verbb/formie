import {
    PaneTabs,
    PaneTabsList,
    PaneTabsTrigger,
    PaneTabsContent,
} from '@verbb/plugin-kit-react/components';

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faTriangleExclamation } from '@fortawesome/pro-solid-svg-icons';

import useUrlRouter from '@form-builder/hooks/useUrlRouter';
import { useFormBuilderTabErrors } from '@form-builder/builder/useFormBuilderTabErrors';
import { useFormBuilderApp } from '@form-builder/contexts/FormBuilderAppContext';

import { cn } from '@verbb/plugin-kit-react/utils';

import {
    createContext, useContext,
} from 'react';

// Create a context to pass tab error information down
const TabErrorsContext = createContext({});

export const useTabErrors = () => {
    return useContext(TabErrorsContext);
};

function FormBuilderTabs({
    children, schema, schemaNode, className, ...props
}) {
    // Get global tab state from store
    const { activeTab } = useFormBuilderApp();
    const router = useUrlRouter();
    const tabErrors = useFormBuilderTabErrors(schemaNode ?? schema);

    return (
        <TabErrorsContext.Provider value={tabErrors}>
            <PaneTabs
                value={activeTab}
                onValueChange={router.navigateToTab}
                className={cn(
                    'min-w-0 overflow-hidden',
                    className,
                )}
                {...props}
            >
                {children}
            </PaneTabs>
        </TabErrorsContext.Provider>
    );
}

FormBuilderTabs.usesSchemaNode = true;

function FormBuilderTabList({ children, className, ...props }) {
    return (
        <PaneTabsList
            className={cn(
                'min-w-0 max-w-full flex-nowrap overflow-x-auto overflow-y-hidden',
                className,
            )}
            {...props}
        >
            {children}
        </PaneTabsList>
    );
}

function FormBuilderTabTrigger({
    children, value, schema, className, ...props
}) {
    // Get tab errors from context
    const tabErrors = useTabErrors();


    // Check if this specific tab has errors
    const hasErrors = Boolean(tabErrors[value]);

    return <PaneTabsTrigger
        value={value}
        data-has-errors={hasErrors}
        className={cn(
            'form-builder-section-tab',
            'flex shrink-0 items-center gap-1',
            hasErrors && 'text-error data-[active]:text-error',
            className,
        )}
        {...props}
    >
        {children}

        {hasErrors && (
            <FontAwesomeIcon icon={faTriangleExclamation} className="block size-3" />
        )}
    </PaneTabsTrigger>;
}

function FormBuilderTabContent({ padded, children, ...props }) {
    return (
        <PaneTabsContent {...props}>
            <div className={cn(
                padded !== false && 'gap-4 p-6 max-[640px]:p-4',
                'flex flex-col',
                // 'gap-4',
                'min-h-full',
            )}>{children}</div>
        </PaneTabsContent>
    );
}

export {
    FormBuilderTabs,
    FormBuilderTabList,
    FormBuilderTabTrigger,
    FormBuilderTabContent,
};
