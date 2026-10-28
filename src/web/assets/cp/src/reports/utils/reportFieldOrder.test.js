import { describe, expect, it } from 'vitest';

import {
    expandAllReportFields,
    getLayoutFieldColumns,
    inferFieldOrder,
    orderReportFields,
} from './reportFieldOrder';

const field = (handle, label = handle) => {
    return { type: 'field', handle, label, enabled: true };
};
const id = { type: 'attribute', handle: 'id', enabled: true };
const layout = [
    field('last', 'Last Name'),
    field('first', 'First Name'),
    field('company', 'Company'),
];
const handles = (columns) => {
    return columns.map((column) => {
        return column.handle;
    });
};

describe('report field order', () => {
    it('defaults old selected reports to Custom and automatic reports to Form layout', () => {
        expect(inferFieldOrder({ fieldColumnsMode: 'selected' })).toBe(
            'custom',
        );
        expect(inferFieldOrder({ fieldColumnsMode: 'all' })).toBe('layout');
        expect(
            inferFieldOrder({
                fieldColumnsMode: 'selected',
                fieldOrder: 'layout',
            }),
        ).toBe('layout');
    });

    it('sorts only field slots, including a selected subset', () => {
        const selected = [layout[2], id, layout[0]];
        expect(handles(orderReportFields(selected, 'layout', layout))).toEqual([
            'last',
            'id',
            'company',
        ]);
        expect(
            handles(orderReportFields(selected, 'alphabetical', layout)),
        ).toEqual(['company', 'id', 'last']);
        expect(handles(selected)).toEqual(['company', 'id', 'last']);
    });

    it('snapshots the displayed order when switching to Custom', () => {
        const alphabetical = orderReportFields(
            [layout[0], id, layout[1], layout[2]],
            'alphabetical',
            layout,
        );
        expect(
            handles(
                orderReportFields(
                    alphabetical,
                    'custom',
                    [...layout].reverse(),
                ),
            ),
        ).toEqual(['company', 'id', 'first', 'last']);
    });

    it('keeps all-field custom positions and labels while appending new fields and excluding subfields', () => {
        const saved = [field('company', 'Employer'), id, field('deleted')];
        const expanded = expandAllReportFields(saved, [
            ...layout,
            field('person.firstName'),
        ]);
        expect(handles(expanded)).toEqual(['company', 'id', 'last', 'first']);
        expect(expanded[0].label).toBe('Employer');
        expect(
            expanded
                .filter((column) => {
                    return column.type === 'field';
                })
                .every((column) => {
                    return column.enabled;
                }),
        ).toBe(true);
    });

    it('sorts alphabetically by label regardless of handles and preserves equal-label order', () => {
        expect(
            handles(
                orderReportFields(
                    [
                        field('z', 'apple'),
                        id,
                        field('a', 'Banana'),
                        field('b', 'Apple'),
                    ],
                    'alphabetical',
                ),
            ),
        ).toEqual(['z', 'id', 'b', 'a']);
    });

    it('uses stable form order and deduplicates shared handles in the layout catalog', () => {
        const fields = getLayoutFieldColumns([
            { formId: 2, columns: [field('company'), field('extra')] },
            { formId: 1, columns: layout },
        ]);
        expect(handles(fields)).toEqual(['last', 'first', 'company', 'extra']);
    });
});

describe('automatic compound field columns', () => {
    const available = [
        field('person', 'Name'),
        field('person.firstName', 'Name: First Name'),
        field('person.lastName', 'Name: Last Name'),
        field('address', 'Address'),
        field('address.city', 'Address: City'),
        field('single', 'Single name'),
        field('company', 'Company'),
    ];

    it.each([
        ['combined', ['person', 'address', 'single', 'company']],
        [
            'subfields',
            [
                'person.firstName',
                'person.lastName',
                'address.city',
                'single',
                'company',
            ],
        ],
        [
            'both',
            available.map((column) => {
                return column.handle;
            }),
        ],
        ['invalid', ['person', 'address', 'single', 'company']],
    ])(
        'expands %s columns while retaining ordinary fields and attribute positions',
        (mode, expected) => {
            const expanded = expandAllReportFields([id], available, mode);
            expect(handles(expanded)).toEqual(['id', ...expected]);
            expect(
                handles(orderReportFields(expanded, 'layout', available)),
            ).toEqual(['id', ...expected]);
            expect(
                orderReportFields(expanded, 'alphabetical', available)[0],
            ).toBe(id);
        },
    );

    it('drops disabled subfields and appends newly enabled parts in Custom order', () => {
        const changed = available.filter((column) => {
            return column.handle !== 'person.firstName';
        });
        changed.push(field('person.middleName', 'Name: Middle Name'));
        const expanded = expandAllReportFields(
            [
                available[2],
                id,
                available[1],
                available[4],
                available[5],
                available[6],
            ],
            changed,
            'subfields',
        );
        expect(handles(expanded)).toEqual([
            'person.lastName',
            'id',
            'address.city',
            'single',
            'company',
            'person.middleName',
        ]);
    });
});
