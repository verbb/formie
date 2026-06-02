import React from 'react';
import { Lightswitch } from '@verbb/plugin-kit-react/components';
import { usePreviewSchemaContext } from './PreviewSchemaContext';
import { extractTableCellValue, toTableDisplayValue } from './previewValueUtils';

export const PreviewTable = () => {
    const { field } = usePreviewSchemaContext();
    const columns = Array.isArray(field?.columns)
        ? field.columns
        : Object.values(field?.columns || {});
    const rows = Array.isArray(field?.defaults) && field.defaults.length > 0
        ? field.defaults.slice(0, 2)
        : [{}];

    if (!columns.length) {
        return (
            <div className="formie-field-preview-input">
                {Craft.t('formie', 'Table')}
            </div>
        );
    }

    const isThinColumn = (column) => {
        const type = column?.type || 'singleline';
        return Boolean(column?.thin || type === 'checkbox' || type === 'lightswitch');
    };

    const getSelectOptionLabel = (column, rawValue) => {
        const value = toTableDisplayValue(rawValue);
        const options = Array.isArray(column?.options) ? column.options : [];
        const match = options.find((option) => {
            return String(option?.value ?? '') === value;
        });

        return match?.label || value;
    };

    const renderCell = (column, rowValue, key) => {
        const type = column?.type || 'singleline';
        const value = extractTableCellValue(rowValue);

        if (type === 'checkbox') {
            return (
                <div key={key} className="formie-field-preview-table-checkbox-only">
                    <input type="checkbox" checked={Boolean(value)} readOnly />
                </div>
            );
        }

        if (type === 'lightswitch') {
            return (
                <div key={key} className="formie-field-preview-table-lightswitch-only">
                    <Lightswitch checked={Boolean(value)} disabled size="sm" />
                </div>
            );
        }

        if (type === 'select') {
            const selectedLabel = getSelectOptionLabel(column, value);

            return (
                <div key={key} className="formie-field-preview-table-select-indicator">
                    {selectedLabel && (
                        <span className="formie-field-preview-table-cell-value">
                            {selectedLabel}
                        </span>
                    )}
                </div>
            );
        }

        if (type === 'color') {
            const colorValue = toTableDisplayValue(value);

            return (
                <div key={key} className="formie-field-preview-table-color-cell">
                    {colorValue && (
                        <span className="formie-field-preview-table-color-swatch" style={{ backgroundColor: colorValue }} />
                    )}

                    <span className="formie-field-preview-table-cell-value">
                        {colorValue || ' '}
                    </span>
                </div>
            );
        }

        if (type === 'heading' || type === 'html') {
            return (
                <div key={key} className="formie-field-preview-table-static-cell">
                    {toTableDisplayValue(value) || ' '}
                </div>
            );
        }

        const displayValue = toTableDisplayValue(value);

        return (
            <div key={key} className="formie-field-preview-table-empty-cell">
                {displayValue && (
                    <span className="formie-field-preview-table-cell-value">
                        {displayValue}
                    </span>
                )}
            </div>
        );
    };

    return (
        <div className="formie-field-preview-table-wrap">
            <table className="formie-field-preview-table">
                <thead>
                    <tr>
                        {columns.map((column, index) => {
                            return (
                                <th key={index} className={isThinColumn(column) ? 'formie-field-preview-table-col--thin' : ''}>
                                    {column.heading || ' '}
                                </th>
                            );
                        })}
                    </tr>
                </thead>

                <tbody>
                    {rows.map((defaultRow, rowIndex) => {
                        return (
                            <tr key={rowIndex}>
                                {columns.map((column, columnIndex) => {
                                    const cellKey = column.id || column.handle || `col${columnIndex + 1}`;
                                    const value = defaultRow?.[cellKey] ?? '';

                                    return (
                                        <td key={columnIndex} className={isThinColumn(column) ? 'formie-field-preview-table-col--thin' : ''}>
                                            {renderCell(column, value, `${rowIndex}-${columnIndex}`)}
                                        </td>
                                    );
                                })}
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
};
