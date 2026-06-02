import assert from 'node:assert/strict';
import {
    moveNestedFieldWithinParentInPages,
    moveTopLevelFieldToNestedInPages,
    moveNestedFieldToTopLevelInPages,
    canDropInNestedContainer,
    canDropToTopLevel,
    isAllowedNestedTargetDrop,
} from '../src/form-builder/builder/nestedMoveUtils.js';

const toRows = (layout) => {
    return layout.map((row) => {
        return {
            _id: `row-${Math.random().toString(16).slice(2)}`,
            fields: row.map((label) => {
                return { _id: `field-${label}`, label };
            }),
        };
    });
};

const fromRows = (rows) => {
    return rows.map((row) => {
        return row.fields.map((field) => {
            return field.label;
        });
    });
};

const makePages = (layout) => {
    return {
        pages: [
            {
                rows: [
                    {
                        fields: [
                            {
                                rows: toRows(layout),
                            },
                        ],
                    },
                ],
            },
        ],
    };
};

const makePagesTopAndNested = ({
    topRows,
    nestedRows,
}) => {
    return {
        pages: [
            {
                rows: topRows.map((row) => {
                    return {
                        fields: row.map((label) => {
                            if (label === 'PARENT') {
                                return {
                                    label: 'PARENT',
                                    type: 'Group',
                                    rows: toRows(nestedRows),
                                };
                            }

                            return {
                                label,
                                type: 'Text',
                            };
                        }),
                    };
                }),
            },
        ],
    };
};

const fromTopRows = (pages) => {
    return pages.pages[0].rows.map((row) => {
        return row.fields.map((field) => {
            return field.label;
        });
    });
};

const fromNestedRowsAtParent = (pages, parentRowIndex = 0, parentFieldIndex = 0) => {
    const rows = pages.pages[0].rows[parentRowIndex].fields[parentFieldIndex].rows;
    return fromRows(rows);
};

const modelMove = ({
    layout,
    fromRow,
    fromField,
    toRow,
    toField,
    asNewRow,
}) => {
    const rows = layout.map((r) => { return [...r]; });
    const isOriginalSameRowMove = fromRow === toRow;
    const moved = rows[fromRow][fromField];
    rows[fromRow].splice(fromField, 1);

    let targetRow = toRow;
    if (rows[fromRow].length === 0) {
        rows.splice(fromRow, 1);
        if (fromRow < targetRow) {
            targetRow = Math.max(0, targetRow - 1);
        }
    }

    if (asNewRow) {
        const insertRow = targetRow === -1 ? 0 : targetRow + 1;
        rows.splice(insertRow, 0, [moved]);
        return rows;
    }

    const insertIndexRaw = toField === -1 ? 0 : toField + 1;
    const insertIndex = (isOriginalSameRowMove && fromField <= toField)
        ? Math.max(0, insertIndexRaw - 1)
        : insertIndexRaw;
    if (!rows[targetRow]) {
        return rows;
    }
    rows[targetRow].splice(insertIndex, 0, moved);
    return rows;
};

const layouts = [
    [['A', 'B', 'C']],
    [['A'], ['B', 'C']],
    [['A', 'B'], ['C']],
    [['A'], ['B'], ['C']],
    [['A', 'B'], ['C', 'D']],
];

