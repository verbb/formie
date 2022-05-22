import { has } from 'lodash-es';
import { empty } from '@formkit/utils';

const emailOrVariable = (node, address) => {
    const variableRegex = /({.*?})/;
    const emailRegex = /(^$|^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$)/;

    if (variableRegex.test(node.value)) {
        return true;
    }

    return emailRegex.test(node.value);
};

export default emailOrVariable;
