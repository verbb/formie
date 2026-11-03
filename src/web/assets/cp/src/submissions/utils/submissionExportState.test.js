import { describe, expect, it } from 'vitest';

import {
    applySectionColumns,
    getFormSelectionKey,
    getSubmissionExportDefaults,
    getSubmissionExportParams,
    getSubmissionTypes,
    groupExportColumns,
    mergeExportColumns,
    resolveStatusIds,
    setColumnsEnabled,
} from './submissionExportState.js';

const column = (handle, enabled = true, type = 'field') => {return { type, handle, label: handle, enabled }};

describe('getSubmissionExportDefaults', () => {
    it('maps a form source and its filters', () => {
        const defaults = getSubmissionExportDefaults({
            sourceKey: 'form:42',
            isIncomplete: false,
            isSpam: false,
            startDate: new Date(2026, 9, 1),
            endDate: new Date(2026, 9, 31),
            search: 'sydney',
            status: 'new',
            siteId: 3,
            sortAttribute: 'elements.dateUpdated',
            sortDirection: 'asc',
        });

        expect(defaults).toMatchObject({
            formIds: [42],
            includeComplete: true,
            includeIncomplete: false,
            includeSpam: false,
            dateRange: { startDate: '2026-10-01 00:00:00', endDate: '2026-10-31 23:59:59' },
            search: 'sydney',
            status: 'new',
            siteId: '3',
            sort: { handle: 'dateUpdated', dir: 'asc' },
            hasUncarriedFilters: false,
        });
    });

    it.each([
        ['all', null, null, ['complete', 'incomplete', 'spam']],
        ['complete', false, false, ['complete']],
        ['incomplete', true, false, ['incomplete']],
        ['spam', false, true, ['spam']],
    ])('maps the %s state', (_name, isIncomplete, isSpam, expected) => {
        const defaults = getSubmissionExportDefaults({ sourceKey: '*', isIncomplete, isSpam });

        expect(getSubmissionTypes(defaults)).toEqual(expected);
    });

    it('treats the All Forms source as every form with no dates', () => {
        const defaults = getSubmissionExportDefaults({ sourceKey: '*' });

        expect(defaults.formIds).toBe('*');
        expect(defaults.dateRange).toEqual({ startDate: null, endDate: null });
        expect(defaults.sort).toEqual({ handle: 'dateCreated', dir: 'desc' });
        expect(defaults.hasUncarriedFilters).toBe(false);
    });

    it('flags filters that cannot carry over', () => {
        expect(getSubmissionExportDefaults({ sourceKey: 'form:1', hasActiveFilter: true }).hasUncarriedFilters).toBe(true);
        expect(getSubmissionExportDefaults({ sourceKey: 'custom:abc' }).hasUncarriedFilters).toBe(true);
    });
});

describe('getSubmissionExportParams', () => {
    it('expands the selected submission types', () => {
        expect(getSubmissionExportParams({
            formIds: [1],
            submissionTypes: ['complete', 'spam'],
            statusIds: ['2'],
            siteId: '3',
        })).toEqual({
            formIds: [1],
            includeComplete: true,
            includeIncomplete: false,
            includeSpam: true,
            statusIds: ['2'],
            siteId: '3',
        });
    });
});

describe('resolveStatusIds', () => {
    const statuses = [{ value: '5', handle: 'new' }, { value: '6', handle: 'done' }];

    it('finds the index status by handle', () => {
        expect(resolveStatusIds('done', statuses)).toEqual(['6']);
    });

    it('ignores no status or an unknown one', () => {
        expect(resolveStatusIds(null, statuses)).toEqual([]);
        expect(resolveStatusIds('live', statuses)).toEqual([]);
    });
});

describe('getFormSelectionKey', () => {
    it('is stable regardless of selection order', () => {
        expect(getFormSelectionKey([3, 1])).toBe(getFormSelectionKey([1, 3]));
        expect(getFormSelectionKey('*')).toBe('*');
    });
});

describe('mergeExportColumns', () => {
    it('keeps existing order and choices and enables new columns', () => {
        const merged = mergeExportColumns(
            [column('message', false), column('email')],
            [column('email'), column('message'), column('phone')],
        );

        expect(merged.map(({ handle, enabled }) => {return [handle, enabled]})).toEqual([
            ['message', false],
            ['email', true],
            ['phone', true],
        ]);
    });

    it('drops columns that no longer exist', () => {
        expect(mergeExportColumns([column('old')], [column('email')]).map(({ handle }) => {return handle})).toEqual(['email']);
    });

    it('keeps the server default for new columns it disables', () => {
        expect(mergeExportColumns([], [column('id', false, 'attribute')])[0].enabled).toBe(false);
    });

    it('takes labels from the current columns rather than stored values', () => {
        const merged = mergeExportColumns(
            [{ type: 'field', handle: 'email', enabled: true }],
            [{ type: 'field', handle: 'email', label: 'Email address', enabled: true }],
        );

        expect(merged[0].label).toBe('Email address');
    });
});

describe('groupExportColumns', () => {
    const columns = [column('title', true, 'attribute'), column('email'), column('message'), column('phone'), column('orphan')];
    const groups = [
        { formId: 1, formTitle: 'Contact', handles: ['email', 'message', 'email.domain'] },
        { formId: 2, formTitle: 'Support', handles: ['email', 'phone'] },
        { formId: 3, formTitle: 'Empty', handles: ['notInList'] },
    ];

    it('groups submission details and each form, in the current order', () => {
        const sections = groupExportColumns(columns, groups);

        expect(sections.map(({ key, columns: list }) => {return [key, list.map(({ handle }) => {return handle})]})).toEqual([
            ['attributes', ['title']],
            ['form:1', ['email', 'message']],
            ['form:2', ['email', 'phone']],
            ['other', ['orphan']],
        ]);
    });
});

describe('applySectionColumns', () => {
    const columns = [column('title', true, 'attribute'), column('a'), column('b'), column('c')];

    it('reorders within the section without moving other columns', () => {
        const section = [columns[1], columns[3]];
        const next = applySectionColumns(columns, section, [columns[3], columns[1]]);

        expect(next.map(({ handle }) => {return handle})).toEqual(['title', 'c', 'b', 'a']);
    });

    it('applies a toggle to the column', () => {
        const section = [columns[1], columns[2]];
        const next = applySectionColumns(columns, section, [{ ...columns[1], enabled: false }, columns[2]]);

        expect(next.map(({ enabled }) => {return enabled})).toEqual([true, false, true, true]);
    });
});

describe('setColumnsEnabled', () => {
    it('changes only the given columns', () => {
        const columns = [column('a'), column('b')];

        expect(setColumnsEnabled(columns, [columns[0]], false).map(({ enabled }) => {return enabled})).toEqual([false, true]);
        expect(setColumnsEnabled(columns, columns, false).every(({ enabled }) => {return !enabled})).toBe(true);
    });
});
