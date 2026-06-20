import { beforeEach, describe, expect, it, vi } from 'vitest';

const TEST_TRANSLATABLE_CONFIG = {
    form: ['title'],
    formSettings: ['errorMessage'],
    page: ['label'],
    pageSettings: ['submitButtonLabel'],
    notification: ['subject', 'content', 'fromName', 'replyToName'],
    fieldTypes: {
        'verbb\\formie\\fields\\SingleLineText': [
            'label',
            'instructions',
            'placeholder',
            'defaultValue',
            'validationMessages',
        ],
        'verbb\\formie\\base\\OptionsField': [
            'label',
            'options',
        ],
        'verbb\\formie\\fields\\Table': [
            'label',
            'addRowLabel',
            'columns',
        ],
        'verbb\\formie\\fields\\Name': [
            'label',
            'instructions',
            'validationMessages',
        ],
        'verbb\\formie\\fields\\Dropdown': [
            'label',
            'options',
            'instructions',
            'validationMessages',
        ],
        'verbb\\formie\\fields\\Group': [
            'label',
            'instructions',
            'validationMessages',
        ],
        'verbb\\formie\\fields\\Repeater': [
            'label',
            'addLabel',
            'instructions',
            'validationMessages',
        ],
    },
    scalarKeys: ['label', 'title', 'subject', 'content'],
    nestedKeys: ['options', 'columns'],
};

vi.mock('@form-builder/hooks/useAppStore', () => {
    return {
        default: {
            getState: () => ({
                translatableProperties: TEST_TRANSLATABLE_CONFIG,
            }),
        },
    };
});

import {
    extractSiteTranslationsFromFormData,
    mergeSiteOverridesIntoFormData,
    stripTranslatableValuesToCanonical,
} from './siteOverrides.js';

