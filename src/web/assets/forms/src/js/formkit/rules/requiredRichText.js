import { empty } from '@formkit/utils';

const requiredRichText = (node, address) => {
    if (node.value === '[{"type":"paragraph","attrs":{"textAlign":"start"}}]') {
        return false;
    }

    return !empty(node.value);
};

requiredRichText.skipEmpty = false;

export default requiredRichText;
