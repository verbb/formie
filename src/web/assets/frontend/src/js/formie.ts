import '../css/formie.css';

import { createDebug, formie, setFormieDebugEnabled } from '@verbb/formie-browser';
import type { FormieApp } from '@verbb/formie-browser';

type BrowserStartupConfig = {
    autoInit: boolean;
    useObserver: boolean;
    debug: boolean;
};

const debug = createDebug('general', 'browser');
const PLUGIN_FORM_SELECTOR = '[data-formie]:not([data-formie-init="false"]), [data-formie-form]:not([data-formie-init="false"])';
let pluginFormsApp: FormieApp | null = null;

function resolveBrowserStartupScript(): HTMLScriptElement | null {
    const currentModuleUrl = new URL(import.meta.url, document.baseURI).href;
    const scripts = Array.from(document.querySelectorAll<HTMLScriptElement>('script[data-formie-startup][src]'));

    return scripts.find((script) => {
        return new URL(script.src, document.baseURI).href === currentModuleUrl;
    }) || null;
}

function getBrowserStartupConfig(): BrowserStartupConfig {
    const script = resolveBrowserStartupScript();

    return {
        autoInit: script?.dataset.formieAutoInit !== 'false',
        useObserver: script?.dataset.formieUseObserver !== 'false',
        debug: script?.dataset.formieDebug === 'true',
    };
}

export async function startPluginForms(): Promise<FormieApp> {
    if (pluginFormsApp) {
        return pluginFormsApp;
    }

    const config = getBrowserStartupConfig();
    setFormieDebugEnabled(config.debug);
    debug.log('Starting plugin-rendered Formie forms.', {
        useObserver: config.useObserver,
    });

    pluginFormsApp = await formie({
        element: PLUGIN_FORM_SELECTOR,
        observe: config.useObserver,
        allowEmpty: true,
    });

    debug.log('Plugin-rendered Formie forms started.', {
        mountedInstances: pluginFormsApp.instances.length,
        observing: config.useObserver,
    });

    return pluginFormsApp;
}

function bootstrap(): void {
    const config = getBrowserStartupConfig();
    setFormieDebugEnabled(config.debug);
    debug.log('Browser startup invoked.', config);

    if (!config.autoInit) {
        debug.log('Auto-start disabled by browser startup config.');
        return;
    }

    void startPluginForms();
}

if (getBrowserStartupConfig().autoInit) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrap, { once: true });
    } else {
        bootstrap();
    }
}
