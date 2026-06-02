export type TranslationReplacements = Record<string, string | number>;
export declare function getFormieTranslations(): Record<string, string>;
export declare function setFormieTranslations(translations: Record<string, string>): Record<string, string>;
export declare function mergeFormieTranslations(translations: Record<string, string>): Record<string, string>;
export declare function t(message: string, replacements?: TranslationReplacements): string;
export declare const translate: typeof t;
//# sourceMappingURL=i18n.d.ts.map