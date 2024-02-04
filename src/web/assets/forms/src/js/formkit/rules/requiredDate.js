import { empty } from '@formkit/utils';

const requiredDate = (node, address) => {
    if (node.value && typeof node.value === 'object' && !node.value.date) {
        return false;
    }

    return !empty(node.value);
};

requiredDate.skipEmpty = false;

export default requiredDate;
