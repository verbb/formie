import { empty, clone } from '@formkit/utils';

const checkDuplicates = function(options, field) {
    const occurrences = options.reduce((counter, item) => {
        counter[item[field]] = counter[item[field]] + 1 || 1;
        return counter;
    }, {});

    return Object.keys(occurrences).filter((item) => {
        return occurrences[item] > 1 ? item : false;
    });
};

const required = function(node, prop) {
    const options = clone(node.value);
    const { columns } = node.context;

    if (!Array.isArray(options) || !Array.isArray(columns)) {
        return true;
    }

    const emptyFields = options.filter((row) => {
        const valueKey = columns.find((o) => { return o.type === prop; }).name || prop;

        // Opt-groups cancel out values
        if (prop === 'value' && row.isOptgroup) {
            return false;
        }

        if (empty(row[valueKey])) {
            if (prop === 'value') {
                node.context.valuesWithError.push(row[valueKey]);
            } else if (prop === 'label') {
                node.context.labelsWithError.push(row[valueKey]);
            }
        }

        return empty(row[valueKey]);
    });

    return !emptyFields.length;
};

const unique = function(node, prop) {
    let options = clone(node.value);
    const { columns } = node.context;

    if (prop === 'label') {
        node.context.labelsWithError = [];
    } else if (prop === 'value') {
        node.context.valuesWithError = [];
    }

    if (!Array.isArray(options) || !Array.isArray(columns)) {
        return true;
    }

    const value = columns.find((o) => { return o.type === prop; }).name || prop;

    if (prop === 'value') {
        options = options.filter((option) => { return !option.isOptgroup; });
    }

    const duplicates = checkDuplicates(options, value);

    duplicates.forEach((duplicate) => {
        if (prop === 'value') {
            node.context.valuesWithError.push(duplicate);
        } else if (prop === 'label') {
            node.context.labelsWithError.push(duplicate);
        }
    });

    return !duplicates.length;
};

const requiredTableCellLabel = (node) => {
    return required(node, 'label');
};

const requiredTableCellValue = (node) => {
    return required(node, 'value');
};

const uniqueTableCellLabel = (node) => {
    return unique(node, 'label');
};

const uniqueTableCellValue = (node) => {
    return unique(node, 'value');
};

export {
    requiredTableCellLabel, requiredTableCellValue, uniqueTableCellLabel, uniqueTableCellValue,
};
