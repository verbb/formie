import type { FormieModuleDefinition } from '#contracts/modules';

export const builtinFieldModuleLoaders: Record<string, () => Promise<FormieModuleDefinition>> = {
    // Keep the builtin map flat and explicit so manifest ids remain the source of
    // truth for lazy-loading first-party field enhancements.
    'calculations': () => import('#modules/fields/calculations').then((module) => module.calculationsModule),
    'checkbox-radio': () => import('#modules/fields/checkbox-radio').then((module) => module.checkboxRadioModule),
    'combobox': () => import('#modules/fields/combobox').then((module) => module.comboboxModule),
    'conditions': () => import('#modules/fields/conditions').then((module) => module.conditionsModule),
    'custom-google-maps': () => import('#modules/fields/custom-google-maps').then((module) => module.customGoogleMapsModule),
    'custom-link': () => import('#modules/fields/custom-link').then((module) => module.customLinkModule),
    'custom-maps': () => import('#modules/fields/custom-maps').then((module) => module.customMapsModule),
    'date-picker': () => import('#modules/fields/date-picker').then((module) => module.datePickerModule),
    'file-upload': () => import('#modules/fields/file-upload').then((module) => module.fileUploadModule),
    'upload-manager': () => import('#modules/fields/upload-manager').then((module) => module.uploadManagerModule),
    'hidden': () => import('#modules/fields/hidden').then((module) => module.hiddenModule),
    'phone-country': () => import('#modules/fields/phone-country').then((module) => module.phoneCountryModule),
    'address-country': () => import('#modules/fields/address-country').then((module) => module.addressCountryModule),
    'address-state': () => import('#modules/fields/address-state').then((module) => module.addressStateModule),
    'repeater': () => import('#modules/fields/repeater').then((module) => module.repeaterModule),
    'rich-text': () => import('#modules/fields/rich-text').then((module) => module.richTextModule),
    'signature': () => import('#modules/fields/signature').then((module) => module.signatureModule),
    'summary': () => import('#modules/fields/summary').then((module) => module.summaryModule),
    'survey-likert': () => import('#modules/fields/survey-likert').then((module) => module.surveyLikertModule),
    'survey-rank': () => import('#modules/fields/survey-rank').then((module) => module.surveyRankModule),
    'survey-rating': () => import('#modules/fields/survey-rating').then((module) => module.surveyRatingModule),
    'table': () => import('#modules/fields/table').then((module) => module.tableModule),
    'text-limit': () => import('#modules/fields/text-limit').then((module) => module.textLimitModule),
};
