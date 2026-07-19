import { registerFormFields } from '@verbb/plugin-kit-react/forms';
import { CodeEditorField } from '@verbb/plugin-kit-react/forms/fields/CodeEditorField';

import { CalculationsField } from './CalculationsField.jsx';
import { HandleField } from './HandleField.jsx';
import { ListField } from './ListField.jsx';
import { RichTextField } from './RichTextField.jsx';
import { VariablePickerField } from './VariablePickerField.jsx';
import { ElementSelectField } from '@utils/ElementSelectField.jsx';

/**
 * SchemaForm `$field` keys that left kit builtins and are Formie-owned.
 * Call from every CP entry that renders Formie schemas (builder, defaults, form groups, …).
 */
export function registerFormieOwnedSchemaFields(extraFields = {}) {
    registerFormFields({
        handle: HandleField,
        list: ListField,
        richText: RichTextField,
        variablePicker: VariablePickerField,
        calculations: CalculationsField,
        elementSelect: ElementSelectField,
        codeEditor: CodeEditorField,
        ...extraFields,
    });
}
