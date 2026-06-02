import { getDevToolsConfig } from '@form-builder/dev/config';
import { createFieldPreviewPages } from '@form-builder/dev/scenarios/fieldPreviewScenario';
import { createStressTestPages, parseStressPattern } from '@form-builder/dev/scenarios/stressTestScenario';

const isDevBuild = () => {
    return import.meta.env.DEV;
};

const applyDevScenarios = (settings) => {
    if (!isDevBuild()) {
        return settings;
    }

    const config = getDevToolsConfig();
    if (!config.enabled || config.mode === 'none') {
        return settings;
    }

    if (config.mode === 'fieldPreview') {
        settings.data = {
            ...(settings.data || {}),
            pages: createFieldPreviewPages(settings, {
                valueMode: config.previewValueMode,
            }),
        };

        console.info('FormBuilder dev scenario enabled: field preview matrix', {
            valueMode: config.previewValueMode,
        });
        return settings;
    }

    if (config.mode === 'stressTest') {
        const stressConfig = parseStressPattern(config.stressPattern);
        if (!stressConfig) {
            console.warn('Invalid stress pattern. Use NxRxF (e.g. 20x5x5).');
            return settings;
        }

        const pages = createStressTestPages(settings, stressConfig);

        settings.data = {
            ...(settings.data || {}),
            pages,
            activePage: pages[0]?._handle || null,
        };

        console.info('FormBuilder dev scenario enabled: stress test', stressConfig);
    }

    if (config.mode === 'existingFieldsStress') {
        console.info('FormBuilder dev scenario enabled: existing fields stress test', {
            existingFieldsPattern: config.existingFieldsPattern,
        });
    }

    return settings;
};

const shouldRenderDevToolbar = () => {
    return isDevBuild();
};

export {
    applyDevScenarios,
    shouldRenderDevToolbar,
};
