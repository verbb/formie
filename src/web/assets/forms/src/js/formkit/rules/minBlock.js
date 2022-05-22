import { has } from 'lodash-es';
import { empty } from '@formkit/utils';

const minBlock = (node, address) => {
    const values = node.at('$root').value;

    if (has(values, 'address1Enabled')) {
        return values.autocompleteEnabled || values.address1Enabled || values.address2Enabled || values.address3Enabled || values.cityEnabled || values.stateEnabled || values.zipEnabled || values.countryEnabled;
    }

    if (has(values, 'prefixEnabled')) {
        return values.prefixEnabled || values.firstNameEnabled || values.middleNameEnabled || values.lastNameEnabled;
    }

    return true;
};

minBlock.skipEmpty = false;

export default minBlock;
