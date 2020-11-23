import get from 'lodash/get';

export const parse = function(attribute, obj) {
    if (typeof attribute !== 'string') {
        return attribute;
    }

    if (!obj) {
        return null;
    }

    // Check for negative
    let negative = false;
    let compare = '';

    if (attribute.includes('||')) {
        var attributes = attribute.split('||');
        
        var results = attributes.map(attr => {
            return parse(attr, obj);
        });

        return results.includes(true);
    }

    if (attribute.startsWith('!')) {
        attribute = attribute.replace('!', '');
        negative = true;
    }

    if (attribute.includes('=')) {
        [ attribute, compare ] = attribute.split('=');
    }

    if (attribute) {
        if (compare) {
            if (negative) {
                return get(obj, attribute) !== compare;
            } else {
                return get(obj, attribute) === compare;
            }
        } else {
            if (negative) {
                return !get(obj, attribute);
            } else {
                return get(obj, attribute);
            }
        }
    }

    return null;
};
