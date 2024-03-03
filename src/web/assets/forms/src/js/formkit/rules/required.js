import { empty } from '@formkit/utils';

const required = function required({ value }, action = 'default') {
    return action === 'trim' && typeof value === 'string' ? !empty(value.trim()) : !empty(value);
};

required.skipEmpty = false;

export default required;
