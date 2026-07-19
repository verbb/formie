import { Fragment, createContext, useContext, useEffect, useMemo } from 'react';
import { Icon, Tab, TabPanel, Tabs } from '@verbb/plugin-kit-react/components';
import { cn } from '@verbb/plugin-kit-react/utils';

import useUrlRouter from '@form-builder/hooks/useUrlRouter';
import { useFormBuilderTabErrors } from '@form-builder/builder/useFormBuilderTabErrors';
import { useFormBuilderApp } from '@form-builder/contexts/FormBuilderAppContext';
import { useFormValues } from '@form-builder/hooks/useFormTools';
import { formHasQuestionnaireFields } from '@form-builder/utils/questionnaireFields';
import useAppStore from '@form-builder/hooks/useAppStore';

// Tab error map is owned by Formie (schema prefixes) — not a kit tabs concern.
const TabErrorsContext = createContext({});

export const useTabErrors = () => {
    return useContext(TabErrorsContext);
};

function FormBuilderTabs({
    children, schema, schemaNode, className, ...props
}) {
    const { activeTab } = useFormBuilderApp();
    const router = useUrlRouter();
    const tabErrors = useFormBuilderTabErrors(schemaNode ?? schema);
    const formValues = useFormValues();
    const getFieldTypeByType = useAppStore((state) => { return state.getFieldTypeByType; });
    const hasQuestionnaireFields = formHasQuestionnaireFields(formValues, getFieldTypeByType);

    // Only pane-level FormBuilderTabTrigger values. Nested ModalTabs emit pk-change after
    // their own select; kit pk-tabs ignores foreign selects, but fail-closed here too.
    const paneTabValues = useMemo(() => {
        const root = schemaNode ?? schema;
        const values = new Set();

        const visit = (nodes) => {
            if (!Array.isArray(nodes)) {
                return;
            }

            nodes.forEach((node) => {
                if (!node || typeof node !== 'object') {
                    return;
                }

                if (node.$cmp === 'FormBuilderTabTrigger' && typeof node.props?.value === 'string') {
                    values.add(node.props.value);
                }

                if (Array.isArray(node.children)) {
                    visit(node.children);
                } else if (node.children && typeof node.children === 'object') {
                    visit(Object.values(node.children));
                }
            });
        };

        if (Array.isArray(root?.children)) {
            visit(root.children);
        } else if (root?.children && typeof root.children === 'object') {
            visit(Object.values(root.children));
        } else if (Array.isArray(root)) {
            visit(root);
        }

        return values;
    }, [schema, schemaNode]);

    useEffect(() => {
        if (activeTab === 'results' && !hasQuestionnaireFields) {
            router.navigateToTab('fields');
        }
    }, [activeTab, hasQuestionnaireFields, router]);

    return (
        <TabErrorsContext.Provider value={tabErrors}>
            <Tabs
                variant="pane"
                value={activeTab || ''}
                // Stock kit has no routing — Formie's URL sync stays on the wrapper.
                // Nested controls also emit `pk-change`; Tabs facade filters host-only events,
                // and we still require a known pane tab value before navigating.
                onPkChange={(event) => {
                    const next = event.detail?.value;
                    if (typeof next !== 'string' || !next) {
                        return;
                    }

                    // Fail closed: if we could not collect pane triggers, never route
                    // from a nested ModalTabs value (e.g. "advanced") that would hide
                    // every panel while leaving the URL on /notifications.
                    if (paneTabValues.size === 0 || !paneTabValues.has(next)) {
                        return;
                    }

                    router.navigateToTab(next);
                }}
                className={cn(
                    'form-builder-section-tabs',
                    // min-w-0 for flex shrink; do not use overflow-hidden here —
                    // it clips pane frame + selected-tab elevation shadows.
                    'min-w-0',
                    className,
                )}
                {...props}
            >
                {children}
            </Tabs>
        </TabErrorsContext.Provider>
    );
}

FormBuilderTabs.usesSchemaNode = true;

/**
 * Schema still nests triggers under FormBuilderTabList. pk-tabs only discovers
 * `pk-tab` nodes assigned to `slot="nav"`, so this must not introduce a DOM wrapper.
 */
function FormBuilderTabList({ children }) {
    return <Fragment>{children}</Fragment>;
}

function FormBuilderTabTrigger({
    children, value, schema, className, ...props
}) {
    const tabErrors = useTabErrors();
    const hasErrors = Boolean(tabErrors[value]);

    return (
        <Tab
            value={value}
            data-has-errors={hasErrors ? 'true' : undefined}
            className={cn(
                'form-builder-section-tab',
                'shrink-0',
                className,
            )}
            {...props}
            // Must win over any schema props — pk-tabs only indexes nav-slot tabs.
            slot="nav"
        >
            {/* Keep icon in the default label slot — status slot :has(::slotted(*)) is flaky. */}
            <span className="inline-flex items-center gap-1">
                {children}
                {hasErrors ? (
                    <Icon icon="triangle-exclamation" className="block size-3" />
                ) : null}
            </span>
        </Tab>
    );
}

function FormBuilderTabContent({
    padded, children, className, ...props
}) {
    return (
        <TabPanel className={className} {...props}>
            <div className={cn(
                padded !== false && 'gap-4 p-6 max-[640px]:p-4',
                'flex flex-col',
                'min-h-full',
            )}
            >
                {children}
            </div>
        </TabPanel>
    );
}

export {
    FormBuilderTabs,
    FormBuilderTabList,
    FormBuilderTabTrigger,
    FormBuilderTabContent,
};
