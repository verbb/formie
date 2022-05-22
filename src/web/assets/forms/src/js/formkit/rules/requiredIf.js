import { empty } from '@formkit/utils';

const requiredIf = (node, address) => {
    const foreignValue = node.at(address)?.value;

    return foreignValue ? !empty(node.value) : true;
};

requiredIf.skipEmpty = false;

export default requiredIf;
