// CSS needs to be inlined for Shadow DOM usage
import formBuilderStyles from '@form-builder/css/style.css?inline';

const hmrData = import.meta.hot?.data?.formBuilder ?? {};
let hmrRoot = hmrData.root ?? null;
let hmrMountNode = hmrData.mountNode ?? null;
let hmrPortalContainer = hmrData.portalContainer ?? null;
let hmrSettings = hmrData.settings ?? null;

const persistHmrData = () => {
    if (import.meta.hot) {
        import.meta.hot.data.formBuilder = {
            root: hmrRoot,
            mountNode: hmrMountNode,
            portalContainer: hmrPortalContainer,
            settings: hmrSettings,
        };
    }
};

const isSettingsForCurrentPath = (settings) => {
    const baseUrl = settings?.baseUrl;
    if (!baseUrl) {
        return true;
    }

    try {
        const basePath = new URL(baseUrl, window.location.origin).pathname.replace(/\/+$/, '');
        const currentPath = window.location.pathname.replace(/\/+$/, '');

        if (!basePath || !currentPath) {
            return true;
        }

        return currentPath.startsWith(basePath) || basePath.startsWith(currentPath);
    } catch (error) {
        console.warn('Unable to validate FormBuilder settings path:', error);
        return true;
    }
};

// Accept HMR as per: https://vitejs.dev/guide/api-hmr.html
if (import.meta.hot) {
    import.meta.hot.accept(() => {
        if (hmrSettings && isSettingsForCurrentPath(hmrSettings)) {
            Craft.Formie?.FormBuilder?.(hmrSettings);
        } else if (hmrSettings) {
            console.warn('Skipping HMR FormBuilder re-init due to mismatched baseUrl/current path.', {
                baseUrl: hmrSettings.baseUrl,
                currentPath: window.location.pathname,
            });
        }
    });

    import.meta.hot.dispose(() => {
        persistHmrData();
    });
}

import useAppStore from '@form-builder/hooks/useAppStore';
import { initializeRouterState } from '@form-builder/hooks/useUrlRouter';
import { bootstrapShadowReactApp, ensureCraftNamespace, markContainerReady } from '@utils';

import { createElement, Fragment } from 'react';
import { createRoot } from 'react-dom/client';
import { registerFormComponents, registerFormFields } from '@verbb/plugin-kit-react/forms/registry';
import { CodeEditorField } from '@verbb/plugin-kit-react/forms/fields/CodeEditorField';

import { FormBuilder } from '@form-builder/components/FormBuilder';
import {
    FormBuilderTabs, FormBuilderTabList, FormBuilderTabTrigger, FormBuilderTabContent,
} from '@form-builder/components/FormBuilderTabs';
import { FormUsage } from '@form-builder/components/FormUsage';
import { FormMetaDetails } from '@form-builder/components/FormMetaDetails';
import { Notifications } from '@form-builder/components/Notifications';
import { NotificationPreview } from '@form-builder/components/NotificationPreview';
import { NotificationTest } from '@form-builder/components/NotificationTest';
import { Integrations } from '@form-builder/components/Integrations';
import { FieldBuilder } from '@form-builder/components/FieldBuilder';

import { PageConditionsField } from '@form-builder/fields/PageConditionsField';
import { NotificationRecipientsField } from '@form-builder/fields/NotificationRecipientsField';
import { NotificationConditionsField } from '@form-builder/fields/NotificationConditionsField';
import { IntegrationConditionsField } from '@form-builder/fields/IntegrationConditionsField';
import { FieldSelectField } from '@form-builder/fields/FieldSelectField';
import { FieldConditionsField } from '@form-builder/fields/FieldConditionsField';
import { NextButtonConditionsField } from '@form-builder/fields/NextButtonConditionsField';
import { StatusRuleConditionsField } from '@form-builder/fields/StatusRuleConditionsField';
import { RedirectRuleConditionsField } from '@form-builder/fields/RedirectRuleConditionsField';
import { StatusRulesField } from '@form-builder/fields/StatusRulesField';
import { RedirectRulesField } from '@form-builder/fields/RedirectRulesField';
import { NestedLayoutField } from '@form-builder/fields/NestedLayoutField';
import { IntegrationFieldMappingField } from '@form-builder/fields/IntegrationFieldMappingField';
import { IntegrationRefreshSelectField } from '@form-builder/fields/IntegrationRefreshSelectField';
import { IntegrationActionButtonField } from '@form-builder/fields/IntegrationActionButtonField';
import { PaymentProviderSettingsField } from '@form-builder/fields/PaymentProviderSettingsField';
import { FormieEditableTableField } from '@form-builder/fields/FormieEditableTableField';
import { FormieTableColumnsField } from '@form-builder/fields/FormieTableColumnsField';
import { FormieTableDefaultsField } from '@form-builder/fields/FormieTableDefaultsField';
import { FormieStaticTableField } from '@form-builder/fields/FormieStaticTableField';
import { OptionDynamicSettingsField } from '@form-builder/fields/OptionSourceSettingsField';
import { previewSchemaComponents } from '@form-builder/components/preview';
import { applyDevScenarios, shouldRenderDevToolbar } from '@form-builder/dev';
import { DevToolsToolbar } from '@form-builder/dev/DevToolsToolbar';

