import { normalizeFormData, serializeFormData } from '@form-builder/hooks/useFormTools';

const saveFormSnapshot = (values = {}) => {
    return serializeFormData(normalizeFormData(values || {}));
};

export { saveFormSnapshot };
