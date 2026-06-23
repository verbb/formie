<?php

// Optional paths scanned by plugin-translator in addition to the plugin base path.
// Formie browser source lives outside src/, but its t('...') calls are translated server-side
// via Rendering::getFrontendJsTranslations().
return [
    dirname(__DIR__, 2) . '/packages/formie-browser/src/js',
];
