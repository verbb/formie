import baseConfig from '@verbb/plugin-kit/eslint/config.base.js';
import { createReactHooksConfig } from '@verbb/plugin-kit/eslint/config.react-hooks.js';

import { defineConfig } from 'eslint/config';

export default defineConfig([
    ...baseConfig,
    ...createReactHooksConfig({
        jsxFiles: ['**/*.{js,jsx}'],
        hookFiles: ['**/use*.js', '**/*Hook*.js'],
    }),
]);
