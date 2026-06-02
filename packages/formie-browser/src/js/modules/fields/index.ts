import type { FormieModuleDefinition } from '#contracts/modules';

export const builtinFieldModuleLoaders: Record<string, () => Promise<FormieModuleDefinition>> = {
    // Keep the builtin map flat and explicit so manifest ids remain the source of
    // truth for lazy-loading first-party field enhancements.
    'calculations': () => import('#modules/fields/calculations').then((module) => module.calculationsModule),
    'checkbox-radio': () => import('#modules/fields/checkbox-radio').then((module) => module.checkboxRadioModule),
    'conditions': () => import('#modules/fields/conditions').then((module) => module.conditionsModule),
    'date-picker': () => import('#modules/fields/date-picker').then((module) => module.datePickerModule),
    'file-upload': () => import('#modules/fields/file-upload').then((module) => module.fileUploadModule),
    'hidden': () => import('#modules/fields/hidden').then((module) => module.hiddenModule),
    'phone-country': () => import('#modules/fields/phone-country').then((module) => module.phoneCountryModule),
    'repeater': () => import('#modules/fields/repeater').then((module) => module.repeaterModule),
    'rich-text': () => import('#modules/fields/rich-text').then((module) => module.richTextModule),
    'signature': () => import('#modules/fields/signature').then((module) => module.signatureModule),
    'summary': () => import('#modules/fields/summary').then((module) => module.summaryModule),
    'table': () => import('#modules/fields/table').then((module) => module.tableModule),
    'text-limit': () => import('#modules/fields/text-limit').then((module) => module.textLimitModule),
};