ensureCraftNamespace('Formie');

Craft.Formie.FormBuilder = function(settings) {
    if (!isSettingsForCurrentPath(settings)) {
        console.warn('Ignoring FormBuilder init for mismatched baseUrl/current path.', {
            baseUrl: settings?.baseUrl,
            currentPath: window.location.pathname,
        });
        return;
    }

    hmrSettings = settings;

    settings = applyDevScenarios(settings);

    const boot = bootstrapShadowReactApp({
        containerSelector: '.formie-form-builder',
        pluginHandle: 'formie',
        styleTexts: [formBuilderStyles],
        missingContainerMessage: 'FormBuilder container not found: .formie-form-builder',
    });

    if (!boot) {
        return;
    }

    const { mountNode, portalContainer, targetContainer } = boot;
    hmrMountNode = mountNode;
    hmrPortalContainer = portalContainer;

    // Register custom form schema fields/components
    registerFormComponents({
        FormBuilderTabs,
        FormBuilderTabList,
        FormBuilderTabTrigger,
        FormBuilderTabContent,
        FormUsage,
        FormMetaDetails,
        Integrations,
        FieldBuilder,
        Notifications,
        NotificationPreview,
        NotificationTest,
        ...previewSchemaComponents,
    });
    // Register form builder specific fields
    registerFormFields({
        pageConditions: PageConditionsField,
        notificationRecipients: NotificationRecipientsField,
        notificationConditions: NotificationConditionsField,
        integrationConditions: IntegrationConditionsField,
        fieldSelect: FieldSelectField,
        fieldConditions: FieldConditionsField,
        nextButtonConditions: NextButtonConditionsField,
        statusRuleConditions: StatusRuleConditionsField,
        statusRules: StatusRulesField,
        redirectRuleConditions: RedirectRuleConditionsField,
        redirectRules: RedirectRulesField,
        nestedLayout: NestedLayoutField,
        integrationFieldMapping: IntegrationFieldMappingField,
        integrationRefreshSelect: IntegrationRefreshSelectField,
        integrationRefreshCombobox: IntegrationRefreshSelectField,
        integrationRefreshButton: IntegrationActionButtonField,
        integrationSendTestPayloadButton: IntegrationActionButtonField,
        paymentProviderSettings: PaymentProviderSettingsField,
        table: FormieEditableTableField,
        staticTable: FormieStaticTableField,
        formieTableColumns: FormieTableColumnsField,
        formieTableDefaults: FormieTableDefaultsField,
        optionDynamicSettings: OptionDynamicSettingsField,
        optionSourceSettings: OptionDynamicSettingsField,
        codeEditor: CodeEditorField,
    });

    // Initialize the store with form data
    const { loadForm } = useAppStore.getState();
    loadForm(settings);

    // Field-type configs are server-provided at bootstrap for deterministic builder readiness.

    // Initialize router state after store is loaded
    initializeRouterState();

    const root = hmrRoot ?? createRoot(mountNode);
    hmrRoot = root;
    root.render(createElement(Fragment, null,
        createElement(FormBuilder, {
            initialData: settings.data || {},
            schema: settings.schema || [],
            schemaIndex: settings.schemaIndex || null,
        }),
        shouldRenderDevToolbar() ? createElement(DevToolsToolbar) : null));

    markContainerReady(targetContainer, 'formie-form-builder--ready');

    persistHmrData();
};
