import { craftForms, createDebug, formie, setFormieDebugEnabled } from '@verbb/formie-browser';
import type { FormieApp } from '@verbb/formie-browser';

type BrowserStartupConfig = {
    autoInit: boolean;
    useObserver: boolean;
    debug: boolean;
};

const debug = createDebug('general', 'browser');
const PLUGIN_FORM_SELECTOR = '[data-formie]:not([data-formie-init="false"]), [data-formie-form]:not([data-formie-init="false"])';

// A page can load this script under more than one URL, such as cached markup
// that still points at an earlier asset build. Each URL is a separate module
// instance, so the running app is shared through a page-level key. Otherwise
// every copy mounts every form, duplicating CAPTCHA widgets and submit handling.
const PLUGIN_FORMS_KEY = Symbol.for('verbb.formie.pluginForms');

type PluginFormsHost = typeof globalThis & {
    [PLUGIN_FORMS_KEY]?: Promise<FormieApp>;
};

const pluginFormsHost = globalThis as PluginFormsHost;

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

export function startPluginForms(): Promise<FormieApp> {
    const running = pluginFormsHost[PLUGIN_FORMS_KEY];

    if (running) {
        return running;
    }

    // Store the pending start straight away, because copies loaded together
    // start before either has finished mounting.
    const starting = createPluginForms();
    pluginFormsHost[PLUGIN_FORMS_KEY] = starting;

    starting.catch(() => {
        if (pluginFormsHost[PLUGIN_FORMS_KEY] === starting) {
            delete pluginFormsHost[PLUGIN_FORMS_KEY];
        }
    });

    return starting;
}

async function createPluginForms(): Promise<FormieApp> {
    const config = getBrowserStartupConfig();
    setFormieDebugEnabled(config.debug);
    debug.log('Starting plugin-rendered Formie forms.', {
        useObserver: config.useObserver,
    });

    const craftFormsApp = craftForms({ observe: config.useObserver });
    const pluginFormsApp = await formie({
        element: PLUGIN_FORM_SELECTOR,
        observe: config.useObserver,
        allowEmpty: true,
    });

    const destroy = pluginFormsApp.destroy.bind(pluginFormsApp);
    pluginFormsApp.destroy = async() => {
        craftFormsApp.destroy();
        await destroy();
        delete pluginFormsHost[PLUGIN_FORMS_KEY];
    };
    const rescan = pluginFormsApp.rescan.bind(pluginFormsApp);
    pluginFormsApp.rescan = async() => {
        craftFormsApp.rescan();
        return rescan();
    };

    debug.log('Plugin-rendered Formie forms started.', {
        mountedInstances: pluginFormsApp.instances.length,
        observing: config.useObserver,
    });

    return pluginFormsApp;
}

function bootstrap(): void {
    // Leave another copy's running app, and its debug setting, alone.
    if (pluginFormsHost[PLUGIN_FORMS_KEY]) {
        return;
    }

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