let cases = 0;
layouts.forEach((layout) => {
    layout.forEach((row, fromRow) => {
        row.forEach((_, fromField) => {
            layout.forEach((targetRowFields, toRow) => {
                for (let toField = -1; toField < targetRowFields.length; toField++) {
                    const input = makePages(layout);
                    const actual = moveNestedFieldWithinParentInPages({
                        pages: input,
                        pageIndex: 0,
                        rowIndex: 0,
                        fieldIndex: 0,
                        fromNestedRowIndex: fromRow,
                        fromNestedFieldIndex: fromField,
                        toNestedRowIndex: toRow,
                        toNestedFieldIndex: toField,
                        asNewRow: false,
                    });
                    const actualLayout = fromRows(actual.pages[0].rows[0].fields[0].rows);
                    const expectedLayout = modelMove({
                        layout,
                        fromRow,
                        fromField,
                        toRow,
                        toField,
                        asNewRow: false,
                    });

                    assert.deepEqual(
                        actualLayout,
                        expectedLayout,
                        `Mismatch for in-row move: layout=${JSON.stringify(layout)} from=${fromRow}:${fromField} to=${toRow}:${toField}`,
                    );
                    cases += 1;
                }
            });

            for (let toRow = -1; toRow < layout.length; toRow++) {
                const input = makePages(layout);
                const actual = moveNestedFieldWithinParentInPages({
                    pages: input,
                    pageIndex: 0,
                    rowIndex: 0,
                    fieldIndex: 0,
                    fromNestedRowIndex: fromRow,
                    fromNestedFieldIndex: fromField,
                    toNestedRowIndex: toRow,
                    toNestedFieldIndex: -1,
                    asNewRow: true,
                });
                const actualLayout = fromRows(actual.pages[0].rows[0].fields[0].rows);
                const expectedLayout = modelMove({
                    layout,
                    fromRow,
                    fromField,
                    toRow,
                    toField: -1,
                    asNewRow: true,
                });

                assert.deepEqual(
                    actualLayout,
                    expectedLayout,
                    `Mismatch for new-row move: layout=${JSON.stringify(layout)} from=${fromRow}:${fromField} toRow=${toRow}`,
                );
                cases += 1;
            }
        });
    });
});

// Top-level -> nested
{
    const input = makePagesTopAndNested({
        topRows: [['A', 'PARENT'], ['B']],
        nestedRows: [['N1', 'N2']],
    });
    const actual = moveTopLevelFieldToNestedInPages({
        pages: input,
        fromPageIndex: 0,
        fromRowIndex: 0,
        fromFieldIndex: 0,
        toPageIndex: 0,
        toRowIndex: 0,
        toFieldIndex: 1,
        toNestedRowIndex: 0,
        toNestedFieldIndex: 0,
        asNewRow: false,
    });

    assert.deepEqual(fromTopRows(actual), [['PARENT'], ['B']], 'Top-level source field should be removed');
    assert.deepEqual(fromNestedRowsAtParent(actual), [['N1', 'A', 'N2']], 'Field should be inserted at requested nested index');
    cases += 1;
}

{
    const input = makePagesTopAndNested({
        topRows: [['A', 'PARENT']],
        nestedRows: [['N1']],
    });
    const actual = moveTopLevelFieldToNestedInPages({
        pages: input,
        fromPageIndex: 0,
        fromRowIndex: 0,
        fromFieldIndex: 0,
        toPageIndex: 0,
        toRowIndex: 0,
        toFieldIndex: 1,
        toNestedRowIndex: 0,
        toNestedFieldIndex: -1,
        asNewRow: true,
    });

    assert.deepEqual(fromTopRows(actual), [['PARENT']], 'Top-level source row should retain parent');
    assert.deepEqual(fromNestedRowsAtParent(actual), [['N1'], ['A']], 'Field should create a new nested row');
    cases += 1;
}

// Nested -> top-level
{
    const input = makePagesTopAndNested({
        topRows: [['PARENT', 'X']],
        nestedRows: [['N1', 'N2']],
    });
    const actual = moveNestedFieldToTopLevelInPages({
        pages: input,
        fromPageIndex: 0,
        fromRowIndex: 0,
        fromFieldIndex: 0,
        fromNestedRowIndex: 0,
        fromNestedFieldIndex: 1,
        toPageIndex: 0,
        toRowIndex: 0,
        toFieldIndex: 1,
        asNewRow: false,
    });

    assert.deepEqual(fromNestedRowsAtParent(actual), [['N1']], 'Nested source field should be removed');
    assert.deepEqual(fromTopRows(actual), [['PARENT', 'X', 'N2']], 'Field should insert to top-level position');
    cases += 1;
}