describe('siteOverrides', () => {
    const canonicalData = {
        title: 'Primary title',
        settings: {
            errorMessage: 'Primary error',
            submitMethod: 'ajax',
        },
        pages: [
            {
                uid: 'page-1',
                _handle: 'pageOne',
                label: 'Page 1',
                settings: {
                    submitButtonLabel: 'Submit',
                },
                rows: [
                    {
                        fields: [
                            {
                                uid: 'field-1',
                                type: 'verbb\\formie\\fields\\SingleLineText',
                                label: 'Name',
                                handle: 'name',
                            },
                            {
                                uid: 'field-2',
                                type: 'verbb\\formie\\base\\OptionsField',
                                label: 'Choice',
                                handle: 'choice',
                                options: [
                                    { value: 'a', label: 'Option A' },
                                    { value: 'b', label: 'Option B' },
                                ],
                            },
                            {
                                uid: 'field-3',
                                type: 'verbb\\formie\\fields\\Table',
                                label: 'Table',
                                handle: 'table',
                                columns: [
                                    { handle: 'col1', heading: 'Column 1' },
                                ],
                            },
                        ],
                    },
                ],
            },
        ],
        notifications: [
            {
                handle: 'admin',
                uid: 'notif-1',
                subject: 'Primary subject',
                content: 'Primary content',
                fromName: 'Primary from',
                replyToName: 'Primary reply',
            },
        ],
    };

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('merges form root, settings, page, field, and notification overrides', () => {
        const merged = mergeSiteOverridesIntoFormData(canonicalData, {
            title: 'French title',
            settings: {
                errorMessage: 'French error',
            },
            pages: {
                'page-1': {
                    label: 'Page FR',
                    settings: {
                        submitButtonLabel: 'Envoyer',
                    },
                },
            },
            fields: {
                'field-1': {
                    label: 'Nom',
                },
                'field-2': {
                    options: [
                        { value: 'a', label: 'Option A FR' },
                    ],
                },
                'field-3': {
                    columns: [
                        { handle: 'col1', heading: 'Colonne 1' },
                    ],
                },
            },
            notifications: {
                admin: {
                    subject: 'Sujet FR',
                    fromName: 'De FR',
                },
            },
        });

        expect(merged.title).toBe('French title');
        expect(merged.settings.errorMessage).toBe('French error');
        expect(merged.settings.submitMethod).toBe('ajax');
        expect(merged.pages[0].label).toBe('Page FR');
        expect(merged.pages[0].settings.submitButtonLabel).toBe('Envoyer');
        expect(merged.pages[0].rows[0].fields[0].label).toBe('Nom');
        expect(merged.pages[0].rows[0].fields[1].options[0].label).toBe('Option A FR');
        expect(merged.pages[0].rows[0].fields[1].options[1].label).toBe('Option B');
        expect(merged.pages[0].rows[0].fields[2].columns[0].heading).toBe('Colonne 1');
        expect(merged.notifications[0].subject).toBe('Sujet FR');
        expect(merged.notifications[0].fromName).toBe('De FR');
        expect(merged.notifications[0].content).toBe('Primary content');
    });

    it('merges page overrides keyed by handle when page has uid', () => {
        const merged = mergeSiteOverridesIntoFormData(canonicalData, {
            pages: {
                pageOne: {
                    label: 'Page FR by handle',
                },
            },
        });

        expect(merged.pages[0].label).toBe('Page FR by handle');
    });

    it('merges page overrides keyed by id when page has uid', () => {
        const data = {
            ...canonicalData,
            pages: [
                {
                    ...canonicalData.pages[0],
                    id: 42,
                    uid: 'page-uid-1',
                    _handle: 'pageOne',
                },
            ],
        };

        const merged = mergeSiteOverridesIntoFormData(data, {
            pages: {
                42: {
                    label: 'Page FR by id',
                },
            },
        });

        expect(merged.pages[0].label).toBe('Page FR by id');
    });

    it('extracts only changed translatable values', () => {
        const formData = mergeSiteOverridesIntoFormData(canonicalData, {
            title: 'French title',
            settings: {
                errorMessage: 'French error',
            },
            pages: {
                'page-1': {
                    label: 'Page FR',
                    settings: {
                        submitButtonLabel: 'Envoyer',
                    },
                },
            },
            fields: {
                'field-1': {
                    label: 'Nom',
                },
            },
            notifications: {
                admin: {
                    subject: 'Sujet FR',
                },
            },
        });

        const translations = extractSiteTranslationsFromFormData(canonicalData, formData);

        expect(translations).toEqual({
            title: 'French title',
            settings: {
                errorMessage: 'French error',
            },
            pages: {
                'page-1': {
                    label: 'Page FR',
                    settings: {
                        submitButtonLabel: 'Envoyer',
                    },
                },
            },
            fields: {
                'field-1': {
                    label: 'Nom',
                },
            },
            notifications: {
                admin: {
                    subject: 'Sujet FR',
                },
            },
        });
    });

    it('does not extract unchanged nested child fields when canonical uses settings.rows', () => {
        const nameFieldCanonical = {
            uid: 'name-field',
            reference: 'name-field',
            type: 'verbb\\formie\\fields\\Name',
            label: 'Name',
            handle: 'name',
            settings: {
                rows: [
                    {
                        fields: [
                            {
                                uid: 'first-name',
                                reference: 'first-name',
                                type: 'verbb\\formie\\fields\\SingleLineText',
                                label: 'First Name',
                                handle: 'firstName',
                                instructions: [],
                                validationMessages: [],
                            },
                            {
                                uid: 'last-name',
                                reference: 'last-name',
                                type: 'verbb\\formie\\fields\\SingleLineText',
                                label: 'Last Name',
                                handle: 'lastName',
                                instructions: [],
                                validationMessages: [],
                            },
                            {
                                uid: 'prefix',
                                reference: 'prefix',
                                type: 'verbb\\formie\\base\\OptionsField',
                                label: 'Prefix',
                                handle: 'prefix',
                                options: [
                                    { value: 'mr', label: 'Mr.' },
                                    { value: 'mrs', label: 'Mrs.' },
                                ],
                                instructions: [],
                                validationMessages: [],
                            },
                        ],
                    },
                ],
            },
        };

        const canonicalWithName = {
            ...canonicalData,
            pages: [
                {
                    ...canonicalData.pages[0],
                    rows: [
                        {
                            fields: [nameFieldCanonical],
                        },
                    ],
                },
            ],
        };

        const formData = mergeSiteOverridesIntoFormData(canonicalWithName, {
            title: 'Multi Site (Site 2)',
            fields: {
                'name-field': {
                    label: 'Name (Site 2)',
                },
                'first-name': {
                    label: 'First Name (Site 2)',
                },
            },
        });

        const nameField = formData.pages[0].rows[0].fields[0];

        nameField.rows = nameField.settings.rows;

        const translations = extractSiteTranslationsFromFormData(canonicalWithName, formData);

        expect(translations).toEqual({
            title: 'Multi Site (Site 2)',
            fields: {
                'name-field': {
                    label: 'Name (Site 2)',
                },
                'first-name': {
                    label: 'First Name (Site 2)',
                },
            },
        });
    });

    it('does not extract unchanged nested group child fields when rows and settings.rows diverge', () => {
        const groupA = {
            uid: 'uid-group-a',
            reference: 'group-a',
            type: 'verbb\\formie\\fields\\SingleLineText',
            label: 'Group A',
            handle: 'groupA',
        };
        const groupB = {
            uid: 'uid-group-b',
            reference: 'group-b',
            type: 'verbb\\formie\\fields\\SingleLineText',
            label: 'Group B',
            handle: 'groupB',
        };
        const groupCanonical = {
            uid: 'uid-group-field',
            reference: 'group-field',
            type: 'verbb\\formie\\fields\\Group',
            label: 'Group',
            handle: 'group',
            rows: [
                {
                    fields: [groupA, groupB],
                },
            ],
        };
        const canonicalWithGroup = {
            ...canonicalData,
            pages: [
                {
                    ...canonicalData.pages[0],
                    rows: [
                        {
                            fields: [groupCanonical],
                        },
                    ],
                },
            ],
        };
        const formData = mergeSiteOverridesIntoFormData(canonicalWithGroup, {
            fields: {
                'group-field': {
                    label: 'Group (Site 2)',
                },
                'group-a': {
                    label: 'Group A (Site 2)',
                },
            },
        });
        const groupField = formData.pages[0].rows[0].fields[0];

        groupField.rows = [
            {
                fields: [
                    {
                        ...groupA,
                        label: 'Group A (Site 2)',
                    },
                    {
                        uid: 'uid-group-b',
                        type: 'verbb\\formie\\fields\\SingleLineText',
                        label: 'Group B',
                        handle: 'groupB',
                    },
                ],
            },
        ];
        groupField.settings = {
            rows: [
                {
                    fields: [groupA, groupB],
                },
            ],
        };

        const translations = extractSiteTranslationsFromFormData(canonicalWithGroup, formData);

        expect(translations.fields).toEqual({
            'group-field': {
                label: 'Group (Site 2)',
            },
            'group-a': {
                label: 'Group A (Site 2)',
            },
        });
    });

    it('does not extract unchanged repeater child fields or unchanged addLabel', () => {
        const repeaterA = {
            uid: 'uid-repeater-a',
            reference: 'repeater-a',
            type: 'verbb\\formie\\fields\\SingleLineText',
            label: 'Repeater A',
            handle: 'repeaterA',
        };
        const repeaterB = {
            uid: 'uid-repeater-b',
            reference: 'repeater-b',
            type: 'verbb\\formie\\fields\\SingleLineText',
            label: 'Repeater B',
            handle: 'repeaterB',
        };
        const repeaterCanonical = {
            uid: 'uid-repeater-field',
            reference: 'repeater-field',
            type: 'verbb\\formie\\fields\\Repeater',
            label: 'Repeater',
            handle: 'repeater',
            addLabel: 'Add another row',
            rows: [
                {
                    fields: [repeaterA, repeaterB],
                },
            ],
        };
        const canonicalWithRepeater = {
            ...canonicalData,
            pages: [
                {
                    ...canonicalData.pages[0],
                    rows: [
                        {
                            fields: [repeaterCanonical],
                        },
                    ],
                },
            ],
        };
        const formData = mergeSiteOverridesIntoFormData(canonicalWithRepeater, {
            fields: {
                'repeater-field': {
                    label: 'Repeater (Site 2)',
                },
                'repeater-a': {
                    label: 'Repeater A (Site 2)',
                },
            },
        });
        const repeaterField = formData.pages[0].rows[0].fields[0];

        repeaterField.rows = [
            {
                fields: [
                    {
                        ...repeaterA,
                        label: 'Repeater A (Site 2)',
                    },
                    {
                        uid: 'uid-repeater-b',
                        type: 'verbb\\formie\\fields\\SingleLineText',
                        label: 'Repeater B',
                        handle: 'repeaterB',
                    },
                ],
            },
        ];
        repeaterField.settings = {
            rows: [
                {
                    fields: [repeaterA, repeaterB],
                },
            ],
        };

        const translations = extractSiteTranslationsFromFormData(canonicalWithRepeater, formData);

        expect(translations.fields).toEqual({
            'repeater-field': {
                label: 'Repeater (Site 2)',
            },
            'repeater-a': {
                label: 'Repeater A (Site 2)',
            },
        });
    });

    it('extracts option overrides with canonical match keys when label and value change', () => {
        const canonicalWithRadio = {
            ...canonicalData,
            pages: [
                {
                    ...canonicalData.pages[0],
                    rows: [
                        {
                            fields: [
                                {
                                    uid: 'radio-field',
                                    type: 'verbb\\formie\\base\\OptionsField',
                                    label: 'Radio',
                                    handle: 'radio',
                                    options: [
                                        { label: 'Option 1', value: 'Option 1' },
                                        { label: 'Option 2', value: 'Option 2' },
                                        { label: 'Option 3', value: 'Option 3' },
                                    ],
                                },
                            ],
                        },
                    ],
                },
            ],
        };

        const formData = {
            ...canonicalWithRadio,
            pages: [
                {
                    ...canonicalWithRadio.pages[0],
                    rows: [
                        {
                            fields: [
                                {
                                    ...canonicalWithRadio.pages[0].rows[0].fields[0],
                                    options: [
                                        { label: 'Option 1', value: 'Option 1' },
                                        { label: 'Option 2 (Site 2)', value: 'Option 2 (Site 2)' },
                                        { label: 'Option 3', value: 'Option 3' },
                                    ],
                                },
                            ],
                        },
                    ],
                },
            ],
        };

        const translations = extractSiteTranslationsFromFormData(canonicalWithRadio, formData);

        expect(translations.fields['radio-field'].options).toEqual([
            { value: 'Option 2', label: 'Option 2 (Site 2)', optionValue: 'Option 2 (Site 2)' },
        ]);
    });

    it('merges legacy option overrides that stored translated values in value', () => {
        const canonicalWithRadio = {
            ...canonicalData,
            pages: [
                {
                    ...canonicalData.pages[0],
                    rows: [
                        {
                            fields: [
                                {
                                    uid: 'radio-field',
                                    type: 'verbb\\formie\\base\\OptionsField',
                                    label: 'Radio',
                                    handle: 'radio',
                                    options: [
                                        { label: 'Option 1', value: 'Option 1' },
                                        { label: 'Option 2', value: 'Option 2' },
                                        { label: 'Option 3', value: 'Option 3' },
                                    ],
                                },
                            ],
                        },
                    ],
                },
            ],
        };

        const merged = mergeSiteOverridesIntoFormData(canonicalWithRadio, {
            fields: {
                'radio-field': {
                    options: [
                        { label: 'Option 2 (Site 2)', value: 'Option 2 (Site 2)' },
                    ],
                },
            },
        });

        expect(merged.pages[0].rows[0].fields[0].options[1].label).toBe('Option 2 (Site 2)');
        expect(merged.pages[0].rows[0].fields[0].options[1].value).toBe('Option 2 (Site 2)');
    });

    it('merges canonical-keyed option overrides with translated values', () => {
        const canonicalWithRadio = {
            ...canonicalData,
            pages: [
                {
                    ...canonicalData.pages[0],
                    rows: [
                        {
                            fields: [
                                {
                                    uid: 'radio-field',
                                    type: 'verbb\\formie\\base\\OptionsField',
                                    label: 'Radio',
                                    handle: 'radio',
                                    options: [
                                        { label: 'Option 1', value: 'Option 1' },
                                        { label: 'Option 2', value: 'Option 2' },
                                        { label: 'Option 3', value: 'Option 3' },
                                    ],
                                },
                            ],
                        },
                    ],
                },
            ],
        };

        const merged = mergeSiteOverridesIntoFormData(canonicalWithRadio, {
            fields: {
                'radio-field': {
                    options: [
                        { value: 'Option 2', label: 'Option 2 (Site 2)', optionValue: 'Option 2 (Site 2)' },
                    ],
                },
            },
        });

        expect(merged.pages[0].rows[0].fields[0].options[1].label).toBe('Option 2 (Site 2)');
        expect(merged.pages[0].rows[0].fields[0].options[1].value).toBe('Option 2 (Site 2)');
    });

    it('strips option label and value back to canonical for save payload', () => {
        const canonicalWithRadio = {
            ...canonicalData,
            pages: [
                {
                    ...canonicalData.pages[0],
                    rows: [
                        {
                            fields: [
                                {
                                    uid: 'radio-field',
                                    type: 'verbb\\formie\\base\\OptionsField',
                                    label: 'Radio',
                                    handle: 'radio',
                                    options: [
                                        { label: 'Option 1', value: 'Option 1' },
                                        { label: 'Option 2', value: 'Option 2' },
                                        { label: 'Option 3', value: 'Option 3' },
                                    ],
                                },
                            ],
                        },
                    ],
                },
            ],
        };

        const formData = {
            ...canonicalWithRadio,
            pages: [
                {
                    ...canonicalWithRadio.pages[0],
                    rows: [
                        {
                            fields: [
                                {
                                    ...canonicalWithRadio.pages[0].rows[0].fields[0],
                                    options: [
                                        { label: 'Option 1', value: 'Option 1' },
                                        { label: 'Option 2 (Site 2)', value: 'Option 2 (Site 2)' },
                                        { label: 'Option 3', value: 'Option 3' },
                                    ],
                                },
                            ],
                        },
                    ],
                },
            ],
        };

        const stripped = stripTranslatableValuesToCanonical(formData, canonicalWithRadio);

        expect(stripped.pages[0].rows[0].fields[0].options).toEqual([
            { label: 'Option 1', value: 'Option 1' },
            { label: 'Option 2', value: 'Option 2' },
            { label: 'Option 3', value: 'Option 3' },
        ]);
    });

    it('strips translatable values back to canonical for save payload', () => {
        const formData = mergeSiteOverridesIntoFormData(canonicalData, {
            title: 'French title',
            settings: {
                errorMessage: 'French error',
            },
            pages: {
                'page-1': {
                    label: 'Page FR',
                    settings: {
                        submitButtonLabel: 'Envoyer',
                    },
                },
            },
            fields: {
                'field-1': {
                    label: 'Nom',
                },
            },
            notifications: {
                admin: {
                    subject: 'Sujet FR',
                },
            },
        });

        const stripped = stripTranslatableValuesToCanonical(formData, canonicalData);

        expect(stripped.title).toBe('Primary title');
        expect(stripped.settings.errorMessage).toBe('Primary error');
        expect(stripped.pages[0].label).toBe('Page 1');
        expect(stripped.pages[0].settings.submitButtonLabel).toBe('Submit');
        expect(stripped.pages[0].rows[0].fields[0].label).toBe('Name');
        expect(stripped.notifications[0].subject).toBe('Primary subject');
    });
});
