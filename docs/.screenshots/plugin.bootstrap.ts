import type { ScreenshotSetupContext } from '@verbb/docs-screenshots/types';
import { registerPluginBootstrap } from '@verbb/docs-screenshots/api';

export default registerPluginBootstrap({
    id: 'plugin-default',
    async setup(_context: ScreenshotSetupContext) {
        // Plugin-specific screenshot setup hooks can be added here later.
    },
});