{
    const input = makePagesTopAndNested({
        topRows: [['PARENT']],
        nestedRows: [['N1'], ['N2']],
    });
    const actual = moveNestedFieldToTopLevelInPages({
        pages: input,
        fromPageIndex: 0,
        fromRowIndex: 0,
        fromFieldIndex: 0,
        fromNestedRowIndex: 0,
        fromNestedFieldIndex: 0,
        toPageIndex: 0,
        toRowIndex: 0,
        toFieldIndex: -1,
        asNewRow: true,
    });

    assert.deepEqual(fromNestedRowsAtParent(actual), [['N2']], 'Empty nested row should be removed');
    assert.deepEqual(fromTopRows(actual), [['PARENT'], ['N1']], 'Field should create new top-level row');
    cases += 1;
}

// Constraint utility coverage
{
    const activeTop = { source: 'top-level', isNew: false };
    const activeNew = { source: 'pill', isNew: true };
    const activeNestedGroup = {
        source: 'nested',
        isNew: false,
        isRepeatableParentField: false,
        pageIndex: 0,
        rowIndex: 0,
        fieldIndex: 1,
    };
    const activeNestedRepeater = {
        source: 'nested',
        isNew: false,
        isRepeatableParentField: true,
        pageIndex: 0,
        rowIndex: 0,
        fieldIndex: 1,
    };

    assert.equal(canDropInNestedContainer({
        activeData: activeTop, isRepeater: false, pageIndex: 0, rowIndex: 0, fieldIndex: 1,
    }), true, 'Group should accept top-level existing fields');
    assert.equal(canDropInNestedContainer({
        activeData: activeTop, isRepeater: true, pageIndex: 0, rowIndex: 0, fieldIndex: 1,
    }), false, 'Repeater should reject top-level existing fields');
    assert.equal(canDropInNestedContainer({
        activeData: activeNew, isRepeater: true, pageIndex: 0, rowIndex: 0, fieldIndex: 1,
    }), true, 'Repeater should accept new fields');
    assert.equal(canDropInNestedContainer({
        activeData: activeNestedGroup, isRepeater: false, pageIndex: 0, rowIndex: 0, fieldIndex: 1,
    }), true, 'Group nested fields can move between groups');
    assert.equal(canDropInNestedContainer({
        activeData: activeNestedRepeater, isRepeater: true, pageIndex: 0, rowIndex: 0, fieldIndex: 1,
    }), true, 'Repeater nested fields can move within same repeater');
    assert.equal(canDropInNestedContainer({
        activeData: activeNestedRepeater, isRepeater: true, pageIndex: 0, rowIndex: 0, fieldIndex: 2,
    }), false, 'Repeater nested fields cannot move into different repeater');

    assert.equal(canDropToTopLevel({ activeData: activeNestedRepeater }), false, 'Repeater nested fields cannot move to top-level');
    assert.equal(canDropToTopLevel({ activeData: activeNestedGroup }), true, 'Group nested fields can move to top-level');

    assert.equal(isAllowedNestedTargetDrop({
        fieldData: activeNestedRepeater,
        targetIsRepeater: true,
        isSameNestedParent: true,
    }), true, 'Repeater nested -> same repeater is valid');
    assert.equal(isAllowedNestedTargetDrop({
        fieldData: activeNestedRepeater,
        targetIsRepeater: true,
        isSameNestedParent: false,
    }), false, 'Repeater nested -> different repeater invalid');
    assert.equal(isAllowedNestedTargetDrop({
        fieldData: activeNestedRepeater,
        targetIsRepeater: false,
        isSameNestedParent: false,
    }), false, 'Repeater nested -> group invalid');
    assert.equal(isAllowedNestedTargetDrop({
        fieldData: activeNestedGroup,
        targetIsRepeater: false,
        isSameNestedParent: false,
    }), true, 'Group nested -> group valid');
    cases += 1;
}

console.log(`Builder move/constraint tests passed (${cases} cases).`);
